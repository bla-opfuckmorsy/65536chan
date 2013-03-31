<?php

$boardname = "test";

require "post_common.php";

$json_string = file_get_contents('php://input');
$json_data = json_decode($json_string);
$topic = $json_data->threadtopic;
$name = $json_data->postname;
$text = $json_data->posttext;
$mediatype = $json_data->mtype;
$filename = $json_data->fname;
$mediadata = $json_data->mdata;

$mediaHTML = "";
$postfiles = array();

$mongo = new Mongo();
$board = $mongo->selectDB("chan")->selectCollection($GLOBALS['boardname']);

switch($mediatype)
{
	case 'file':
		if($filename != "")
		{
			$serverfile = time()."_".$filename;
			$decoded_mediadata = base64_decode($mediadata);
			$mediaHTML = generateAudioHTML($serverfile, $decoded_mediadata, $postfiles);
			if($mediaHTML == "")
			{
				$mediaHTML = generateImageHTML($serverfile, $decoded_mediadata, 250, $postfiles);
			}
			unset($decoded_mediadata);
		} else
		{
			die("Error: You need an image or video or audio to post!");
		}
		break;
	case "ExternalDataAtURL":
		if($mediadata != "")
		{
			$mediaHTML = generateExternalMediaHTML($mediadata);
		} else
		{
			die("Error: No media URL specified!");
		}
		break;
	default:
		die("Error: No media type found, you need an image or video or something to start a thread!");
}

if($mediadata != "")
{
	unset($mediadata);
	$post_data = generatePostData($topic, $name, $text, $mediaHTML, $postfiles);
	if(savePost_Mongo($topic, $post_data))
	{
		echo $post_data['postnum'];
	}
} else
{
	die("Error:You need an image or video or something to post!");
}

function generatePostData($posttopic, $postname, $postText, $media_html, &$pfiles)
{
	$postdata = array();
	
	if($postname == "")
	{
		$postdata['postname'] = "Anonymous";
	} else
	{
		$postdata['postname']  = $postname;
	}
	$postdata['special'] = json_encode(array("OP_Post"=>true, "topic"=>$posttopic));
	$postdata['postnum'] = getPostNumber_Mongo();
	$postdata['posttime'] = time();
	$replyarray = array();
	$postdata['posttexthtml'] = processText($postText, $replyarray);
	$postdata['mediahtml'] = $media_html;
	$postdata['replysto'] = $replyarray;
	foreach($replyarray as $reply)
	{
		addReplyIfExists($postnumber, $reply);
	}
	$postdata['replysfrom'] = array();
	$postdata['files'] = $pfiles;
	
	return $postdata;
}

function savePost_Mongo($threadtopic, $postArray)
{
	$threaddoc = array('threadnum' => $postArray['postnum'],
		'topic' => $threadtopic,
		'lastposttime' => time(),
		'numposts' => 1,
		'averagepostdelay' => 0,
		'posts' => array($postArray['postnum'] => $postArray));
	
	$GLOBALS['board']->save($threaddoc);
	
	return true;
}
?>
