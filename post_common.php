<?php
function generateImageHTML($image_filename, &$image_binary, $thumbsize, &$postfiles)
{
	$ret = "";
	
	$image = new Imagick();
	$image->readImageBlob($image_binary);
	if(!checkImage($image))
	{
		return "";
	}

	if($image->getImageFormat() == 'GIF')
	{
		$image->writeImages("./images/".$image_filename, true);
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
	
	$ret .= "<a href=\"/images/".$image_filename."\">";
	$ret .= "<img src=\"/images/thumbs/".$image_filename."\" />";
	$ret .= "</a>";
	
	$postfiles[] = "./images/".$image_filename;
	$postfiles[] = "./images/thumbs/".$image_filename;
		
	return $ret;
}

function checkImage(&$image)
{
	try
	{
		$identity= $image->identifyImage();
		$format = $identity['format'];
		return !((substr($format, 0, 3) != 'PNG') && (substr($format, 0, 3) != 'GIF') && (substr($format, 0, 4) != 'JPEG'));
	} catch(ImagickException $iexcp)
	{
		return false;
	}
}

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

function generateAudioHTML($audio_filename, &$audio_data, &$postfiles)
{
	$ret = "";
	
	if(!audioIsOgg($audio_data))
	{
		return "";
	}
	$afp = fopen("./audio/".$audio_filename, 'wb');
	if(!$afp)
	{
		die("Error: Can't save audio file!");
	}
	
	fwrite($afp, $audio_data);
	
	$ret .= "<audio controls preload=\"none\">";
	$ret .= "<source src=\"/audio/".$audio_filename."\" type=\"audio/ogg\">";
	$ret .= "</audio>";
	
	$postfiles[] = "./audio/".$audio_filename;
	
	return $ret;
}

function audioIsOgg(&$audio_data)
{
	return (substr($audio_data, 0, 4) == 'OggS');
}

function generateExternalMediaHTML($url_to_media)
{
	$urldata = parse_URL($url_to_media);
	$ret = "";
	
	switch($urldata['host'])
	{
		case "www.youtube.com":
			if($urldata['path'] == "/watch")
			{
				$queryparts = explode("&", $urldata['query']);
				
				for($x = 0; $x < count($queryparts); $x++)
				{
					if(substr($queryparts[$x], 0, 2) == "v=")
					{
						return generateYoutubeEmbed(substr($queryparts[$x], 2));
					}
				}
			} else
			{
				die("Error:Can't do anything with this youtube path.  ".$urldata['path']);
			}
			break;
		default:
			die("Error:Can't do anything with this URL.  ".$urldata['host']);
	}
}

function generateYoutubeEmbed($vid)
{
	$vidposttime = strval(time());
	$ret = "<object width=\"200\" height=\"200\" data=\"http://www.youtube.com/v/";
	$ret .= $vid."\"";
	$ret .= " type=\"application/x-shockwave-flash\" id=\"".$vidposttime."_youtube_".$vid."\">\n";
	$ret .= "<param name=\"src\" value=\"http://www.youtube.com/v/".$vid."\" />";
	$ret .="\n</object><img src=\"expcol.png\" onclick=\"expandYTVid(&#34;".$vidposttime."_youtube_".$vid."&#34;)\" />";
	
	return $ret;
}

function processText($toProcess, &$replys)
{
	$res = "";
	
	preg_match_all('/(?<=>>)[0-9]+/', $toProcess, $reparray, PREG_PATTERN_ORDER);
	
	foreach($reparray[0] as $repentry)
	{
		if(!in_array($repentry, $replys))
		{
			$replys[] = intval($repentry);
		}
	}
	
	$toProcess = str_replace("<", "&lt;", $toProcess);
	$toProcess = str_replace(">", "&gt;", $toProcess);
	
	$toProcess = preg_replace('/(?<=&gt;&gt;)[0-9]+/', '<span style="color:#0000ff" onclick="gotoPost($0)" onmouseover="hoverPost($0, event)" onmouseout="unhoverPost()">$0</span>', $toProcess);
	
	$textlines = preg_split("/(\r\n|\n|\r)/", $toProcess);
	
	$tllength = count($textlines);
	
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
