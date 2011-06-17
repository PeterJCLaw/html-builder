<?php

require_once('html.php');

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
	public static function createFormTable($fields)
	{
		$table = new HTMLElement('table');

		foreach ($fields as $id => $field)
		{
			$row = new HTMLElement('tr');
			$table->appendChildren($row);
			$header = new HTMLElement('th');
			$cell = new HTMLElement('td');
			$row->appendChildren($header, $cell);

			switch (@$field['type'])
			{
				case 'textarea':
					$input = new HTMLElement('textarea');
					break;
				case 'select':
					$options = self::idToNameArray($field['options']);
					$input = HTML::select($options);
					break;
				default:
					$input = new HTMLElement('input');
			}

			$label = new HTMLElement('label');
			$header->appendChildren($label);
			$cell->appendChildren($input);

			$input->id = $input->name = $label->for = $id;
			$row->title = $input->title = $label->title = $field['title'];
			$label->appendChildren(self::idToName($id));

			if (!empty($field['type'])
			 && in_array($field['type'], array('float', 'integer', 'number')))
			{
				self::numberFieldToInput($field, $input, $cell);
			}
		}

		return $table;
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
