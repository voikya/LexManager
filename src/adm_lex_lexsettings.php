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
	// adm_lex_lexsettings.php
	// 
	// Purpose: Open lexicon formatting and display settings and allow the administrator to make changes
	// Inputs: 
	//     'i' (GET, mandatory): the index of the lexicon in the "lexinfo" table
	//     multiple (POST, optional): the new data submitted to replace the current display settings
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
		die('<p class=\"statictext warning\">Error: Missing index.</p>');
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
                    	$queryReply = mysql_query("SELECT `FieldLabels`, `FieldTypes`, `SearchableFields` FROM `lexinfo` WHERE `Index_ID`=" . $lexIndex . ";");
                    	$fieldLabelArray = explode("\n", mysql_result($queryReply, 0, 'FieldLabels'));
                    	$fieldTypeArray = explode("\n", mysql_result($queryReply, 0, 'FieldTypes'));
						$cleanFieldLabelArray;
						foreach($fieldLabelArray as $key => $field) {
							$cleanFieldLabelArray[$key] = str_replace(' ', '', $field);
						}
						$searchableFieldArray = explode("\n", mysql_result($queryReply, 0, 'SearchableFields'));
						
						// If data was submitted via POST, update the database
						if(isset($_POST['submit'])) {
							// Iterate over submitted fields by referencing the field label array, and create a SQL update command for each field
							foreach($fieldLabelArray as $key => $fieldLabel) {
								switch($fieldTypeArray[$key]) {
									case 'id':
									case 'hidden':
										break;
									case 'text':
									case 'rich':
										$cleanFieldLabel = $cleanFieldLabelArray[$key];
										$fontFamily = mysql_real_escape_string($_POST[$cleanFieldLabel . "-fontfamily"]);
										$fontSize = mysql_real_escape_string($_POST[$cleanFieldLabel . "-size"]);
										$fontColor = mysql_real_escape_string($_POST[$cleanFieldLabel . "-color"]);
										$bold = (isset($_POST[$cleanFieldLabel . "-bold"])) ? 1 : 0;
										$italic = (isset($_POST[$cleanFieldLabel . "-italic"])) ? 1 : 0;
										$underline = (isset($_POST[$cleanFieldLabel . "-underline"])) ? 1 : 0;
										$smallcaps = (isset($_POST[$cleanFieldLabel . "-smallcaps"])) ? 1 : 0;
										$label = (isset($_POST[$cleanFieldLabel . "-label"])) ? 1 : 0;

										mysql_query("UPDATE `" . $curLex . "-styles` SET `FontFamily`='" . $fontFamily . "', `FontSize`='" . $fontSize . "', `FontColor`='" . $fontColor . "', `Bold`='" . $bold . "', `Italic`='" . $italic . "', `Underline`='" . $underline . "', `SmallCaps`='" . $smallcaps . "', `Label`='" . $label . "' WHERE `Index_ID`='" . ($key + 1) . "';");
										break;
									case 'list':
										$cleanFieldLabel = $cleanFieldLabelArray[$key];
										$fontFamily = mysql_real_escape_string($_POST[$cleanFieldLabel . "-fontfamily"]);
										$fontSize = mysql_real_escape_string($_POST[$cleanFieldLabel . "-size"]);
										$fontColor = mysql_real_escape_string($_POST[$cleanFieldLabel . "-color"]);
										$label = (isset($_POST[$cleanFieldLabel . "-label"])) ? 1 : 0;
										$bulletType = mysql_real_escape_string($_POST[$cleanFieldLabel . "-bullets"]);
										
										mysql_query("UPDATE `" . $curLex . "-styles` SET `FontFamily`='" . $fontFamily . "', `FontSize`='" . $fontSize . "', `FontColor`='" . $fontColor . "', `Label`='" . $label . "', `BulletType`='" . $bulletType . "' WHERE `Index_ID`='" . ($key + 1) . "';");
										break;
								}
							}
							
							// Identify which fields were marked as searchable, and update the lexinfo table
							$searchable = implode("\n", $_POST['searchable']);
							mysql_query("UPDATE `lexinfo` SET `SearchableFields`='" . mysql_real_escape_string($searchable) . "' WHERE `Index_ID`=" . $lexIndex . ";");
							
							echo("<p>Display settings updated.</p>\n");
							
							// Close database connection and end script
							@mysql_close($dbLink);
							exit();
						}
                    ?>
                    <form id="lexdisplaysettings" action="adm_lex_lexsettings.php?i=<?php echo $lexIndex; ?>" method="post">
                        <table class="lex_newentry">
                        	<tr>
                            	<th colspan="2">Entry Formatting Options</th>
                            </tr>
                            <?php
								// Get the formatting settings for the current lexicon
								$displayBuf = "";
								$queryReply = mysql_query("SELECT * FROM `" . $curLex . "-styles`;");
								
								// Iterate over the table structure and generate a form containing the current row's contents pre-loaded
								for($i = 0; $i < mysql_num_rows($queryReply); $i++) {
									// For each field, get the data, a "cleaned" label safe for HTML IDs, and create a new HTML table row showing the field label
									$fieldLabel = $fieldLabelArray[$i];
									$cleanFieldLabel = $cleanFieldLabelArray[$i];
									$fieldType = $fieldTypeArray[$i];
									$displayBuf .= "<tr><td><label for=\"" . $cleanFieldLabel . "\">" . $fieldLabel . "</label></td>\n";
									// Show field options based on the field type
									switch($fieldType) {
										case 'id':
										case 'hidden':
											// If an ID or hidden field, output a note stating there are no display settings
											$displayBuf .= "<td>This is a non-displaying field.</td>\n";
											break;
										case 'text':
										case 'rich':
											// If a text or rich text field, load the current settings...
											$fontFamily = mysql_result($queryReply, $i, 'FontFamily');
											$fontSize = mysql_result($queryReply, $i, 'FontSize');
											$fontColor = mysql_result($queryReply, $i, 'FontColor');
											$bold = mysql_result($queryReply, $i, 'Bold');
											$italic = mysql_result($queryReply, $i, 'Italic');
											$underline = mysql_result($queryReply, $i, 'Underline');
											$smallcaps = mysql_result($queryReply, $i, 'SmallCaps');
											$label = mysql_result($queryReply, $i, 'Label');
											
											// ... and present a pre-loaded form to make changes
											$displayBuf .= "<td><table class=\"lex_displayoptions\">";
											$displayBuf .= "<tr><td>Font Family:</td><td><select name=\"" . $cleanFieldLabel . "-fontfamily\"><option value=\"serif\"" . (($fontFamily == "serif") ? " selected=\"selected\"" : "") . ">serif</option><option value=\"sans-serif\"" . (($fontFamily == "sans-serif") ? " selected=\"selected\"" : "") . ">sans-serif</option><option value=\"monospace\"" . (($fontFamily == "monospace") ? " selected=\"selected\"" : "") . ">monospace</option><option value=\"'Palatino Linotype', 'Book Antiqua', Palatino, serif\"" . (($fontFamily == "'Palatino Linotype', 'Book Antiqua', Palatino, serif") ? " selected=\"selected\"" : "") . ">formal</option></select></td></tr>";
											$displayBuf .= "<tr><td>Font Size:</td><td><select name=\"" . $cleanFieldLabel . "-size\"><option value=\"xx-large\"" . (($fontSize == "xx-large") ? " selected=\"selected\"" : "") . ">xx-large</option><option value=\"x-large\"" . (($fontSize == "x-large") ? " selected=\"selected\"" : "") . ">x-large</option><option value=\"larger\"" . (($fontSize == "larger") ? " selected=\"selected\"" : "") . ">larger</option><option value=\"larger\"" . (($fontSize == "large") ? " selected=\"selected\"" : "") . ">large</option><option value=\"medium\"" . (($fontSize == "medium") ? " selected=\"selected\"" : "") . ">medium</option><option value=\"small\"" . (($fontSize == "small") ? " selected=\"selected\"" : "") . ">small</option><option value=\"smaller\"" . (($fontSize == "smaller") ? " selected=\"selected\"" : "") . ">smaller</option><option value=\"x-small\"" . (($fontSize == "x-small") ? " selected=\"selected\"" : "") . ">x-small</option></select></td></tr>";
											$displayBuf .= "<tr><td>Font Color:</td><td><input type=\"text\" name=\"" . $cleanFieldLabel . "-color\" size=\"50\" value=\"" . $fontColor . "\"></td></tr>";
											$displayBuf .= "<tr><td>Other:</td><td><input type=\"checkbox\" name=\"" . $cleanFieldLabel . "-bold\" value=\"yes\"" . (($bold) ? " checked=\"checked\"" : "") . ">Bold<br>
																				   <input type=\"checkbox\" name=\"" . $cleanFieldLabel . "-italic\" value=\"yes\"" . (($italic) ? " checked=\"checked\"" : "") . ">Italic<br>
																				   <input type=\"checkbox\" name=\"" . $cleanFieldLabel . "-underline\" value=\"yes\"" . (($underline) ? " checked=\"checked\"" : "") . ">Underline<br>
																				   <input type=\"checkbox\" name=\"" . $cleanFieldLabel . "-smallcaps\" value=\"yes\"" . (($smallcaps) ? " checked=\"checked\"" : "") . ">Small Caps</td></tr>";
											$displayBuf .= "<tr><td>Visible Label:</td><td><input type=\"checkbox\" name=\"" . $cleanFieldLabel . "-label\" value=\"yes\"" . (($label) ? " checked=\"checked\"" : "") . "></td></tr>";
											$displayBuf .= "</table></td>\n";
											break;
										case 'list':
											// If a list field, load the current settings...
											$fontFamily = mysql_result($queryReply, $i, 'FontFamily');
											$fontSize = mysql_result($queryReply, $i, 'FontSize');
											$fontColor = mysql_result($queryReply, $i, 'FontColor');
											$bulletType = mysql_result($queryReply, $i, 'BulletType');
											$label = mysql_result($queryReply, $i, 'Label');
											
											// ... and present a pre-loaded form to make changes
											$displayBuf .= "<td><table class=\"lex_displayoptions\">";
											$displayBuf .= "<tr><td>Font Family:</td><td><select name=\"" . $cleanFieldLabel . "-fontfamily\"><option value=\"serif\"" . (($fontFamily == "serif") ? " selected=\"selected\"" : "") . ">serif</option><option value=\"sans-serif\"" . (($fontFamily == "sans-serif") ? " selected=\"selected\"" : "") . ">sans-serif</option><option value=\"monospace\"" . (($fontFamily == "monospace") ? " selected=\"selected\"" : "") . ">monospace</option><option value=\"'Palatino Linotype', 'Book Antiqua', Palatino, serif\"" . (($fontFamily == "'Palatino Linotype', 'Book Antiqua', Palatino, serif") ? " selected=\"selected\"" : "") . ">formal</option></select></td></tr>";
											$displayBuf .= "<tr><td>Font Size:</td><td><select name=\"" . $cleanFieldLabel . "-size\"><option value=\"xx-large\"" . (($fontSize == "xx-large") ? " selected=\"selected\"" : "") . ">xx-large</option><option value=\"x-large\"" . (($fontSize == "x-large") ? " selected=\"selected\"" : "") . ">x-large</option><option value=\"larger\"" . (($fontSize == "larger") ? " selected=\"selected\"" : "") . ">larger</option><option value=\"larger\"" . (($fontSize == "large") ? " selected=\"selected\"" : "") . ">large</option><option value=\"medium\"" . (($fontSize == "medium") ? " selected=\"selected\"" : "") . ">medium</option><option value=\"small\"" . (($fontSize == "small") ? " selected=\"selected\"" : "") . ">small</option><option value=\"smaller\"" . (($fontSize == "smaller") ? " selected=\"selected\"" : "") . ">smaller</option><option value=\"x-small\"" . (($fontSize == "x-small") ? " selected=\"selected\"" : "") . ">x-small</option></select></td></tr>";
											$displayBuf .= "<tr><td>Font Color:</td><td><input type=\"text\" name=\"" . $cleanFieldLabel . "-color\" size=\"50\" value=\"" . $fontColor . "\"></td></tr>";
											$displayBuf .= "<tr><td>Bullet Type:</td><td><select name=\"" . $cleanFieldLabel . "-bullets\"><option value\"decimal\"" . (($bulletType == "decimal") ? " selected=\"selected\"" : "") . ">Decimal (1., 2., 3.)</option><option value=\"lower-roman\"" . (($bulletType == "lower-roman") ? " selected=\"selected\"" : "") . ">Lower-Roman (i., ii., iii.)</option><option value=\"upper-roman\"" . (($bulletType == "upper-roman") ? " selected=\"selected\"" : "") . ">Upper-Roman (I., II., III.)</option><option value=\"lower-alpha\"" . (($bulletType == "lower-alpha") ? " selected=\"selected\"" : "") . ">Lower-Alpha (a., b., c.)</option><option value=\"upper-alpha\"" . (($bulletType == "upper-alpha") ? " selected=\"selected\"" : "") . ">Upper-Alpha (A., B., C.)</option></select></td></tr>";
											$displayBuf .= "<tr><td>Visible Label:</td><td><input type=\"checkbox\" name=\"" . $cleanFieldLabel . "-label\" value=\"yes\"></td></tr>";
											$displayBuf .= "</table></td>\n";
											break;
										default:
											// If none of the above, show an error message
											$displayBuf .= "<td>No input means specified.</td></tr>\n";
											break;
									}
								}

                                echo($displayBuf);
                            ?>
                            <tr>
                            	<th colspan="2">Searchable Fields</td>
                            </tr>
                            <tr>
                            	<td colspan="2">Select which fields will be searched from the Search Box on the main lexicon view.<br>
                                    <table class="lex_displayoptions">
										<?php
											// Print out a series of checkboxes indicating which fields are searchable
											$displayBuf = "";
                                            foreach($fieldLabelArray as $key => $field) {
                                                $displayBuf .= "<tr><td>" . $field . "</td><td><input type=\"checkbox\" name=\"searchable[]\" value=\"" . $field . "\"" . (in_array($field, $searchableFieldArray) ? " checked=\"checked\"" : "") . "></td></tr>\n";
                                            }
											echo($displayBuf);
                                        ?>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                            	<td></td>
                                <td><input type="submit" name="submit" value="Submit"></td>
                            </tr>
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