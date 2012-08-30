<?php
/*
+-----------------------------------------------------------------------------------------------+
| LexManager, Copyright ©2011 Martin Posthumus                                                  |
|                                                                                               |
| This file is part of LexManager, a free and open-source web-based dictionary managament tool. |
| You may redistribute and/or modify LexManager under the terms of the GNU General Public       |
| License (GPL) as published by the Free Software Foundation, either version 3 of the license   |
| or any later version. For the full text of the GPL3 license, please see                       |
| < http://www.gnu.org/licenses/ >.                                                             |
|                                                                                               |
| LexManager is distributed in the hope that it or some part of it will be useful, but comes    |
| with no warranty for loss of data, as per the GPL3 license.                                   |
+-----------------------------------------------------------------------------------------------+
*/

	//////
	// adm_login.php
	// 
	// Purpose: Present a login form to protect the administration pages from general access
	// Inputs:
	//     'username' (POST, optional): the username to be validated
	//     'password' (POST, optional): the password to be validated
	//
	//////

	// Import configuration
	if(!file_exists('cfg/lex_config.php')) {
		die("<p class=\"statictext warning\">You are missing a configuration file. You must have a valid configuration file to use LexManager. Go to the <a href=\"adm_setup.php\">Configuration Setup</a> page to create one.</p>");
	} else {
		include('cfg/lex_config.php');
	}
	
	// Connect to MySQL database
    $dbLink = mysql_connect($LEX_serverName, $LEX_adminUser, $LEX_adminPassword);
    @mysql_select_db($LEX_databaseName) or die("      <p class=\"statictext warning\">Unable to connect to database.</p>\n");
    $charset = mysql_query("SET NAMES utf8");

	// If data was submitted via POST, validate it
	if(isset($_POST['submit'])) {
		$user = mysql_real_escape_string($_POST['username']);
		$pass = md5($_POST['password']);
		$queryReply = mysql_query("SELECT `password` FROM `lex_userinfo` WHERE `Name`='" . $user . "';");
		
		// If signin invalid, set a flag; if valid, create a new session and set a cookie
		if(mysql_num_rows($queryReply) == 0 || mysql_result($queryReply, 0, 'Password') !== $pass) {
			$loginFailed = TRUE;
		} else {
			session_start();
			$_SESSION['LM_login'] = "1";
			header('Location: manager.php');
		}
	}
?>
<!DOCTYPE HTML>
<html>
	<head>
    	<title>LexManager Administration</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="res/lex_core.css">
        <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="res/favicon.ico">
        <link rel="apple-touch-icon" href="res/apple-touch-icon.png">
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
        <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
        <script type="text/javascript" src="res/lex.js"></script>
        <script type="text/javascript" src="res/admin.js"></script>
    </head>
    <body>
    	<div id="content">
        	<div id="topbar">
            	<a href="manager.php" class="title">Administration</a><br/>
            </div>
            <div id="main">
	        	<div id="leftbar">
	            </div>
	            <div id="entryview">
                	<?php
						// Show error message if login failed
						if(@$loginFailed) {
							echo("<p class=\"statictext warning\">Error: Incorrect username or password.</p>\n");
						}
					?>
                	<p>To continue, you need to sign in.</p>
                    <form id="login" action="adm_login.php" method="post">
                    	<fieldset>
                        	<legend>Sign In</legend>
                            <table>
                            	<tr>
                                	<td><label for="username">Username:</label></td>
                                    <td><input type="text" name="username" size="50"></td>
                                </tr>
                                <tr>
                                	<td><label for="password">Password:</label></td>
                                    <td><input type="password" name="password" size="50"></td>
                                </tr>
                            </table>
                            <input type="submit" name="submit" value="Sign In">
                        </fieldset>
                    </form>
                    <noscript>
                    	<p class="statictext warning">This page requires that JavaScript be enabled.</p>
                    </noscript>
                    <br/><br/>
	            </div>
            </div>
        </div>
    </body>
</html>

<?php
	// Close database connection
	@mysql_close($dbLink);
?>