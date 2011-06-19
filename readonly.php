<?php

require_once('utils.php');

class ReadOnlyBase
{
	protected $message;

	protected function __construct($readOnly)
	{
		if ($readOnly === True)
		{
			$this->message = 'Instances of '.get_called_class().' are read-only.';
		}
		else
		{
			$this->message = 'This '.get_called_class().' has been marked read-only.';
		}
	}

	private $isReadOnly = False;

	public function markReadOnly()
	{
		$this->isReadOnly = True;
	}

	protected function checkReadOnly()
	{
		if ($this->isReadOnly === True)
		{
			trigger_error($this->message, E_USER_ERROR);
		}
	}
}

class ReadOnlyProperties extends ReadOnlyBase
{
	protected $data = array();

	protected function __construct($readOnly, $data = null)
	{
		if ($data !== null && is_array($data) && isAssoc($data))
		{
			$this->data = $data;
		}
		parent::__construct($readOnly);
	}

	public function __get($name)
	{
		$value = $this->data[$name];
		return $value;
	}

	public function __isset($name)
	{
		$isset = isset($this->data[$name]);
		return $isset;
	}

	public function __set($name, $value)
	{
		$this->checkReadOnly();
		$this->data[$name] = $value;
	}

	public function __unset($name)
	{
		$this->__set($name, null);
	}
}

class Properties extends ReadOnlyProperties
{
	public function __construct($data = null)
	{
		parent::__construct(False, $data);
	}

	public function checkReadOnly()
	{
		return;
	}
}
