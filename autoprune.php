<?php

require "dbedit_common.php";

function prune($boardname, $maxthreads)
{
	$mongo = new Mongo();
	$board = $mongo->selectDB("chan")->selectCollection($boardname);
	
	$threads = $board->find(array('threadnum' => array('$exists' => true)));
	
	$numthreads = count($threads);
	
	if($numthreads <= $maxthreads)
	{
		return;
	}
	
	$averages = array();
	
	foreach($threads as $thread)
	{
		$averages[$thread['threadnum']] = calculateAvgDelay($thread);
	}
	
	rsort($averages);
	
	foreach($averages as $thn=>$avgval)
	{
		if($numthreads <= $maxthreads)
		{
			break;
		}
		
		deleteThread($thn, $boardname);
		$numthreads--;
	}
}

function calculateAvgDelay($thread)
{
	$lasttime = $thread['posts'][0]['posttime'];
	$average = 0;
	
	foreach($thread['posts'] as $toast)
	{
		$average /= 2;
		$average += $toast['posttime'] / 2;
	}
	
	return $average;
}
?>
