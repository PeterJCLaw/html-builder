<?php

require_once('html.php');
require_once('forms.php');

$hiddenValue = 'beans';
$newIdValue = '@new@';
$formResult = null;

// These fields are used to build up the submission form, as well as validate it.

$fields = array(
	'name' => array(
		'title' => 'The name of the object'
		,'required' => true
	)
	// the description is optional.
	,'description' => array(
		'type' => 'textarea'
		,'title' => 'A description of the object.'
	)
	// The min, max, and types (and units) are used for validation.
	// The numeric types (integer, float, number) support min & max values.
	// These are validated server-side, and supplied in HTML5 properties for browser validation if possible.
	// Type 'number' forces the incoming number to be an integer, after some coercion:
	// The incoming number is multiplied by the associated value from the units listing, and this is then treated as an integer
	// There is no need for one of the values to be 1, but this is expected.
	,'price' => array(
		'type' => 'number'
		,'required' => true
		,'title' => 'How much the object costs.'
		,'min' => 0
		,'units' => array('pounds' => 100, 'pence' => 1)
	)
	// As single value in 'units' is simply displayed for the user.
	// Note that despite having a minimum value this field remains not required.
	// Size validation is done after presence validation, and only if the field is submitted.
	,'height' => array(
		'type' => 'integer'
		,'title' => 'How tall the object is.'
		,'units' => 'cm'
		,'min' => 1
		,'max' => 1000
	)
	// Hidden fields can be used for all sorts of things, and are always considered required, but give a different error if missing.
	// Hidden fields are the only type that don't expect a title value.
	// They are returned separately from the main table, and need to be added to the output manually.
	,'id' => array(
		'type' => 'hidden'
	)
);

$body = new HTMLElement('div');

// Check for submissions
if (isset($_POST['submit-hidden']) && $_POST['submit-hidden'] == $hiddenValue)
{
	$formValidator = new FormValidator($_POST, $fields);
	$formResult = $formValidator->getResult();

	if (!$formResult->isValid())
	{
		$body->appendChildren($formResult->getErrorsPrintable());
	}
	else
	{
		$li = new HTMLElement('li', null, 'It all worked');
		$ul = new HTMLElement('ul', array('class' => 'inform info'), $li);
		$body->appendChildren($ul);
	}
}

// Show the form

$form = new HTMLElement('form');
$form->method = 'POST';

list($table, $hiddenInputs) = FormBuilder::createFormTable($fields, $formResult);

$form->appendChildren($table);
$body->appendChildren($form);

$hiddenInputs['id']->value = $newIdValue;
$hiddenSubmit = HTML::input(array('name' => 'submit-hidden', 'type' => 'hidden', 'value' => $hiddenValue));
$hiddenInputs[] = $hiddenSubmit;
$submit = HTML::input(array('type' => 'submit', 'value' => 'Save Changes'));
$cell = new HTMLElement('td', array('colspan' => 2), $submit);
$cell->appendChildren($hiddenInputs, True);
$row = new HTMLElement('tr', null, $cell);
$table->appendChildren($row);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>This is a dummy form</title>
	<style type="text/css">
/* inform lists */
ul.inform.error {
	color: red;
}
ul.inform.info {
	color: #009000;
}
table {
	border-collapse: collapse;
}
/* Bad rows */
tr.error {
	background-color: #FF9088;
}
	</style>
</head>
<body>
<?php
	// You can simply echo the HTMLElement.
	// It will automatically convert itself, and all its children, to the appropriate html representation.
	echo $body;
?>
</body>
</html>
