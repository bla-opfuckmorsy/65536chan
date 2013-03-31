<html>
	<head>
		<meta charset=utf-8>
		<title>test</title>
		<style>
			.poststyle{
				display:inline-block;
				background-color: #808090;
				border: 2px solid black;
				padding: 5px;
				margin-bottom:5px;
			}
			
		</style>
		<?php
			echo "<script> var threadnumber = ".$_REQUEST['threadnum'].";</script>";
		?>
		<script src="threadscript_common.js"></script>
		<script src="threadscript_thread.js"></script>
	</head>
	<body bgcolor="#808080" onload="updatePosts()">
		[b / <a href="test.php">test</a> ]
		<hr />
		<div style="margin-left:auto;margin-right:auto;width:302px;display:table;">
			<img src="chanban.png" />
		</div>
		<h1 style="text-align:center">/test/ - Procedurally breaking shit</h1>
		<div style="margin-left:auto;margin-right:auto;width:60%;display:table;">
			<div>
				<div style="display:table-row">
					<div style="display:table-cell;vertical-align:middle;">Name</div>
					<div style="display:table-cell">
						<input type="text" cols="60" id="postnamebox">
					</div>
				</div>
				<div style="display:table-row">
					<div style="display:table-cell;vertical-align:middle;">Post Text:</div>
					<div style="display:table-cell">
						<textarea rows="6" cols = "60" wrap="hard" spellcheck="true" id="posttextbox"></textarea>
					</div>
				</div>
			</div>
			<div style="display:table-row">
				<div style="display:table-cell;float:left">
					<img src="expand.png" id="expcol" onclick="togglemedia()" />
				</div>
				<div style="display:table-cell;float:left">
					<div id="justimage">
						<input type="file" value="File" id="filename">
					</div>
					<div id="moremedia" style="display:none">
						<input type="text" id="URLinput">
					</div>
				</div>
			</div>
			<div style="display:table-row">
				<div style="display:table-cell">
					<input type="button" value="post" onclick="initpost()">
				</div>
			</div>
			<div style="display:table-row;" id="errormsg"></div>
		</div>
		<hr style="height:5px" />
		<div id="postarea"></div>
		<div style="clear:both;">
			<input type="button" value="Update" onclick="updatePosts()">
		</div>
		<div id="posthovercontent" style="position:absolute;display:none;z-index:9001;background-color:#808090;border:2px solid black;"></div>
	</body>
</html>
