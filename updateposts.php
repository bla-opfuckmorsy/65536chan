<?php

header('Content-type: text/plain');

require "postserve_common.php";

$boardname = "test";

$json_string = file_get_contents('php://input');
$json_data = json_decode($json_string);

$mongo = new Mongo();
$board = $mongo->selectDB("chan")->selectCollection($GLOBALS['boardname']);

//find the thread
$threadDoc = $board->findOne(array('threadnum' => intval($json_data->threadnum)));

//get the posts from the thread
$threadPosts = $threadDoc['posts'];

$postsret = array();
$index_postsret = 0;

//0 is a placeholder for a newly-loaded page, I should really use -1 or something instead
if($json_data->postnum != 0)
{
	foreach($threadPosts as $postnum=>$postData)
	{
		//So we only return posts past the given postnum
		if($postnum > $json_data->postnum)
		{
			stripUnused($postData);
			$postsret[$index_postsret++] = $postData;
		}
	}
} else
{
	//We have a newly-loaded page, so just add all the posts
	foreach($threadPosts as $post)
	{
		//This stripUnused is the reason I don't just assign $threadposts to $postsret; we need to strip off unused data first
		stripUnused($post);
		$postsret[$index_postsret++] = $post;
	}
}

//Encode the result and send it to the client
echo json_encode($postsret);
?>
