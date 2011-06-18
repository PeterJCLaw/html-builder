<?php

function isAssoc($arr)
{
	return array_keys($arr) !== range(0, count($arr) - 1);
}

function startsWith($string, $start)
{
	$stringLen = strlen($string);
	$startLen = strlen($start);

	if ($startLen > $stringLen)
	{
		return False;
	}

	$actualStart = substr($string, 0, $startLen);

	$startsWith = $actualStart == $start;
	return $startsWith;
}

function endsWith($string, $end)
{
	$stringLen = strlen($string);
	$endLen = strlen($end);

	if ($endLen > $stringLen)
	{
		return False;
	}

	$actualEnd = substr($string, -1 * $endLen);

	$endsWith = $actualEnd == $end;
	return $endsWith;
}
