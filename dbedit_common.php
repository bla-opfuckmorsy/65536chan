<?php
function deleteThread($threadnum, $boardname)
{
	$mongo = new Mongo();
	$board = $mongo->selectDB("chan")->selectCollection($boardname);
	
	$thread = $board->findOne(array('threadnum' => $threadnum));
	
	$threadPosts = $threadDoc['posts'];

	//Make sure the files from the thread's posts are also removed from the server
	foreach($threadPosts as $post)
	{
		foreach($post['files'] as $file)
		{
			unlink($file);
		}
	}
	
	//Finally, remove the thread from the database
	$board->remove(array('threadnum' => $threadnum));
}

function deletePost($postnum, $boardname)
{
	$mongo = new Mongo();
	$board = $mongo->selectDB("chan")->selectCollection($boardname);

	//Find the thread in which the post specified by $postnum resides
	$thread = $board->findOne(array('posts.'.$postnum => array('$exists' => true)));
	
	//Get the files associated with this post
	$files = $thread['posts'][$postnum]['files'];
	
	//Remove them from the server
	foreach($files as $file)
	{
		unlink($file);
	}
	
	//Remove this post from the thread's list of posts
	$board->update(array('posts.'.$postnum => array('$exists' => true)), array('$unset' => array('$posts.'.$postnum => array('$exists' => true))));
}
?>
