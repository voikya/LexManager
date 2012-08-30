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
	// adm_lex_newentry.php
	// 
	// Purpose: Present a form to allow the administrator to add a new entry to the current lexicon
	// Inputs: 
	//     'i' (GET, mandatory): the index of the lexicon in the "lexinfo" table
	//     multiple (POST, optional): the new data submitted to add to the lexicon
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
	} elseif(!isset($_POST['submit'])) {
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
        <script type="text/javascript" src="res/admin.js"></script>
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
                    	// Retrieve table structure and create two parallel arrays containing field labels and field types
                    	$queryReply = mysql_query("SELECT `FieldLabels`, `FieldTypes` FROM `lexinfo` WHERE `Index_ID`=" . $lexIndex . ";");
                    	$fieldLabelArray = explode("\n", mysql_result($queryReply, 0, 'FieldLabels'));
                    	$fieldTypeArray = explode("\n", mysql_result($queryReply, 0, 'FieldTypes'));
						
						// If data was submitted via POST, update the database
						if(isset($_POST['submit'])) {
							$querystring = "INSERT INTO `" . $curLex . "` VALUES (";
							// Iterate over submitted fields by referencing the field label array, and create a SQL insert command
							foreach($fieldLabelArray as $key => $fieldLabel) {
								$fieldLabel = str_replace(' ', '', $fieldLabel);
								switch($fieldTypeArray[$key]) {
									case 'id':
										// If an ID field, insert nothing; ID values will be handled by MySQL's AUTO_INCREMENT
										$querystring .= "''";
										break;
									default:
										// Otherwise, add the data to the SQL insert
										$val = str_replace("\r", "", $_POST[$fieldLabel]);
										$querystring .= ", '" . mysql_real_escape_string($val) . "'";
										break;
								}
							}
							$querystring .= ");";
							$queryReply = mysql_query($querystring);
							
							// If update was successful, update the timestamp of the most recent edit in "lexinfo", else output an error message
							if($queryReply) {
								echo("<p>New entry successfully added to lexicon.</p>\n");
								mysql_query("UPDATE `lexinfo` SET `DateChanged`=NOW(), `Count`=Count+1 WHERE `Index_ID`=" . $lexIndex . ";");
							} else {
								echo("<p>Error: Database insert failed.</p>\n");
							}
						}
                    ?>
                    <form id="addentry" action="adm_lex_newentry.php?i=<?php echo $lexIndex; ?>" method="post">
                        <table class="lex_newentry">
                            <?php
								// Iterate over the table structure and generate an empty form to input the new data
                                $displayBuf = "";
                                foreach($fieldLabelArray as $key => $fieldLabel) {
									$cleanFieldLabel = str_replace(' ', '', $fieldLabel);
                                    $displayBuf .= "<tr><td><label for=\"" . $cleanFieldLabel . "\">" . $fieldLabel . "</label></td>\n";
									switch($fieldTypeArray[$key]) {
										case 'id':
											// If an ID field, show an uneditable field containing the presumed ID of the new entry
											$queryReply = mysql_query("SELECT MAX(Index_ID) FROM `" . $curLex . "`;");
											$newIndex = mysql_result($queryReply, 0, "MAX(Index_ID)") + 1;
											$displayBuf .= "<td><input type=\"text\" name=\"" . $cleanFieldLabel . "\" disabled=\"disabled\" size=\"50\" value=\"" . $newIndex . "\"></td>\n";
											break;
										case 'text':
											// If a text field, show an empty text input field
											$displayBuf .= "<td><input type=\"text\" name=\"" . $cleanFieldLabel . "\" size=\"50\"></td>\n";
											break;
										case 'rich':
											// If a rich text field, show an empty textarea field
											$displayBuf .= "<td><textarea rows=\"5\" cols=\"50\" name=\"" . $cleanFieldLabel . "\"></textarea><br>";
											$displayBuf .= "<span class=\"howtoformat\">''bold'', //italic//, __underline__, [[URL|link text]], [[ID#|link text]]</span></td>\n";
											break;
										case 'list':
											// If a list field, show a list containing a single text input field corresponding to one list item and a button to add new fields
											$displayBuf .= "<td><ol class=\"listinput\" id=\"" . $cleanFieldLabel . "\"><li><input type=\"text\" size=\"50\"></li></ol>\n";
											$displayBuf .= "<input type=\"button\" class=\"addListInput\" value=\"+\"></td>\n";
											break;
										case 'hidden':
											// If a hidden field, show an empty text input field
											$displayBuf .= "<td><input type=\"text\" name=\"" . $cleanFieldLabel . "\" size=\"50\"> (Hidden)</td>\n";
											break;
										default:
											// If none of the above, show an error message
											$displayBuf .= "<td>No input means specified</td></tr>\n";
											break;
									}
                                }
								$displayBuf .= "<tr><td></td><td><input type=\"submit\" name=\"submit\" value=\"Submit\"></td></tr></table>";
                                echo($displayBuf);
                            ?>
                        </table>
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