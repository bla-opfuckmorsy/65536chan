<?php

require "dbedit_common.php";

$boardname = "test";

$json_string = file_get_contents('php://input');
$json_data = json_decode($json_string);

switch($json_data->func)
{
	case "delete_thread":
		deleteThread($json_data->data, $boardname);
		break;
	default:
		break;
}
?>
