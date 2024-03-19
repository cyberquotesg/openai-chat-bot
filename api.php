<?php

include "library.php";

header('Content-Type: application/json; charset=utf-8');

// set default empty params
$params = [];

// find out the params that needed
$required_params = (new ReflectionMethod("library", $_REQUEST["function"]))->getParameters();

// auto fill parameters
foreach($required_params as $index => $required_param)
{
	$param_name = $required_param->name;

	// maybe it exists on $_REQUEST
	if (isset($_REQUEST[$param_name]))
	{
		$params[] = $_REQUEST[$param_name];
	}

	// maybe it exists on $_FILES
	else if (isset($_FILES[$param_name]))
	{
		if (!file_exists('./files')) mkdir('./files', 0777, true);
		$params[] = move_uploaded_file($_FILES[$param_name]["tmp_name"], "./files/" . $_FILES[$param_name]["name"]);
	}

	// otherwise, die
	else
	{
		die("param " . $param_name . " is not defined");
	}
}

$data = call_user_func_array("\\library::" . $_REQUEST["function"], $params);
print json_encode($data);

?>