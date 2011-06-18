<?php

require_once('utils.php');
require_once('html.php');
require_once('readonly.php');

/**
 * Static From Builder class.
 */
class FormBuilder
{
	/**
	 * Create a table for use in a form element from the supplied listing of fields.
	 * @param fields The fields to use.
	 * @returns An HTMLElement representing the table of the fields.
	 */
	public static function createFormTable($fields, FormResult $result = null)
	{
		$table = new HTMLElement('table');
		$hiddenInputs = array();

		foreach ($fields as $id => $field)
		{
			$type = isset($field['type']) ? $field['type'] : 'text';

			switch ($type)
			{
				case 'textarea':
					$input = new HTMLElement('textarea');
					$input->appendChildren(@$result->$id);
					break;
				case 'select':
					$options = self::idToNameArray($field['options']);
					$input = HTML::select($options, null, @$result->$id);
					break;
				default:
					$input = new HTMLElement('input');
					$input->value = @$result->$id;
			}

			$input->id = $input->name = $id;

			if ($type == 'hidden')
			{
				$input->type = $type;
				$hiddenInputs[$id] = $input;
				continue;
			}

			$row = new HTMLElement('tr');
			$table->appendChildren($row);
			$header = new HTMLElement('th');
			$cell = new HTMLElement('td');
			$row->appendChildren($header, $cell);
			$label = new HTMLElement('label');
			$header->appendChildren($label);
			$cell->appendChildren($input);

			$label->for = $id;
			$row->title = $input->title = $label->title = $field['title'];
			$label->appendChildren(self::idToName($id));

			if ($result !== null && $result->isFieldInError($id))
			{
				$row->class = 'error';
				$row->title = implode(" \n", $result->getFieldErrors($id));
			}

			if (in_array($type, array('float', 'integer', 'number')))
			{
				self::numberFieldToInput($field, $input, $cell);
			}
		}

		return array($table, $hiddenInputs);
	}

	private static function numberFieldToInput($field, $input, $cell)
	{
		$input->number = 'number';
		$input->size = isset($field['size']) ? $field['size'] : 3;
		if (isset($field['max']))
		{
			$input->min = $field['max'];
		}
		if (isset($field['min']))
		{
			$input->min = $field['min'];
		}

		if (isset($field['step']))
		{
			$input->step = $field['step'];
		}
		else if ($field['type'] == 'integer')
		{
			$input->step = 1;
		}

		if (isset($field['units']))
		{
			// multiple units -> select box
			if (is_array($field['units']))
			{
				$units = self::idToNameArray(array_keys($field['units']));
				$unitsSelect = HTML::select($units);
				$cell->appendChildren($unitsSelect);
				$unitsSelect->id = $unitsSelect->name = $input->id.'-number-units';
			}
			else
			{
				$cell->appendChildren($field['units']);
			}
		}
	}

	public static function idToName($id)
	{
		$spaces = str_replace('_', ' ', $id);
		$upper = ucwords($spaces);
		return $upper;
	}

	public static function idToNameArray($array)
	{
		$names = array_map(array(__CLASS__, 'idToName'), $array);
		$combined = array_combine($array, $names);
		return $combined;
	}
}

class FormResult extends ReadOnlyProperties
{
	/**
	 * An array of the errors in the form submission.
	 * If this array is populated then the form should be returned to the user with no action taken.
	 */
	private $errors = array();

	public function __construct()
	{
		parent::__construct(False);
	}

	public function addError($fieldId, $reaason)
	{
		$this->checkReadOnly();
		$this->errors[$fieldId][] = $reaason;
	}

	/**
	 * Whether or not the form submission was successfully validated.
	 */
	public function isValid()
	{
		$count = count($this->errors);
		return $count == 0;
	}

	public function getFieldData()
	{
		return $this->data;
	}

	public function getErrorsList()
	{
		return $this->errors;
	}

	public function getFieldsInError()
	{
		return array_keys($this->errors);
	}

	public function isFieldInError($fieldId)
	{
		$errors = $this->getFieldErrors($fieldId);
		$inError = count($errors) > 0;
		return $inError;
	}

	public function getFieldErrors($fieldId)
	{
		$errors = isset($this->errors[$fieldId]) ? $this->errors[$fieldId] : array();
		return $errors;
	}

	public function getErrorsPrintable()
	{
		ksort($this->errors);
		$ul = new HTMLElement('ul', array('class' => 'inform error'));
		foreach ($this->errors as $fieldId => $errors)
		{
			foreach ($errors as $error)
			{
				$li = new HTMLElement('li', null, $error);
				$ul->appendChildren($li);
			}
		}
		return $ul;
	}
}

class FormValidator
{
	private $input;
	private $fields;

	/**
	 * @param input An array of the raw values, typically $_POST or similar.
	 * @param fields The fields expected.
	 */
	public function __construct($input, $fields)
	{
		$this->input = $input;
		$this->fields = $fields;
	}

	/**
	 * Construct a FormResult object that contains the validated results from the submitted form.
	 * @returns A FormResult containing the valid inputs and a listing of the errors.
	 */
	public function getResult()
	{
		$result = new FormResult();
		foreach ($this->fields as $id => $field)
		{
			$this->checkField($id, $result);
		}
		return $result;
	}

	public function checkField($id, FormResult $result)
	{
		$value = $this->getValue($id);
		// TODO: somehow make the idtoName functions common, and possibly allow custom callbacks
		$name = FormBuilder::idToName($id);
		if ($value === null)
		{
			if ($this->getProperty($id, 'type') == 'hidden')
			{
				$result->addError($id, "Invalid '$id' supplied.");
			}
			elseif ($this->getProperty($id, 'required') === True)
			{
				$result->addError($id, "Required field '$name' not completed.");
			}
			return;
		}

		// store the actual value
		$result->$id = $value;

		$type = $this->getProperty($id, 'type');
		// TODO: make this comparison common somehow
		if (in_array($type, array('float', 'integer', 'number')))
		{
			switch ($type)
			{
				case 'number':
					// TODO: error handling in here, so many ways it can go wrong.
					$unitValue = $this->getValue($id.'-number-units');
					$units = $this->getProperty($id, 'units');
					if (!isset($units[$unitValue]))
					{
						$result->addError($id, "Unknown units selection '$unitValue' for '$name'.");
					}
					else
					{
						$valueMultiplier = $units[$unitValue];
						$value *= $valueMultiplier;
					}
					// intentionally no break here -- after multiplication we should get an integer

				case 'integer':
					$int = intval($value);
					if ($int != $value)
					{
						$result->addError($id, "Field '$name' must be an integer (in its smallest unit).");
					}
					break;
			}

			$min = $this->getProperty($id, 'min');
			if ($min !== null && $value < $min)
			{
				$result->addError($id, "Field '$name' must be greater than or equal to $min.");
			}

			$max = $this->getProperty($id, 'max');
			if ($max !== null && $value > $max)
			{
				$result->addError($id, "Field '$name' must be less than or equal to $max.");
			}
		}
	}

	private function getValue($id)
	{
		$value = isset($this->input[$id]) && $this->input[$id] != '' ? $this->input[$id] : null;
		return $value;
	}

	private function getProperty($id, $name)
	{
		$field = $this->fields[$id];
		$value = isset($field[$name]) ? $field[$name] : null;
		return $value;
	}
}
