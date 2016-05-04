<?php

function readConfig($filename)
{
	// Read the config from a YAML file and put the config entries in a named array
	$config_yaml = Spyc::YAMLLoad($filename);
	return ($config_yaml);
}

function timestamp_sort($a,$b)
{
	return $a['timestamp'] < $b['timestamp'];
}
?>
