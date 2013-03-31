<?php
//strips data the script doesn't need and data the user shouldn't see from the post
function stripUnused(&$post)
{
	unset($post['files']);
}
?>
