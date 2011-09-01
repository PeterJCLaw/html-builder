<?php

require_once('utils.php');

/**
 * Static HTML element helper class.
 */
class HTML
{
	/**
	 * Get a listing of the html tags that can self-close.
	 * @returns An array containing the names of self-closable tags.
	 */
	public static function selfClosingTags()
	{
		return array('area' ,'base' ,'basefont' ,'br' ,'hr' ,'input' ,'img' ,'link' ,'meta');
	}

	/**
	 * Convenience function that creates an HTML form input from a set of attributes.
	 * @param attributes An array of attributes suitable for passing to arrayToAttributes.
	 * @returns An input element with the given attributes.
	 */
	public static function input($attributes)
	{
		$attributesString = HTMLElement::arrayToAttributes($attributes);
		return '<input'.$attributesString." />\n";
	}

	/**
	 * Convenience function that creates a select HTMLElement with the given options.
	 * @param options A list of options to use. If the array is associative then the keys are used as the values for the options.
	 * @param attributes An array of attributes suitable for passing to arrayToAttributes.
	 * @param selected The value of the selected option.
	 * @returns An input element with the given attributes.
	 */
	public static function select($options, $attributes = null, $selected = null)
	{
		$select = new HTMLElement('select', $attributes);
		$isAssoc = isAssoc($options);
		foreach ($options as $value => $option)
		{
			$opt = new HTMLElement('option');
			$opt->value = $isAssoc ? $value : $option;
			if ($opt->value == $selected)
			{
				$opt->selected = 'selected';
			}
			$opt->appendChildren($option);
			$select->appendChildren($opt);
		}
		return $select;
	}
}

/**
 * Represents an element in an HTML hierachy.
 */
class HTMLElement
{
	private $name;
	private $attributes = array();
	private $children = array();

	/**
	 * Creates a new HTMLElement.
	 * @param name The name of the tag for this element.
	 * @param attributes Any attributes to give the element.
	 * @param children
	 */
	public function __construct($name, $attributes = null, $children = null)
	{
		$this->name = $name;
		if ($attributes !== null)
		{
			$this->attributes = $attributes;
		}
		if ($children !== null)
		{
			$args = func_get_args();
			// remove the first two, since these are the name and attributes.
			array_shift($args);
			array_shift($args);
			$this->children = $args;
		}
	}

	/**
	 * Convert the object to its string representation, including any children.
	 */
	public function toString()
	{
		return $this->__toString();
	}

	public function __toString()
	{
		$attributesString = self::arrayToAttributes($this->attributes);
		$string = "<$this->name$attributesString";
		if (count($this->children) > 0)
		{
			$string .= ">\n";
			foreach ($this->children as $child)
			{
				$string .= $child;
			}
			$string .= "</$this->name>";
		}
		else
		{
			if (in_array($this->name, HTML::selfClosingTags()))
			{
				$string .= " />\n";
			}
			else
			{
				$string .= "></$this->name>";
			}
		}
		return $string;
	}

	/**
	 * Set the value of an attribute to this element.
	 * @param name The name of the attribute.
	 * @param value The value of the attribute.
	 */
	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
		if ($value === null)
		{
			unset($this->attributes[$name]);
		}
	}

	public function __set($name, $value)
	{
		$this->setAttribute($name, $value);
	}

	public function __unset($name)
	{
		$this->__set($name, null);
	}

	public function __get($name)
	{
		return $this->attributes[$name];
	}

	/**
	 * Create a new HTMLElement and append it as a child of this element.
	 * @param name The name of the element.
	 * @param attributes Any attributes to add.
	 * @param children Any children to add to the newly created element.
	 * @returns The newly created element.
	 */
	public function createChild($name, $attributes = null, $children = null)
	{
		$elem = new HTMLElement($name, $attributes, $children);
		$this->appendChildren($elem);
		return $elem;
	}

	/**
	 * Append an arbitrary number of children to the element.
	 * @param children Children to add. These can be HTMLElements or strings.
	 * @param isArray Whether or not the arguments are passed as an array.
	 *   When True the second parameter is treated as the array of children to add.
	 *   Otherwise the array of all parameters is used to supply the children.
	 */
	public function appendChildren($children, $isArray = False)
	{
		if ($isArray !== True)
		{
			$children = func_get_args();
		}
		$this->children = array_merge($this->children, $children);
	}

	/**
	 * Converts an associative array to HTML attributes, quotes and all.
	 * Note that this function doesn't do any sanitising or validation.
	 * @param array An array of name => value pairs to convert.
	 * @returns a string that can be included in HTML.
	 */
	public static function arrayToAttributes($array)
	{
		$string = '';
		foreach ($array as $key => $value)
		{
			$string .= " $key=\"$value\"";
		}
		return $string;
	}
}
