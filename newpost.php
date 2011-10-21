<?php
/* 
 * New Post page for our basic internet forum (newpost.php)
 * Code by Jared Clason 4/17/09 for Internet Systems Programming
 * Edit functionality added 4/20/09 by Jared Clason
 *
 *  Valid Parameters from outside pages
 * 
 *   "type"	 The only valid value for type is 'edit', used for editing a post/thread.
 *		 If type is passed, postid must also be passed to locate the post to edit.
 *
 *   "postid"     The nine digit postid code that uniquely indentifies a post
 *		  Should only be passed when using the 'edit' value for type
 *
 *   "threadid"    The 4 digit id that uniquely identifies a thread
 *		 When used alone, displays a new reply page for the matching thread.
 *
 *   
 */
	  # ob_start() and ob_end_flush() allow the header($url) function 
	  # to redirect to a url at any point in between

	  ob_start();

	  $dbuser = "jmc151_isp";
	  $dbpass = "isp2009";
	  $database = "jmc151_isp";

	  mysql_connect("db1.cs.uakron.edu",$dbuser,$dbpass);
	  @mysql_select_db($database) or die( "Unable to select database");
	  
	  # Get the query string variables and the user information

	  if($_SESSION[useremail])
	  {
		$useremail = $_SESSION[useremail];
		$username = $_SESSION[username];
	  }
	  else
	  {
		#user is not logged in, redirect?
		$useremail = "xiao@uakron.edu";
		$username = "Dr. Xiao";
		##$url = "Location: login.php?return='newpost.php?threadid=" . $threadid . "'";
		##header($url);
	  }

	  $postid = $_GET["postid"];
	  if(!$postid)
	  {
		  $postid = $_POST["postid"];
	  }
 	
	  $threadid = $_GET["threadid"];
	  if(!$threadid)
	  {
	  	$threadid = $_POST["threadid"];
	  }

	  $threadtitle = $_POST["threadtitle"];
	  if(!$threadtitle)
	  {
		$threadtitle = "";
	  }

	  $postbody = $_POST["postbody"];

	  $type = $_POST["type"];
	  if(!$type)
	  {
		 $type = $_GET["type"];
	  }

	  
	  # Check if a post is being submitted
	  if($postbody)
	  {

		if($type == 'thread')
		{
			# This is a new thread

			# Get the threadid by counting the number of threads from the DB and adding it to our starting id

			$query = "select COUNT(*) FROM Threads";
			$result = mysql_query($query);
			$threadnum = mysql_result($result, 0, "COUNT(*)") + 1;
			$threadid = $threadnum + 1001;

			#first post in a thread is always 00001

			$postnum = "00001";
			$postid = $threadid . $postnum;

			$query = "INSERT INTO Threads values ('$threadid', '$threadtitle', '$useremail')";
			mysql_query($query);
			$query = "INSERT INTO Posts values ('$postid', NOW(), '$postbody')";
			mysql_query($query);
			$query = "INSERT INTO Thread_posts values ('$threadid', '$postnum', '$postid')";
			mysql_query($query);
			$query = "INSERT INTO User_posts values ('$useremail', '$postid')";
			mysql_query($query);
		}
		else
		{
			if($type == 'edit')
			{
				# This is an update to a post
				# Insert the new values for title and content. 
				# Get the date and append an 'edited' by message to the post.
				$date = date(DATE_RSS);

				# Take just the threadid out of the postid value: (threadid . postnum)

				$threadid = substr($postid, 0, 4);
				$postbody = $postbody . "\n $username edited this post at $date";

				$query = "UPDATE Threads SET title='" . $threadtitle . "' WHERE threadid='" . $threadid . "'";
				mysql_query($query);
				$query = "UPDATE Posts SET content='" . $postbody . "' WHERE postid='" . $postid . "'";
				mysql_query($query);
			}
			else
			{
				#this is a new post

				# Get the postnum
				$query = "select COUNT(*) from Thread_posts where threadid='$threadid'";
				$result = mysql_query($query);
				$numposts = mysql_result($result, 0, "COUNT(*)");

				#Pad our post number with 0's to fit the 5 digit format
				$postnum = str_pad($numposts, 5 - strlen($postnum), '0', STR_PAD_LEFT);
				$postid = $threadid . $postnum;
			

				$query = "insert into Posts values ('$postid', NOW(), '$postbody')";
				mysql_query($query);
				$query = "insert into Thread_posts values ('$threadid', '$postnum', '$postid')";
				mysql_query($query);
				$query = "insert into User_posts values ('$useremail', '$postid')";
				mysql_query($query);
			}
		}

		#This is where we'll redirect to the thread after entering the post to the database

		$url = "Location: thread_test_live.php?threadid=" . $threadid;
		header($url);

		
	  }

	  if($type == 'edit')
	  {
		  # Pull the current post content from the DB so we can modify it

		  $query = "select content from Posts where postid='$postid'";
		  $result = mysql_query($query);
		  $postbody = mysql_result($result, 0, "content");
		  $threadid = substr($postid, 0, 4);
	  }
	  ob_end_flush();
