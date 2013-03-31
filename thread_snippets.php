<?php
header('Content-type: text/plain');

require "postserve_common.php";

$boardname = "test";

$json_string = file_get_contents('php://input');
$json_data = json_decode($json_string);

class ThreadSample{
	public $threadnumber = 0;
	public $threadposts = NULL;
}

$mongo = new Mongo();
$board = $mongo->selectDB("chan")->selectCollection($GLOBALS['boardname']);

$query = array('threadnum'=>array('$exists'=>true));
$threads = $board->find($query);

$response = array();
$index_response = 0;

foreach($threads as $thread)
{
	$ts = array();
	
	$ts['threadnumber'] = $thread['threadnum'];
	$ts['threadposts'] = array();
	
	$threadPosts = $thread['posts'];

	$displayed = 0;
	
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
	
	$response[$index_response++] = $ts;
}

echo json_encode($response);
?>
