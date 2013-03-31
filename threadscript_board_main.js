
function dopost(event)
{
	var filedata  = null;
	var filename = "";
	if(event != null)
	{
		filename = document.getElementById("filename").files[0].name;
		filedata = event.target.result;
	}
	
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
				window.location = "test_thread.php?threadnum=" + xmlhttp.responseText;
			} else
			{
				document.getElementById("errormsg").innerHTML = "<span style=\"color:#bb0000\">"+xmlhttp.responseText.substr(6)+"</span>";
			}
		}
	}
	
	var topic = document.getElementById("threadtopic").value;
	var name = document.getElementById("postnamebox").value;
	var text = document.getElementById("posttextbox").value;
	
	var mediatype;
	var mediadata;
	
	if(mediastate)
	{
		mediatype = "ExternalDataAtURL";
		mediadata = document.getElementById("URLinput").value;
	} else
	{
		mediatype = "file";
		document.getElementById("errormsg").innerHTML = "Base64 Encoding data...";
		mediadata = arrayBuffer2Base64(filedata);
		document.getElementById("errormsg").innerHTML = "Done. Sending Request...";
	}
	
	xmlhttp.open("POST", "OP_post.php", true);
	xmlhttp.setRequestHeader("Content-Type", "application/json; charset=UTF-8");
	
	xmlhttp.send(JSON.stringify({threadtopic:topic, postname:name, posttext:text, mtype:mediatype, fname:filename, mdata:mediadata}));
	
	document.getElementById("posttextbox").value = "";
	document.getElementById("errormsg").value = "";
	
	var oldF_in = document.getElementById("filename");
	var newF_in = document.createElement("input");
	
	newF_in.type = "file";
	newF_in.id = oldF_in.id;
	
	oldF_in.parentNode.replaceChild(newF_in, oldF_in);
}

function decodePost(jsonObj, threadnum, num, setID)
{
	setID = (setID === undefined) ? true : setID;
	
	var date = new Date(parseInt(jsonObj.posttime) * 1000);
	
	var htmlstring = "";
	
	var op = false;
	var topic = "";
	var specialjson;
	
	if(jsonObj.special != "")
	{
		specialjson = JSON.parse(jsonObj.special);
		if(specialjson.OP_Post != undefined)
		{
			op = specialjson.OP_Post;
			topic = specialjson.topic;
		}
	}
	
	if(!op)
	{
		htmlstring += "<div class=\"poststyle\" ";
		if(setID)
		{
			htmlstring += "id=\"post_"+jsonObj.postnum+"\"";
		}
		htmlstring += ">";
		htmlstring += "<span style=\"color:#007700\">";
		htmlstring += jsonObj.postname + "</span>  ";
		htmlstring += date.toLocaleString();
		htmlstring += "  No.<span id=\"pnspan_"+jsonObj.postnum+"\" <a href=\"test_thread.php?threadnum=" + threadnum + "\">";
		htmlstring += jsonObj.postnum;
		htmlstring += "</a></span>";
		htmlstring += "<hr />";
		htmlstring += "<div>";
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
		htmlstring += "  No.<a href=\"test_thread.php?threadnum=" + threadnum + "\">";
		htmlstring += jsonObj.postnum;
		htmlstring += "</a><br />";
		htmlstring += "<span>";
		htmlstring += jsonObj.posttexthtml;
		htmlstring += "</span></div>";
		htmlstring += "<br />";
	}
	
	return htmlstring;
}

function updatePosts()
{
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
				document.getElementById("postarea").innerHTML = decodeThread(xmlhttp.responseText);
			} else
			{
				document.getElementById("postarea").innerHTML = "An error has occured." + xmlhttp.responseText;
			}
		}
	}
	
	xmlhttp.open("POST", "thread_snippets.php", true);
	xmlhttp.setRequestHeader("Content-Type", "application/json; charset=UTF-8");
	
	xmlhttp.send(JSON.stringify({threadnum:0, postnum:0}));
}

function decodeThread(threadjson)
{
	var jsonResponses = JSON.parse(threadjson);
	var threaddata;
	var threadposts;
	
	var ret = "";

	for(var i = 0; i < jsonResponses.length; ++i)
	{
		ret += "<div style=\"clear:both\"><br /><hr />";
		threaddata = jsonResponses[i];
		threadposts = threaddata.threadposts;
		
		threadposts.sort(function(a,b)
		{
			return a.postnum > b.postnum ? 1 : -1;
		});
			
		for(var k = 0; k < threadposts.length; ++k)
		{
			ret += decodePost(threadposts[k], threaddata.threadnumber, k);
		}
		
		ret += "</div>";
	}
	
	return ret;
}
