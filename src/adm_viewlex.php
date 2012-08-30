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
	// adm_viewlex.php
	// 
	// Purpose: The main administration page for a lexicon, showing basic information
	// Inputs: 
	//     'i' (GET, mandatory): the index of the lexicon in the "lexinfo" table
	//
	//////
	
	// Check if user is logged in
	session_start();
	if($_SESSION['LM_login'] !== "1") {
		header("Location: adm_login.php");
	}

	// Ensure mandatory GET inputs are set, else end execution
	if(isset($_GET['i'])) {
		$lexIndex = $_GET['i'];		   
	} else {
		die('<p class=\"statictext warning\">Error: No index provided.</p>');
	}

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
                <table>
                	<tr>
                    	<?php
							// Output navigation such that it is aware of the current lexicon
							$displayBuf = "<td><a href=\"adm_lex_viewall.php?i=" . $lexIndex . "\" class=\"lexlink\">View All Entries</a></td>\n";
                        	$displayBuf .= "<td><a href=\"adm_lex_newentry.php?i=" . $lexIndex . "\" class=\"lexlink\">Add New Entry</a></td>\n";
                        	$displayBuf .= "<td><a href=\"adm_lex_lexsettings.php?i=" . $lexIndex . "\" class=\"lexlink\">Display Settings</a></td>\n";
							echo($displayBuf);
						?>
                    </tr>
                </table>
            </div>
            <div id="main">
	        	<div id="leftbar">
					<?php
						// Retrieve list of available lexicons
                        $queryReply = mysql_query("SELECT `Index_ID`, `Name` FROM `lexinfo` ORDER BY `Name`;");
                        $numTables = @mysql_num_rows($queryReply);
                        $displayBuf = "";
						$curLex = "";

						// Display list of lexicons with links to their individual administration pages
						if(!$numTables) {
							echo("<p>No lexicons found.</p>\n");
						} else {
							for ($i = 0; $i < $numTables; $i++) {
	                            $langID = mysql_result($queryReply, $i, 'Index_ID');
								$langName = mysql_result($queryReply, $i, 'Name');
	                            $displayBuf .= "<p><a href=\"adm_viewlex.php?i=" . $langID . "\" class=\"lexlink\">" . $langName . "</a></p>\n";
								
								if($langID == $lexIndex) {
									$curLex = $langName;
								}
							}
							echo($displayBuf);
						}
                    ?>
	            </div>
	            <div id="entryview">
                    <?php
						// Retrieve basic lexicon stats
						$queryReply = mysql_query("SELECT * FROM `lexinfo` WHERE `Index_ID`=" . $lexIndex . ";");
						$displayBuf = "<h1>" . $curLex . " Lexicon Manager</h1>\n";
						echo($displayBuf);
					?>
                    <table>
                    	<tr>
                        	<th colspan="2">Lexicon Information</th>
                        </tr>
                        <tr>
                        	<th>Name</th>
                            <td>
								<?php
									// Show the name of the current lexicon
                            		echo($curLex);
								?>
							</td>
                        </tr>
                        <tr>
                        	<th>Total Entries</th>
                            <td>
                            	<?php
									// Show the number of entries in the current lexicon
									echo(mysql_result($queryReply, 0, 'Count'));
								?>
                            </td>
                        </tr>
                        <tr>
                        	<th>Date Created</th>
                            <td>
                            	<?php
									// Show the timestamp of the lexicon's creation
									echo(mysql_result($queryReply, 0, 'DateCreated'));
								?>
                            </td>
                        </tr>
                        <tr>
                        	<th>Date Last Edited</th>
                            <td>
                            	<?php
									// Show the timestamp of the last time the lexicon was edited
									echo(mysql_result($queryReply, 0, 'DateChanged'));
								?>
                            </td>
                        </tr>
                    </table>
                    <p><a href="index.php?i=<?php echo($lexIndex); ?>">View Lexicon</a></p>

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