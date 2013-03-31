<?php
//Ideally it should get the value of this from $_REQUEST or the JSON or something,
//so it would be easy to use the same script on multiple boards
$boardname = "test";

require "post_common.php";

//Get the JSON that was sent here
$json_string = file_get_contents('php://input');
$json_data = json_decode($json_string);
//Assign it to a bunch of variables, which is kind of pointless
$threadnumber = $json_data->threadnum;
$name = $json_data->postname;
$text = $json_data->posttext;
$mediatype = $json_data->mtype;
$filename = $json_data->fname;
$mediadata = $json_data->mdata;

$mediaHTML = "";
$postfiles = array();

//Start up the connection to the Mongo database that the script will use
$mongo = new Mongo();
$board = $mongo->selectDB("chan")->selectCollection($GLOBALS['boardname']);

//Fun fact:  If you touch this block of code, sneaky bugs will pop up hours later when you're testing a seemingly unrelated part of the script
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
				$mediaHTML = generateImageHTML($serverfile, $decoded_mediadata, 150, $postfiles);
			}
			unset($decoded_mediadata);
		} else
		{
			if($text == "")
			{
				die("Error: You need an image or text or something to post!");
			}
			$mediaHTML = "";
		}
		break;
	case 'ExternalDataAtURL':
		if($mediadata != "")
		{
			$mediaHTML = generateExternalMediaHTML($mediadata);
		} else
		{
			$mediaHTML = "";
		}
		break;
	default:
		$mediaHTML = $mediatype;
		break;
}

//same goes for this one
if(($mediadata != "") || ($text != ""))
{
	unset($mediadata);
	$pn = getPostNumber_Mongo();
	$post_Array = generatePostData($name, $text, $pn, $mediaHTML, $postfiles);
	if(savePost_Mongo($threadnumber, $pn, $post_Array))
	{
		echo json_encode($post_Array);
	}
} else
{
	echo "Error:You need text or an image to post!";
}

function generatePostData($postname, $postText, $postnumber, $media_html, &$pfiles)
{
	$postdata = array();
	
	if($postname == "")
	{
		//neckbeards and 13-year-old script kiddies, cruise-control for edgy and cool
		$postdata['postname'] = "Anonymous";
	} else
	{
		//namefags
		$postdata['postname']  = $postname;
	}
	$postdata['postnum'] = $postnumber;
	$postdata['posttime'] = time();
	//Store a list of replies made in the post
	$replyarray = array();
	$postdata['posttexthtml'] = processText($postText, $replyarray);
	$postdata['mediahtml'] = $media_html;
	$postdata['replysto'] = $replyarray;
	//update the posts that have been replied to
	foreach($replyarray as $reply)
	{
		addReplyIfExists($postnumber, $reply);
	}
	//For replies from other posts
	$postdata['replysfrom'] = array();
	//generally used for posts that are OP or that need special goodies inside
	$postdata['special'] = "";
	$postdata['files'] = $pfiles;
	
	return $postdata;
}

//Save the post in the database
function savePost_Mongo($threadnum, $postnum, $postArray)
{
	$GLOBALS['board']->update(array('threadnum' => $threadnum), array('$set' => array('posts.'.$postnum => $postArray)));
	
	return true;
}
?>
