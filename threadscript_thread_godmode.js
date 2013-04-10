//Used for getting updates, so the server only has to serve out posts past this one
var latest_post = 0;

//do the actual query that creates the post (initpost() prepares for this)
function dopost(event)
{
	var filedata  = null;
	var filename = "";
	
	//Check if we have a file
	if(event != null)
	{
		//if so, get its filename and data
		filename = document.getElementById("filename").files[0].name;
		filedata = event.target.result;
	}
	
	if(window.XMLHttpRequest)
	{
		xmlhttp = new XMLHttpRequest();
	} else
	{
		//Microsoft likes to do things special, because fuck everybody who has ever written a single line of javascript
		//Actually, this imageboard probably wouldn't work under IE anyways with all the other shit in it...
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange = function()
	{
		//Arr ya ready kids?
		if(xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			//Aye Aye Captain!
			
			//"Error: " Usually means the user tried to fuck the server over, but sometimes it's useful for debugging
			if(xmlhttp.responseText.substr(0,6) != "Error:")
			{
				//Add the post to the page
				document.getElementById("postarea").innerHTML += decodePost(JSON.parse(xmlhttp.responseText));
			} else
			{
				//Let the user know what happened, in bright red letters
				document.getElementById("errormsg").innerHTML = "<span style=\"color:#bb0000\">"+xmlhttp.responseText.substr(6)+"</span>";
			}
		}
	}
	
	//From the form-like thing
	var name = document.getElementById("postnamebox").value;
	var text = document.getElementById("posttextbox").value;
	
	var mediatype;
	var mediadata;
	
	//really shitty way to handle user media input
	if(mediastate)
	{
		mediatype = "ExternalDataAtURL";
		mediadata = document.getElementById("URLinput").value;
	} else
	{
		mediatype = "file";
		//It fucking amazes me how fast most browsers can encode >1MB of raw binary data...
		document.getElementById("errormsg").innerHTML = "Base64 Encoding data...";
		mediadata = arrayBuffer2Base64(filedata);
		document.getElementById("errormsg").innerHTML = "Sending Request...";
	}
	
	xmlhttp.open("POST", "post.php", true);
	xmlhttp.setRequestHeader("Content-Type", "application/json; charset=UTF-8");
	
	//Build the post request and send it
	xmlhttp.send(JSON.stringify({threadnum:threadnumber,postname:name, posttext:text, mtype:mediatype, fname:filename, mdata:mediadata}));
	
	//clear out the form fields
	document.getElementById("posttextbox").value = "";
	document.getElementById("errormsg").innerHTML = "";
	
	//Hack for clearing out a file input
	var oldF_in = document.getElementById("filename");
	var newF_in = document.createElement("input");
	newF_in.type = "file";
	newF_in.id = oldF_in.id;
	oldF_in.parentNode.replaceChild(newF_in, oldF_in);
}

//long-assed function for translating post JSON to html code
function decodePost(jsonObj,setID)
{
	//setID is used for giving the post's containing div an id attribute so it can be located easily later
	setID = (setID === undefined) ? true : setID;
	
	var date = new Date(parseInt(jsonObj.posttime) * 1000);
	
	var htmlstring = "";
	
	var op = false;
	var topic = "";
	var specialjson;
	
	//Determine if the post is OP
	if(jsonObj.special != "")
	{
		specialjson = JSON.parse(jsonObj.special);
		if(specialjson.OP_Post != undefined)
		{
			op = specialjson.OP_Post;
			topic = specialjson.topic;
		}
	}
	
	//This just looks ugly, but I'm not so sure there's a better way to do it...
	if(!op)
	{
		htmlstring += "<div class=\"poststyle\" ";
		//gives the post an ID
		if(setID)
		{
			htmlstring += "id=\"post_"+jsonObj.postnum+"\"";
		}
		htmlstring += ">";
		htmlstring += "<span style=\"color:#007700\">";
		htmlstring += jsonObj.postname + "</span>  ";
		htmlstring += date.toLocaleString();
		htmlstring += "  No.<span id=\"pnspan_"+jsonObj.postnum+"\">";
		htmlstring += jsonObj.postnum;
		htmlstring += "</span>";
		htmlstring += genEditPanel(jsonObj.postnum, false);
		for(reply in jsonObj.replysfrom)
		{
			htmlstring += " <span style=\"font-size:small;color:#0000ff;\" onclick=\"gotoPost("+jsonObj.replysfrom[reply]+")\" onmouseover=\"hoverPost("+jsonObj.replysfrom[reply]+", event)\" onmouseout=\"unhoverPost()\">&gt;&gt;"+jsonObj.replysfrom[reply]+"</span>";
		}
		htmlstring += "<hr /><div>";
		htmlstring += "<div style=\"float:left;padding-right:15px;\">";
		htmlstring += jsonObj.mediahtml;
		htmlstring += "</div>";
		htmlstring += "<span>";
		htmlstring += jsonObj.posttexthtml;
		htmlstring += "</span></div>";
		htmlstring += "</div>";
		htmlstring += "<br />";
	}else
	{
		htmlstring += "<div>";
		htmlstring += "<div style=\"float:left;padding-right:15px;\">";
		htmlstring += jsonObj.mediahtml;
		htmlstring += "</div>";
		htmlstring += topic;
		htmlstring += "  <span style=\"color:#007700\">";
		htmlstring += jsonObj.postname + "</span>  ";
		htmlstring += date.toLocaleString();
		htmlstring += "  No.<span id=\"pnspan_"+jsonObj.postnum+"\">";
		htmlstring += jsonObj.postnum;
		htmlstring += "</span>";
		htmlstring += genEditPanel(jsonObj.postnum, true);
		for(reply in jsonObj.replysfrom)
		{
			htmlstring += " <span style=\"font-size:small;color:#0000ff;\" onclick=\"gotoPost("+jsonObj.replysfrom[reply]+")\" onmouseover=\"hoverPost("+jsonObj.replysfrom[reply]+", event)\" onmouseout=\"unhoverPost()\">&gt;&gt;"+jsonObj.replysfrom[reply]+"</span>";
		}
		htmlstring += "<br /><span>";
		htmlstring += jsonObj.posttexthtml;
		htmlstring += "</span></div>";
		htmlstring += "<br />";
	}
	
	//update latest_post
	if(jsonObj.postnum > latest_post)
	{
		latest_post = jsonObj.postnum;
	}
	
	return htmlstring;
}

function genEditPanel(postnum, isOP)
{
	var ret = "  <span style=\"border:2px solid red\" >";
	if(isOP)
	{
		ret += "<img src=\"Placeholder.png\" onclick=\"deleteThreadRequest("+postnum+")\" />";
	}
	ret += "</span>";
	
	return ret;
}

function updatePosts()
{
	var jsonResponses;
	if(window.XMLHttpRequest)
	{
		xmlhttp = new XMLHttpRequest();
	} else
	{
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange = function()
	{
		if(xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			if(xmlhttp.responseText.substr(0,6) != "Error:")
			{
				jsonResponses = JSON.parse(xmlhttp.responseText);
				
				jsonResponses.sort(function(a,b)
				{
					return a.postnum > b.postnum ? 1 : -1;
				});
				
				for(var i = 0; i < jsonResponses.length; ++i)
				{
					document.getElementById("postarea").innerHTML += decodePost(jsonResponses[i]);
				}
				
			} else
			{
				//Error message here.
			}
		}
	}
	
	xmlhttp.open("POST", "updateposts.php", true);
	xmlhttp.setRequestHeader("Content-Type", "application/json; charset=UTF-8");
	
	//Generate the request JSON and send it
	xmlhttp.send(JSON.stringify({threadnum:threadnumber, postnum:latest_post}));
	
	return false;
}