?>
<!?xml version = "1.0" encoding = "utf-8" ?>
<!DOCTYPE html PUBLIC "-//w3c//DTD XHTML 1.1 //EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns = "http://www.w3.org/1999/xhtml">
  <head>
    <title> 
<?php
	# If this is reply or edit
	if($threadid)
	{
		# Change the title based on thread information

		$query = "SELECT * FROM Threads WHERE threadid='$threadid'";
		$result = mysql_query($query);

		$threadname = mysql_result($result, 0, "title");
		$reply = "Reply to: ";
		if($type == 'edit')
		{
			$reply = "Edit post in: ";
		}
		print($reply . $threadname);
	}
	else print("New Thread Page");
?>
 </title>
  </head>
  <body>
    <div align="center" style='font-size:10pt'>
      <p>
        
      </p> 

     <form action="newpost.php" method="post">

      <table border='2' cellpadding='3' style='background:#ddddbb'>
        <tr>
          <th style="width:150px">
		
<?php

	if(!$threadid)
	{	
		# If we don't have a thread id to go on, this is a new thread, show a title box

		print("New Thread Title: </th> <th> <input type='text' size='40' name='threadtitle' />");
		print("<input type='hidden' name='type' value='thread' />");
	}
	else 
	{
		$whatpostnum = substr($postid, 4, strlen($postid));

		if($whatpostnum == "00001")
		{
			# We're editing a thread, because the first post (00001) was requested, show a current title box
			
			print("Edit Thread Title: </th> <th> <input type='text' size='40' name='threadtitle' value='$threadname' />");
		}
		else
		{
			# Posting or Editing a reply, show the thread name
			# Add some hidden values to our form so we know what to do on submit

			$new = "New Reply";

			if($type == 'edit')
				$new = "Edit";
			print("$new to thread</th> <th> <input type='hidden' name='type' value='post'/>
			<input type='hidden' name='threadid' value=$threadid />
			<input type='hidden' name='threadtitle' value=$threadname />");
			print("$new to:<span style='font-size:12pt'> $threadname</span>");
		}
        }
?>
	 </th>
	</tr>
	<tr>

	   <td style="text-align:right;"> Username: &nbsp </td>
	   <td> &nbsp &nbsp <?php print($username); ?> </td>

	</tr>
	<tr>

	   <td><p>Please enter your message in the box provided. <br/>
		Messages may be no longer than 300 characters <br/>
		and thread titles no longer than 40.</p></td>
	   <td style="text-align:center;"> 
<?php 
	if($type == 'edit') 
	{ 
		# If we're editing, have our form submit the 'edit' type parameter and the postid
		# Also create the text area containing the current post content

		print("<textarea name='postbody' rows='15' cols='50'>" . $postbody);
		print("</textarea><input type='hidden' name='postid' value='$postid' /><input type='hidden' name='type' value='edit' />"); 
	}
	else
	{
		# We're making a new post/thread, show a blank text area. 

		print("<textarea name='postbody' rows='15' cols='50'>");
		print("</textarea>");
	}
	
?>
	   </td>
	</tr>

	<tr>

	  <td>
		<input type="reset" value="Cancel"/>
	  </td>

	  <td style="text-align:right;">
		<input type="submit" value="Create Post"/>
	  </td>

	</tr>

      </table>
    </form>
    </div>
  </body>
</html>