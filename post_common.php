<?php
//Generate the images thumbnail, save the image and the thumbnail, and generate the HTML code for embedding the image
function generateImageHTML($image_filename, &$image_binary, $thumbsize, &$postfiles)
{
	$ret = "";
	
	$image = new Imagick();
	$image->readImageBlob($image_binary);
	//Make sure we're actually dealing with an image and not some script kiddy trying to be clever
	if(!checkImage($image))
	{
		return "";
	}

	//We need to do special stuff with GIF's, otherwise they get HORRIBLY mangled
	if($image->getImageFormat() == 'GIF')
	{
		//Write the full-size animated gif to a file
		$image->writeImages("./images/".$image_filename, true);
		//Not sure how this bit works, but it gets a thumbnail with only the first frame of the image
		//because just doing thumbnailImage produces something hideous
		$image->coalesceImages();
		foreach($image as $frame)
		{
			properThumbnailImage($frame, $thumbsize);
			$frame->writeImage("./images/thumbs/".$image_filename);
			break;
		}
	} else
	{
		$image->writeImage("./images/".$image_filename);
		properThumbnailImage($image, $thumbsize);
		$image->writeImage("./images/thumbs/".$image_filename);
	}
	
	//Generate the embed HTML for this image
	$ret .= "<a href=\"/images/".$image_filename."\">";
	$ret .= "<img src=\"/images/thumbs/".$image_filename."\" />";
	$ret .= "</a>";
	
	//Allows for easy tracking of which files belong to which post
	$postfiles[] = "./images/".$image_filename;
	$postfiles[] = "./images/thumbs/".$image_filename;
		
	return $ret;
}

function checkImage(&$image)
{
	try
	{
		$identity= $image->identifyImage();
		//I think there's a faster, less ugly way to do this, but I'm to lazy to think about it
		$format = $identity['format'];
		return !((substr($format, 0, 3) != 'PNG') && (substr($format, 0, 3) != 'GIF') && (substr($format, 0, 4) != 'JPEG'));
	} catch(ImagickException $iexcp)
	{
		//This happens when someone submits data that isn't a JPEG, PNG, or GIF
		return false;
	}
}

//Creates a thumbnail that fits within the given bounds, but isn't stretched to fill them
function properThumbnailImage(&$image, $size)
{
	$iHeight = $image->getImageHeight();
	$iWidth = $image->getImageWidth();
	
	if($iWidth > $iHeight)
	{
		$image->thumbnailImage($size, 0);
	} else
	{
		$image->thumbnailImage(0, $size);
	}
}

//Save an audio file to the server, and generate the code for embedding it withing the page
function generateAudioHTML($audio_filename, &$audio_data, &$postfiles)
{
	$ret = "";
	
	//Codec wars forced me to pick sides, and I use Opera for testing, so I'm sticking with Ogg Vorbis for now
	//In the future, if this immature bickering among the highly-esteemed "professionals" in the industry continues,
	//I'll probably just make the embed default to flash for anything the user's browser can't handle in standard HTML5
	//The internet really needs to pull MPEG's dick out of it's collective asshole
	if(!audioIsOgg($audio_data))
	{
		return "";
	}
	//Save the audio to a file
	$afp = fopen("./audio/".$audio_filename, 'wb');
	if(!$afp)
	{
		die("Error: Can't save audio file!");
	}
	fwrite($afp, $audio_data);
	
	//Generate the embed HTML
	$ret .= "<audio controls preload=\"none\">";
	$ret .= "<source src=\"/audio/".$audio_filename."\" type=\"audio/ogg\">";
	$ret .= "</audio>";
	
	//Useful for keeping track of which files belong to which posts
	$postfiles[] = "./audio/".$audio_filename;
	
	return $ret;
}

//Makes sure the file is a valid Ogg file, this should be improved upon since anybody can fake the first four bytes of a file
function audioIsOgg(&$audio_data)
{
	return (substr($audio_data, 0, 4) == 'OggS');
}

