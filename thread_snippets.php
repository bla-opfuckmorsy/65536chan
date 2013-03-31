<?php
header('Content-type: text/plain');

require "postserve_common.php";

$boardname = "test";

$json_string = file_get_contents('php://input');
$json_data = json_decode($json_string);

$mongo = new Mongo();
$board = $mongo->selectDB("chan")->selectCollection($GLOBALS['boardname']);

//Get all the threads in this board
$query = array('threadnum'=>array('$exists'=>true));
$threads = $board->find($query);

$response = array();
$index_response = 0;

foreach($threads as $thread)
{
	//The contents and related data for each thread
	$ts = array();
	
	$ts['threadnumber'] = $thread['threadnum'];
	$ts['threadposts'] = array();
	
	$threadPosts = $thread['posts'];

	$displayed = 0;
	
	//Display the first 6 posts of the thread
	foreach($threadPosts as $post)
	{
		stripUnused($post);
		if(($displayed < 6))
		{
			$ts['threadposts'][$displayed++] = $post;
		} else
		{
			break;
		}
	}

	//Add this thread to our response array and move on to the next one
	$response[$index_response++] = $ts;
}

//Encode the results and send it to the user
echo json_encode($response);
?>
