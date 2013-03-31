<?php
function deleteThread($threadnum, $boardname)
{
	$mongo = new Mongo();
	$board = $mongo->selectDB("chan")->selectCollection($boardname);
	
	$thread = $board->findOne(array('threadnum' => $threadnum));
	
	$threadPosts = $threadDoc['posts'];

	foreach($threadPosts as $post)
	{
		foreach($post['files'] as $file)
		{
			unlink($file);
		}
	}
	
	$board->remove(array('threadnum' => $threadnum));
}

function deletePost($postnum, $boardname)
{
	$mongo = new Mongo();
	$board = $mongo->selectDB("chan")->selectCollection($boardname);
	
	$thread = $board->findOne(array('posts.'.$postnum => array('$exists' => true)));
	
	$files = $thread['posts'][$postnum]['files'];
	
	foreach($files as $file)
	{
		unlink($file);
	}
	
	$board->update(array('posts.'.$postnum => array('$exists' => true)), array('$unset' => array('$posts.'.$postnum => array('$exists' => true))));
}
?>
