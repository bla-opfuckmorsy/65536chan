//Shitty way to determine type of media input
var mediastate = false;

//Initialize the posting process, gets data from any necessary files
function initpost()
{
	document.getElementById("errormsg").innerHTML = "";
	var file = document.getElementById("filename").files[0];
	if(file != null)
	{
		var reader = new FileReader();
		reader.readAsArrayBuffer(file);
		//call dopost when data is loaded
		reader.onload = dopost;
	} else
	{
		//We're not loading any data, so call dopost now
		dopost(null);
	}
}

//Really piss-poor way to encode the binary data, in fact, the data really shouldn't be sent through JSON
//but I'm far too lazy to try to code up something to manage multiple requests per post	
function arrayBuffer2Base64(buffer)
{
	if(buffer == null)
	{
		return "";
	}
	var binary = '';
	var bytes = new Uint8Array(buffer);
	var len = bytes.byteLength;
	
	for(var i = 0; i < len; i++)
	{
		binary += String.fromCharCode(bytes[i]);
	}
	
	return window.btoa(binary);
}

//More shitty code for determining what the user is trying to post
function togglemedia()
{
	if(mediastate)
	{
		mediastate = false;
		document.getElementById("expcol").src = "expand.png";
		document.getElementById("justimage").style.display = "";
		document.getElementById("moremedia").style.display = "none";
	} else
	{
		mediastate = true;
		document.getElementById("expcol").src = "collapse.png";
		document.getElementById("justimage").style.display = "none";
		document.getElementById("moremedia").style.display = "";
	}
}

//Not great, but seemed to be the most reasonable way to expand/shrink an embedded youtube video without sucking Google's cock.
function expandYTVid(vid_element)
{
	var vidpost = document.getElementById(vid_element);
	if(vidpost.width == 200)
	{
		vidpost.width = 600;
		vidpost.height = 400;
	} else
	{
		vidpost.width = 200;
		vidpost.height = 200;
	}
}

//Scroll the page to the specified post, or redirect to the thread containing that post
function gotoPost(postnumber)
{
	var post = document.getElementById("post_"+postnumber);
	if(post)
	{
		post.scrollIntoView();
	} else
	{
		var readystatefunc = function()
		{
			if(xmlhttp.readyState == 4 && xmlhttp.status == 200)
			{
				if(xmlhttp.responseText.substr(0,6) != "Error:")
				{
					var jsonResponse = JSON.parse(xmlhttp.responseText);
					if(jsonResponse.postfound == true)
					{
						window.location = "test_thread.php?threadnum=" + jsonResponse.postthread;
					}
				} else
				{
					//Error message here.
				}
			}
		};
		findPost(postnumber, readystatefunc);
	}
}

//Display a post when the user's mouse hovers over a reply
function hoverPost(postnumber, e)
{
	var post = document.getElementById("post_"+postnumber);
	var phc = document.getElementById("posthovercontent");
	
	if(post)
	{
		var phc = document.getElementById("posthovercontent");
		
		phc.style.display = "block";
		phc.style.backgroundColor = "#808090";
		phc.style.border = "2px solid black";
		phc.innerHTML = post.innerHTML;
		phc.style.left = e.clientX + document.body.scrollLeft + 15 + "px";
		phc.style.top = e.clientY + document.body.scrollTop - 10 + "px";
	} else
	{
		//Send the server a request for the post's data if it isn't in this thread
		var readystatefunc = function()
		{
			if(xmlhttp.readyState == 4 && xmlhttp.status == 200)
			{
				if(xmlhttp.responseText.substr(0,6) != "Error:")
				{
					var jsonResponse = JSON.parse(xmlhttp.responseText);
					if(jsonResponse.postfound == true)
					{
						var phc = document.getElementById("posthovercontent");
			
						phc.style.display = "block";
						phc.style.backgroundColor = "";
						phc.style.border = "";
						phc.innerHTML = decodePost(jsonResponse.postData, false);
						phc.style.left = e.clientX + document.body.scrollLeft + 15 + "px";
						phc.style.top = e.clientY + document.body.scrollTop - 10 + "px";
					}
				} else
				{
					//Error message here.
				}
			}
		};
		findPost(postnumber, readystatefunc);
	}
}

//Generate and send a request for a specified post's data
function findPost(postnumber, readystatefunc)
{
	var jsonResponse;
	if(window.XMLHttpRequest)
	{
		xmlhttp = new XMLHttpRequest();
	} else
	{
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange = readystatefunc;
	
	xmlhttp.open("POST", "locatepost.php", true);
	xmlhttp.setRequestHeader("Content-Type", "application/json; charset=UTF-8");
	
	xmlhttp.send(JSON.stringify({postnum:postnumber}));
}

//makes sure the post preview fucks off when you're done with it
function unhoverPost()
{
	var phc = document.getElementById("posthovercontent");
	phc.innerHTML = "";
	phc.style.display = "none";
}
