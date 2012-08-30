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
	// manager.php
	// 
	// Purpose: The main entry point of the administrator to manage LexManager
	// Inputs: none
	//
	//////
	
	// Check if user is logged in
	session_start();
	if($_SESSION['LM_login'] !== "1") {
		header("Location: adm_login.php");
	}

	// Import configuration; if it doesn't exist, redirect to the configuration setup page
	if(!file_exists('cfg/lex_config.php')) {
		die("<p class=\"statictext warning\">You are missing a configuration file. You must have a valid configuration file to use LexManager. Go to the <a href=\"adm_setup.php\">Configuration Setup</a> page to create one.</p>");
	} else {
		include('cfg/lex_config.php');
	}

	// Connect to MySQL database
	$dbLink = mysql_connect($LEX_serverName, $LEX_adminUser, $LEX_adminPassword);
    @mysql_select_db($LEX_databaseName) or die("      <p class=\"statictext warning\">Unable to connect to database.</p>\n");
    $charset = mysql_query("SET NAMES utf8");
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
            	<a href="manager.php" class="title">Administration</a><br/>
                <div id="adminnav">
                	<p>• <a href="adm_newlexicon.php">New Lexicon</a></p>
                    <p>• <a href="adm_backup.php">Backup Lexicons</a></p>
                    <p>• <a href="adm_settings.php">Settings</a></p>
                    <p>• <a href="adm_logout.php">Logout</a></p>
                </div>
            </div>
            <div id="main">
	        	<div id="leftbar">
					<?php
						// Retrieve list of available lexicons
                        $queryReply = mysql_query("SELECT `Index_ID`, `Name` FROM `lexinfo` ORDER BY `Name`;");
                        $numTables = @mysql_num_rows($queryReply);
                        $displayBuf = "";

						// Display list of lexicons with links to their individual administration pages
						if(!$numTables) {
							echo("<p>No lexicons found.</p>\n");
						} else {
							for ($i = 0; $i < $numTables; $i++) {
	                            $langID = mysql_result($queryReply, $i, 'Index_ID');
								$langName = mysql_result($queryReply, $i, 'Name');
	                            $displayBuf .= "<p><a href=\"adm_viewlex.php?i=" . $langID . "\" class=\"lexlink\">" . $langName . "</a></p>\n";
							}
							echo($displayBuf);
						}
                    ?>
	            </div>
	            <div id="entryview">
	            	<p class="statictext">Welcome to the LexManager Administration page.</p>
                    <p class="statictext">From here you can control all of the lexicons within LexManager. Select an option from the top right corner to create, import, and export lexicons, or select a specific lexicon in the list to the left to see the options available for that particular language.</p>
                    <p class="statictext">From a particular lexicon's page you can add, edit, and remove entries or modify the structure and appearance of the lexicon as a whole.</p>
                    <?php
						// If no lexicons have yet been created, display a prompt guiding the administrator to the New Lexicon page
						if(!$numTables) {
							$displayBuf = "<p class=\"warning\">It appears you have no lexicons set up. If you would like to set up a new lexicon, please select \"New Lexicon\" above. If you believe this message is in error, check your MySQL and LexManager configurations.</p>";
							
							echo($displayBuf);
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