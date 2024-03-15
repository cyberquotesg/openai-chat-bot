<?php

include "library.php";

header('Content-Type: application/json; charset=utf-8');
$data = call_user_func_array("\\library::" . $_REQUEST["function"], $_REQUEST["params"]);
print json_encode($data);

?>