//The user has given us a URL instead of a file
//Figure out what it points to, and how to embed it in the post
function generateExternalMediaHTML($url_to_media)
{
	$urldata = parse_URL($url_to_media);
	$ret = "";
	
	switch($urldata['host'])
	{
		//Does it come from youtube?
		case "www.youtube.com":
			//Is it a video?
			if($urldata['path'] == "/watch")
			{
				$queryparts = explode("&", $urldata['query']);
				
				//Find the video's ID in the sea of query bullshit
				for($x = 0; $x < count($queryparts); $x++)
				{
					if(substr($queryparts[$x], 0, 2) == "v=")
					{
						//Use the ID to generate some embed code
						return generateYoutubeEmbed(substr($queryparts[$x], 2));
					}
				}
			} else
			{
				//I dunno lol
				die("Error:Can't do anything with this youtube path.  ".$urldata['path']);
			}
			break;
		default:
			die("Error:Can't do anything with this URL.  ".$urldata['host']);
	}
}

function generateYoutubeEmbed($vid)
{
	//Used for adding an ID attribute to the video's embed HTML, so we can shrink/expand the video at will
	$vidposttime = strval(time());
	$ret = "<object width=\"200\" height=\"200\" data=\"http://www.youtube.com/v/";
	$ret .= $vid."\"";
	$ret .= " type=\"application/x-shockwave-flash\" id=\"".$vidposttime."_youtube_".$vid."\">\n";
	$ret .= "<param name=\"src\" value=\"http://www.youtube.com/v/".$vid."\" />";
	//Add a little button to the right of the video for expand/collapse
	$ret .="\n</object><img src=\"expcol.png\" onclick=\"expandYTVid(&#34;".$vidposttime."_youtube_".$vid."&#34;)\" />";
	
	return $ret;
}

function processText($toProcess, &$replys)
{
	$res = "";
	
	//Find all the reply's in the post
	preg_match_all('/(?<=>>)[0-9]+/', $toProcess, $reparray, PREG_PATTERN_ORDER);
	
	//Add them to an array, making sure that there are no duplicates
	foreach($reparray[0] as $repentry)
	{
		if(!in_array($repentry, $replys))
		{
			$replys[] = intval($repentry);
		}
	}
	
	//We don't want the user's text to be interpreted as HTML
	$toProcess = str_replace("<", "&lt;", $toProcess);
	$toProcess = str_replace(">", "&gt;", $toProcess);
	
	//Replace the user's replys to other posts with links that can actually do stuff
	$toProcess = preg_replace('/(?<=&gt;&gt;)[0-9]+/', '<span style="color:#0000ff" onclick="gotoPost($0)" onmouseover="hoverPost($0, event)" onmouseout="unhoverPost()">$0</span>', $toProcess);
	
	$textlines = preg_split("/(\r\n|\n|\r)/", $toProcess);
	
	$tllength = count($textlines);
	
	//so oldfags can greentext
	for($x=0;$x<$tllength;$x++)
	{
		if((substr($textlines[$x], 0, 4) == "&gt;") && (substr($textlines[$x], 4, 4) != "&gt;"))
		{
			$res.="<span style=\"color:#78bb22\">";
			$res.=$textlines[$x]."</span><br />";
		} else
		{
			$res.=$textlines[$x]."<br />";
		}
	}
	return $res;
}

function addReplyIfExists($frompostnum, $topostnum)
{
	$GLOBALS['board']->update(array('posts.'.$topostnum => array('$exists' => true)), array('$push' => array('posts.'.$topostnum.'.replysfrom' => $frompostnum)));
}

//Fuck your orderly, auto-incremented shit SQL; I do what I want!
function getPostNumber_Mongo()
{
	$postnumvar = $GLOBALS['board']->findOne(array('postnum' => array('$exists' => true)));
	
	$pnTemp = 0;
	
	if(count($postnumvar) != 0)
	{
		$pnTemp = $postnumvar['postnum'];
		$GLOBALS['board']->update(array('postnum' => $pnTemp), array('$set'=>array('postnum' => ++$pnTemp)));
	} else
	{
		$postnumdata = array('postnum' => 0);
		$GLOBALS['board']->save($postnumdata);
	}
	
	return $pnTemp;
}
?>
