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
	// adm_setup.php
	// 
	// Purpose: A one-time initial configuration to set up LexManager in a new environment
	// Inputs: 
	//     multiple (POST, optional): the submitted data for creating a new configuration file
	//
	//////

	// If a configuration file already exists, do not continue
	if(file_exists('cfg/lex_config.php')) {
		die('<p>You already have a configuration file! If you want to change anything, go back and visit the Settings page.</p>');
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
        <script type="text/javascript" src="res/lex.js"></script>
    </head>
    <body>
    	<div id="content">
        	<div id="topbar">
            	<a href="manager.php" class="title">LexManager Setup</a><br/>
            </div>
            <div id="main">
	        	<div id="leftbar">

	            </div>
	            <div id="entryview">
                	<?php
						// If data was submitted via POST, create a configuration file
	           			if(isset($_POST['submit'])) {
							// Retrieve variables
							$servername = $_POST['servername'];
							$lexDatabase = mysql_real_escape_string($_POST['lex_database']);
							$admin_user = $_POST['admin_user'];
							$admin_pass = ($_POST['admin_pass'] == $_POST['admin_pass2']) ? $_POST['admin_pass'] : '';
							$public_user = $_POST['public_user'];
							$public_pass = ($_POST['public_pass'] == $_POST['public_pass2']) ? $_POST['public_pass'] : '';
							$lm_username = $_POST['lm_username'];
							$lm_password = ($_POST['lm_password'] == $_POST['lm_password2']) ? $_POST['lm_password'] : '';
							
							if (!$admin_pass || !$public_pass || !$lm_password) {
								// Check to make sure passwords match
								echo('<p class="statictext warning">One set of passwords do not match. Go back and recheck.</p>');
							} else {
								// Create a string containing the contents of the new configuration file
								$configContents = "<?php\n\n\$LEX_adminUser = \"" . $admin_user . "\";\n\$LEX_adminPassword = \"" . $admin_pass . "\";\n\$LEX_publicUser = \"" . $public_user . "\";\n\$LEX_publicPassword = \"" . $public_pass . "\";\n\n\$LEX_serverName = \"" . $servername . "\";\n\$LEX_databaseName = \"" . $lexDatabase . "\";\n\n?>\n";
								
								// Open an actual file for writing, or an error if a stream could not be created
								$configFileHandle = @fopen('cfg/lex_config.php', 'w') or die('<p>Can\'t open file for writing. Check permissions.</p>');
								fwrite($configFileHandle, $configContents);
								fclose($configFileHandle);
								
								// Open a MySQL connection
								$dbLink = mysql_connect($servername, $admin_user, $admin_pass);
								
								// Create the LexManager database
								mysql_query("CREATE database `" . $lexDatabase . "`;");
								echo('<p>The configuration file has been created. You are now ready to create a new lexicon.</p><p><a href="manager.php">Return to LexManager Administration</a></p>');
								
								// Create a user table and add the administrator account and encrypted password
								// The password is simply encrypted using MD5; this is not especially secure, but is more than suitable for the purposes of LexManager
								@mysql_select_db($lexDatabase);
								$charset = mysql_query("SET NAMES utf8");
								mysql_query("CREATE TABLE `lex_userinfo` (`Index_ID` int(1) NOT NULL AUTO_INCREMENT, `Name` varchar(255) NOT NULL, `Password` varchar(255) NOT NULL, PRIMARY KEY (`Index_ID`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
								mysql_query("INSERT INTO `lex_userinfo` (`Name`, `Password`) VALUES ('" . $lm_username . "', '" . md5($lm_password) . "');");
								
								// Start a new session so the user isn't immediately forced to login
								session_start();
								$_SESSION['LM_login'] = "1";
							}
							
						} else {
							// If no data was submitted, output the appropriate setup form
                			echo('
								<p class="statictext">Welcome to LexManager!</p>
								<p class="statictext">Just fill out the following information and a LexManager configuration file will be created for you. Then you\'ll be able to get started using LexManager right away!</p>
								<form id="config_form" action="adm_setup.php" method="post">
									<fieldset>
										<legend>Database Information</legend>
										<p>This information concerns the MySQL instance that LexManager will use to store information. 
										<p>In the following field, enter the name of the server hosting MySQL. If you do not know, contact your hosting provider. If you are running LexManager on your own machine, try using \'localhost\'.</p>
										<table>
											<tr>
												<td><label for="servername">Server Name:</label></td>
												<td><input type="text" name="servername" size="50"></td>
											</tr>
										</table>
										<p>Below, enter what you would like the LexManager database to be called. \'Lexicons\' is the default name, but you may change this if you already have a database with that name or just would prefer to use something else.</p>
										<table>
											<tr>
												<td><label for="lex_database">LexManager Database:</label></td>
												<td><input type="text" name="lex_database" size="50" value="lexicons"></td>
											</tr>
										</table>
									</fieldset>
									<fieldset>
										<legend>MySQL Administrator Information</legend>
										<p>Enter the login information for the MySQL account you will be using as administrator. This is the account that the LexManager administration pages will use to create new lexicons, add entries, etc.</p>
										<table>
											<tr>
												<td><label for="admin_user">Administrator Username:</label></td>
												<td><input type="text" name="admin_user" size="50"></td>
											</tr>
											<tr>
												<td><label for="admin_pass">Administrator Password:</label></td>
												<td><input type="password" name="admin_pass" size="50"></td>
											</tr>
											<tr>
												<td><label for="admin_pass2">Re-Enter Password:</label></td>
												<td><input type="password" name="admin_pass2" size="50"></td>
											</tr>
										</table>
									</fieldset>
									<fieldset>
										<legend>MySQL Anonymous User Access</legend>
										<p>Enter the login information for the MySQL account that will be used by anonymous users (the Internet as a whole) to access the public-facing portions of LexManager. This account is needed for other people to view your lexicons. While you may use the same credentials as in the previous section, this is <strong>strongly</strong> discouraged.</p>
										<table>
											<tr>
												<td><label for="admin_user">Anonymous Username:</label></td>
												<td><input type="text" name="public_user" size="50"></td>
											</tr>
											<tr>
												<td><label for="admin_pass">Anonymous Password:</label></td>
												<td><input type="password" name="public_pass" size="50"></td>
											</tr>
											<tr>
												<td><label for="admin_pass2">Re-Enter Password:</label></td>
												<td><input type="password" name="public_pass2" size="50"></td>
											</tr>
										</table>
									</fieldset>
									<fieldset>
										<legend>LexManager Administrator Access</legend>
										<p>To keep anyone from accessing the LexManager administration pages (where you are now), you need to set up a personal LexManager account. This username and password will be used to log-in and let you make changes.</p>
										<table>
											<tr>
												<td><label for="lm_username">Username:</label></td>
												<td><input type="text" name="lm_username" size="50"></td>
											</tr>
											<tr>
												<td><label for="lm_password">Password:</label></td>
												<td><input type="password" name="lm_password" size="50"></td>
											</tr>
											<tr>
												<td><label for="lm_password2">Re-Enter Password:</label></td>
												<td><input type="password" name="lm_password2" size="50"></td>
											</tr>
										</table>
									</fieldset>
									<p>Clicking \'Create\' will generate a configuration file and build the necessary databases.</p>
									<input type="submit" name="submit" value="Create">
								</form>
							');
						}
                    ?>


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