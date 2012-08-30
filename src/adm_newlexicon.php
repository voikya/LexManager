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
	// adm_newlexicon.php
	// 
	// Purpose: Present a series of forms to allow the administrator to create a new lexicon
	// Inputs: 
	//     multiple (POST, optional): the new data submitted to replace the current row
	//
	//////
	
	// Check if user is logged in
	session_start();
	if($_SESSION['LM_login'] !== "1") {
		header("Location: adm_login.php");
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

	// If data was submitted via POST, update the database
	if(isset($_POST['submit'])) {
		// Retrieve submitted configuration fields
		$lang = mysql_real_escape_string($_POST['lang']);
		$alphabet = mysql_real_escape_string($_POST['alphabet']);
		$collation = mysql_real_escape_string($_POST['collation']);
		$fieldTypes = str_replace("\r", "", $_POST['fieldTypes']);
		$fieldLabels = str_replace("\r", "", $_POST['fieldLabels']);
		
		// Explode the field type and label variables to create two parallel arrays
		$explodedFieldTypes = explode("\n", $fieldTypes);
		$explodedFieldLabels = explode("\n", $fieldLabels);

		// Check if a 'lexinfo' table has been created (to keep track of all lexicons stored in the database)
		$queryReply = mysql_query("SHOW tables LIKE 'lexinfo';");
		if(!mysql_num_rows($queryReply)) {
			// If 'lexinfo' does not exist, create it
			mysql_query("CREATE TABLE `lexinfo` (`Index_ID` int(6) NOT NULL AUTO_INCREMENT, `Name` varchar(255) COLLATE utf8_unicode_ci NOT NULL, `Alphabet` text COLLATE utf8_unicode_ci NOT NULL, `Collation` text COLLATE utf8_unicode_ci NOT NULL, `Count` int(6) NOT NULL, `FieldTypes` text COLLATE utf8_unicode_ci NOT NULL, `FieldLabels` text COLLATE utf8_unicode_ci NOT NULL, `SearchableFields` text COLLATE utf8_unicode_ci NOT NULL, `DateCreated` datetime NOT NULL, `DateChanged` datetime NOT NULL, PRIMARY KEY (`Index_ID`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
		}
		// Store the new lexicon's configuration information in 'lexinfo'
		mysql_query("INSERT INTO `lexinfo` (`Name`, `Alphabet`, `Collation`, `Count`, `FieldTypes`, `FieldLabels`, `DateCreated`, `DateChanged`) VALUES ('" . $lang . "', '" . $alphabet . "', '" . $collation . "', 0, '" . mysql_real_escape_string($fieldTypes) . "', '" . mysql_real_escape_string($fieldLabels) . "', 'Word', NOW(), NOW());");
		
		// Create a SQL create table command by iterating over each field and its corresponding field type
		$tableStructureStr = "";
		foreach($explodedFieldTypes as $key => $value) {
			switch($value) {
				case 'id':
					$tableStructureStr = "`" . mysql_real_escape_string($explodedFieldLabels[$key]) . "` int(6) unsigned NOT NULL AUTO_INCREMENT";
					break;
				case 'text':
				case 'hidden':
					$tableStructureStr .= ", `" . mysql_real_escape_string($explodedFieldLabels[$key]) . "` varchar(255) COLLATE utf8_unicode_ci NOT NULL";
					break;
				case 'rich':
				case 'list':
					$tableStructureStr .= ", `" . mysql_real_escape_string($explodedFieldLabels[$key]) . "` text COLLATE utf8_unicode_ci NOT NULL";
					break;
			}
		}
		mysql_query("CREATE TABLE `" . $lang . "` (" . $tableStructureStr . ", PRIMARY KEY (`Index_ID`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
		
		// Create a formatting table for the new lexicon to store CSS information and fill it with default values
		// CSS styles not applicable for a given field are left NULL
		mysql_query("CREATE TABLE `" . $lang . "-styles` (`Index_ID` int(3) NOT NULL AUTO_INCREMENT, `Name` varchar(255), `FontFamily` varchar(64), `FontSize` varchar(64), `FontColor` varchar(64), `Bold` bool, `Italic` bool, `Underline` bool, `SmallCaps` bool, `Label` bool, `BulletType` varchar(64), PRIMARY KEY(`Index_ID`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
		foreach($explodedFieldTypes as $key => $value) {
			switch($value) {
				case 'id':
				case 'hidden':
					mysql_query("INSERT INTO `" . $lang . "-styles` (`Name`) VALUES ('" . mysql_real_escape_string($explodedFieldLabels[$key]) . "');");
					break;
				case 'text':
				case 'rich':
					mysql_query("INSERT INTO `" . $lang . "-styles` (`Name`, `FontFamily`, `FontSize`, `FontColor`, `Bold`, `Italic`, `Underline`, `SmallCaps`, `Label`) VALUES ('" . mysql_real_escape_string($explodedFieldLabels[$key]) . "', 'serif', 'medium', '#000000', '0', '0', '0', '0', '0');");
					break;
				case 'list':
					mysql_query("INSERT INTO `" . $lang . "-styles` (`Name`, `FontFamily`, `FontSize`, `FontColor`, `Label`, `BulletType`) VALUES ('" . mysql_real_escape_string($explodedFieldLabels[$key]) . "', 'serif', 'medium', '#000000', '0', 'decimal');");
					break;
			}
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
                <div id="adminnav">
                	<p>• <a href="manager.php">Admin Home</a></p>
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
                	<?php
						if(isset($_POST['submit'])) {
							// If data was submitted, output confirmation
							echo("<p>A new lexicon has been created for '" . $lang . "'. Select it on the list to the left to get started!</p>");						
						} else {
							// If no data was submitted, generate the New Lexicon form
							// The form consists of three virtual pages (actually a single page) managed by JavaScript in admin.js
							echo('
							<form id="addlex" action="adm_newlexicon.php" method="post">
								<fieldset id="language_name">
									<legend>Language Name</legend>
									<p>Please enter the name of the language:</p>
									<input type="text" name="lang" size="50">
									<p></p>
									<input type="button" class="next" id="toFields" value="Next &gt;">
								</fieldset>
								<fieldset id="fields">
									<legend>Add Fields</legend>
									<p>Select the fields that will be available for every lexicon entry. Click and drag to reorder. A basic outline has already been provided.</p>
									<p>Choose "Basic Text" for simple, short text fields, such as the word itself, its pronunciation, transliteration, etc. Choose "Rich Text" for longer fields that may contain paragraphs, formatting, links, etc. Choose "List" for fields such as definition lists. Chose "Hidden" for fields that will not be visible on the public lexicon (for personal notes, plugins, etc).</p>
									<div id="fieldlist">
										<div class="fieldcontainer idfield">
											<div class="onefield">
												<table>
													<tr>
														<td><select disabled="disabled"><option value="id" selected="yes">ID</option><option value="text">Basic Text</option><option value="rich">Rich Text</option><option value="list">List</option><option value="hidden">Hidden</option></select></td>
														<td><input type="text" size="50" value="Index_ID" disabled="disabled"></td>
													</tr>
												</table>
											</div>
											<div class="onefield_break"></div>
										</div>
										<div class="fieldcontainer idfield">
											<div class="onefield">
												<table>
													<tr>
														<td><select disabled="disabled"><option value="text" selected="yes">Basic Text</option></select></td>
														<td><input type="text" size="50" value="Word" disabled="disabled"></td>
													</tr>
												</table>
											</div>
											<div class="onefield_break"></div>
										</div>
										<div class="fieldcontainer">
											<div class="onefield">
												<table>
													<tr>
														<td><select><option value="text" selected="yes">Basic Text</option><option value="rich">Rich Text</option><option value="list">List</option><option value="hidden">Hidden</option></select></td>
														<td><input type="text" size="50" value="Pronunciation"></td>
														<td><a href="#" class="remove_link">X</a></td>
													</tr>
												</table>
											</div>
											<div class="onefield_break"></div>
										</div>
										<div class="fieldcontainer">
											<div class="onefield">
												<table>
													<tr>
														<td><select><option value="text" selected="yes">Basic Text</option><option value="rich">Rich Text</option><option value="list">List</option><option value="hidden">Hidden</option></select></td>
														<td><input type="text" size="50" value="Part of Speech"></td>
														<td><a href="#" class="remove_link">X</a></td>
													</tr>
												</table>
											</div>
											<div class="onefield_break"></div>
										</div>
										<div class="fieldcontainer">
											<div class="onefield">
												<table>
													<tr>
														<td><select><option value="text">Basic Text</option><option value="rich">Rich Text</option><option value="list" selected="yes">List</option><option value="hidden">Hidden</option></select></td>
														<td><input type="text" size="50" value="Definition"></td>
														<td><a href="#" class="remove_link">X</a></td>
													</tr>
												</table>
											</div>
											<div class="onefield_break"></div>
										</div>
										<div class="fieldcontainer">
											<div class="onefield">
												<table>
													<tr>
														<td><select><option value="text">Basic Text</option><option value="rich" selected="yes">Rich Text</option><option value="list">List</option><option value="hidden">Hidden</option></select></td>
														<td><input type="text" size="50" value="Examples"></td>
														<td><a href="#" class="remove_link">X</a></td>
													</tr>
												</table>
											</div>
											<div class="onefield_break"></div>
										</div>
										<div class="fieldcontainer">
											<div class="onefield">
												<table>
													<tr>
														<td><select><option value="text">Basic Text</option><option value="rich" selected="yes">Rich Text</option><option value="list">List</option><option value="hidden">Hidden</option></select></td>
														<td><input type="text" size="50" value="Etymology"></td>
														<td><a href="#" class="remove_link">X</a></td>
													</tr>
												</table>
											</div>
											<div class="onefield_break"></div>
										</div>
									</div>
									
									<input type="button" id="addfield" value="Add Field">
									<input type="button" class="next" id="toCollation" value="Next &gt;">
								</fieldset>
								<fieldset id="collation">
									<legend>Alphabet and Collation</legend>
									<p>In the following field, list the language\'s alphabet (or, if not applicable, the alphabet used in romanization). Use capital letters only, separated by a space. This is what will appear in the top navigation bar of the lexicon. The standard Roman alphabet has been inserted below.</p>
									<textarea rows="5" cols="50" name="alphabet">A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</textarea>
									<p>In the next field, describe the collation (alphabetical ordering) to be used. Group together all letters that are treated identically for collation. For instance, in English, upper and lowercase letters (\'A\' and \'a\', \'B\' and \'b\', etc.) are considered to be variants of the same letter, and so when ordered alphabetically, words beginning with \'A\' may be interspersed with words beginning with \'a\'. In Spanish, the four glyphs \'A\', \'a\', \'Á\', and \'á\' are considered to be variants of the same letter, while \'Ñ\' and \'ñ\' are considered distinct from \'N\' and \'n\'. Use brackets to group together digraphs that should be treated as a single letter. Thus, the collations for English and Spanish would look as follows:</p>
									<p>English: Aa Bb Cc Dd Ee Ff Gg Hh Ii Jj Kk Ll Mm Nn Oo Pp Qq Rr Ss Tt Uu Vv Ww Xx Yy Zz</p>
									<p>Spanish: AaÁá Bb Cc [CH][Ch][ch] Dd EeÉé Ff Gg Hh Ii Jj Kk Ll [LL][Ll][ll] Mm Nn Ññ OoÓó Pp Qq Rr [RR][Rr][rr] Ss Tt UuÚúÜü Vv Ww Xx Yy Zz</p>
									<textarea rows="5" cols="50" name="collation">Aa Bb Cc Dd Ee Ff Gg Hh Ii Jj Kk Ll Mm Nn Oo Pp Qq Rr Ss Tt Uu Vv Ww Xx Yy Zz</textarea>
									<p></p>
									<input type="submit" class="next" name="submit" value="Create Lexicon">
								</fieldset>
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