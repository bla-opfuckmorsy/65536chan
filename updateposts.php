<?php

header('Content-type: text/plain');

require "postserve_common.php";

$boardname = "test";

$json_string = file_get_contents('php://input');
$json_data = json_decode($json_string);

$mongo = new Mongo();
$board = $mongo->selectDB("chan")->selectCollection($GLOBALS['boardname']);

$threadDoc = $board->findOne(array('threadnum' => intval($json_data->threadnum)));

$threadPosts = $threadDoc['posts'];

$postsret = array();
$index_postsret = 0;

if($json_data->postnum != 0)
{
	foreach($threadPosts as $postnum=>$postData)
	{
		if($postnum > $json_data->postnum)
		{
			stripUnused($postData);
			$postsret[$index_postsret++] = $postData;
		}
	}
} else
{
	foreach($threadPosts as $post)
	{
		stripUnused($post);
		$postsret[$index_postsret++] = $post;
	}
}

echo json_encode($postsret);
?>
