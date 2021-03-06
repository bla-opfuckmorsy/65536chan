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

//Determine what the user is trying to post
switch($mediatype)
{
	case 'file':
		if($filename != "")
		{
			$serverfile = time()."_".$filename;
			$decoded_mediadata = base64_decode($mediadata);
			//First, let's see if it's an audio file
			$mediaHTML = generateAudioHTML($serverfile, $decoded_mediadata, $postfiles);
			if($mediaHTML == "")
			{
				//If not, then check if it's an image file
				$mediaHTML = generateImageHTML($serverfile, $decoded_mediadata, 250, $postfiles);
				//$mediaHTML ends up being an empty string if it isn't an image file, so it just
				//generates a normal post with no image
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
	//Base64-encoded image and audio data is huge, so free the memory as soon as we're done with it
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

//Generate the post data; this could almost be a common function, but then I'd have to add another parameter
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

//Save the post in a new thread
function savePost_Mongo($threadtopic, $postArray)
{
	//Generate the thread data
	$threaddoc = array('threadnum' => $postArray['postnum'],
		'topic' => $threadtopic,
		'posts' => array($postArray['postnum'] => $postArray));
	
	$GLOBALS['board']->save($threaddoc);
	
	return true;
}
?>
