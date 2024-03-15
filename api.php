<?php

include "library.php";

$data = call_user_func_array("\\library::" . $_GET["function"], $_GET["params"]);
print json_encode($data);

?>