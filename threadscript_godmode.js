function godRequest(jsondata, godcallback)
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
			godcallback(xmlhttp.responseText);
		}
	}
	
	xmlhttp.open("POST", "godmode_stuff.php", true);
	xmlhttp.setRequestHeader("Content-Type", "application/json; charset=UTF-8");
	
	//xmlhttp.send(JSON.stringify({threadnum:0, postnum:0}));
	xmlhttp.send(jsondata);
}

function deleteThreadRequest(postnum)
{
	var cb = function(respString){};
	var jsonData = JSON.stringify({func:"delete_thread", data:postnum});
	
	godRequest(jsonData, cb);
}
