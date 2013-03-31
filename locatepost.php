<?php
header('Content-type: text/plain');

require "postserve_common.php";

$boardname = "test";

$json_string = file_get_contents('php://input');
$json_data = json_decode($json_string);

$mongo = new Mongo();
$board = $mongo->selectDB("chan")->selectCollection($GLOBALS['boardname']);

$threadDoc = $board->findOne(array('posts.'.$json_data->postnum => array('$exists' => true)));

class PostLocationAndJSON{
	public $postfound = false;
	public $postthread = 0;
	public $postData = NULL;
}

$searchResult = new PostLocationAndJSON();
if(count($threadDoc) != 0)
{
	$searchResult->postfound = true;
	$searchResult->postthread = $threadDoc['threadnum'];
	$searchResult->postData = $threadDoc['posts'][intval($json_data->postnum)];
	stripUnused($searchResult->postData);
} else
{
	$searchResult->postfound = false;
}

echo json_encode($searchResult);
?>
