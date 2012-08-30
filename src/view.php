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
	// view.php
	// 
	// Purpose: Display a single lexicon entry, properly-formatted
	// Inputs: 
	//     'i' (GET, mandatory): the index of the lexicon in the "lexinfo" table
	//     'e' (GET, mandatory): the index of the entry within the lexicon's table
	//
	// This PHP file does not produce a complete HTML page. It is intended to be loaded within another HTML page using AJAX
	//////

	// Ensure mandatory GET inputs are set, else end execution
	if(isset($_GET['i']) && isset($_GET['e'])) {
		$lexIndex = $_GET['i'];
		$entryIndex = $_GET['e'];
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

	// Retrieve table structure and create two parallel arrays containing field labels and field types
    $queryReply = mysql_query("SELECT `Name`, `FieldLabels`, `FieldTypes` FROM `lexinfo` WHERE `Index_ID`=" . $lexIndex . ";");
	$lang = mysql_result($queryReply, 0, 'Name');
	$fieldLabelArray = explode("\n", mysql_result($queryReply, 0, 'FieldLabels'));
    $fieldTypeArray = explode("\n", mysql_result($queryReply, 0, 'FieldTypes'));

	// Retrieve the appropriate lexicon entry from the database and assemble it into an array
	$queryReply = mysql_query("SELECT * FROM `" . $lang . "` WHERE `Index_ID`='" . $entryIndex . "';");
	for($i = 0; $i < count($fieldLabelArray); $i++) {
		$lexDataArray[$i] = mysql_result($queryReply, 0, $i);
	}
	
	// Retrieve the proper formatting information
	$queryReply = mysql_query("SELECT * FROM `" . $lang . "-styles`;");
	
	// Iterate over each field and display the entry
	$displayBuf = "";
	foreach($fieldLabelArray as $key => $fieldLabel) {
		$cleanFieldLabel = str_replace(' ', '', $fieldLabel);
		switch($fieldTypeArray[$key]) {
			case 'id':
			case 'hidden':
				// If an ID or hidden field, do nothing
				break;
			case 'text':
				// If a text field, display the contents
				$displayBuf .= "<br><span class=\"" . $cleanFieldLabel . "\">" . ((mysql_result($queryReply, $key, 'Label') == '1') ? $fieldLabel . ": " : "") . $lexDataArray[$key] . "</span>\n";
				break;
			case 'rich':
				// If a rich text field, create a new paragraph and display the formatted contents
				$fieldValue = $lexDataArray[$key];
				// Set up an array of conversions between LexManager markup and HTML
				$formatters = array(array("\n", "<br>", "<br>"),
								    array("''", "<b>", "</b>"),
									array("//", "<i>", "</i>"),
									array("__", "<u>", "</u>"));
				// Format rich text
				foreach($formatters as $formatter) {
					$counter = 0;
					while(strpos($fieldValue, $formatter[0]) !== FALSE) {
						if($counter % 2 == 0) {
							$tmp = explode($formatter[0], $fieldValue, 2);
							$fieldValue = implode($formatter[1], $tmp);
						} else {
							$tmp = explode($formatter[0], $fieldValue, 2);
							$fieldValue = implode($formatter[2], $tmp);
						}
						$counter++;
					}
				}
				
				// Format links
				while(strpos($fieldValue, "[[") !== FALSE) {
					$tmp = explode("[[", $fieldValue, 2);
					$target = substr($tmp[1], 0, strpos($tmp[1], "|"));
					if(is_numeric($target)) {
						$fieldValue = $tmp[0] . "<a class=\"entrylink\" href=\"view.php?i=" . $lexIndex . "&e=" . $target . "\">" . substr($tmp[1], strlen($target) + 1);;
					} else {
						$fieldValue = $tmp[0] . "<a class=\"entrylink external\" href=\"" . $target . "\">" . substr($tmp[1], strlen($target) + 1);
					}
				}
				$fieldValue = str_replace("]]", "</a>", $fieldValue);
				
				$displayBuf .= "<p class=\"" . $cleanFieldLabel . "\">" . ((mysql_result($queryReply, $key, 'Label') == '1') ? $fieldLabel . ": " : "") . $fieldValue . "</p>\n";
				break;
			case 'list':
				// If a list text field, format the contents and generate an HTML list
				$fieldValue = $lexDataArray[$key];
				// Set up an array of conversions between LexManager markup and HTML
				$formatters = array(array("''", "<b>", "</b>"),
									array("//", "<i>", "</i>"),
									array("__", "<u>", "</u>"));
				// Format rich text
				foreach($formatters as $formatter) {
					$counter = 0;
					while(strpos($fieldValue, $formatter[0]) !== FALSE) {
						if($counter % 2 == 0) {
							$tmp = explode($formatter[0], $fieldValue, 2);
							$fieldValue = implode($formatter[1], $tmp);
						} else {
							$tmp = explode($formatter[0], $fieldValue, 2);
							$fieldValue = implode($formatter[2], $tmp);
						}
						$counter++;
					}
				}
				
				// If a list field, generate an HTML list
				$fieldValueArray = explode("\n", $fieldValue);
				$displayBuf .= "<ol class=\"" . $cleanFieldLabel . "\">";
				foreach($fieldValueArray as $def) {
					$displayBuf .= "<li>" . $def . "</li>";
				}
				$displayBuf .= "</ol>\n";
				break;
		}
	}
	echo($displayBuf);
	
	// Print Formatting CSS
	$displayBuf = "<style type=\"text/css\">\n";
	foreach($fieldLabelArray as $key => $fieldLabel) {
		$cleanFieldLabel = str_replace(' ', '', $fieldLabel);
		switch($fieldTypeArray[$key]) {
			case 'id':
			case 'hidden':
				break;
			case 'text':
			case 'rich':
				$displayBuf .= "." . $cleanFieldLabel . "{\n";
				$displayBuf .= "font-family: " . mysql_result($queryReply, $key, 'FontFamily') . ";\n";
				$displayBuf .= "font-size: " . mysql_result($queryReply, $key, 'FontSize') . ";\n";
				$displayBuf .= "color: " . mysql_result($queryReply, $key, 'FontColor') . ";\n";
				$displayBuf .= ((mysql_result($queryReply, $key, 'Bold') == '1') ? "font-weight: bold;\n" : "font-weight: normal;\n");
				$displayBuf .= ((mysql_result($queryReply, $key, 'Italic') == '1') ? "font-style: italic;\n" : "font-style: normal;\n");
				$displayBuf .= ((mysql_result($queryReply, $key, 'Underline') == '1') ? "text-decoration: underline;\n" : "text-decoration: none;\n");
				$displayBuf .= ((mysql_result($queryReply, $key, 'SmallCaps') == '1') ? "font-variant: small-caps;\n" : "font-variant: normal;\n");
				$displayBuf .= "}\n\n";
				break;
			case 'list':
				$displayBuf .= "." . $cleanFieldLabel . "{\n";
				$displayBuf .= "font-family: " . mysql_result($queryReply, $key, 'FontFamily') . ";\n";
				$displayBuf .= "font-size: " . mysql_result($queryReply, $key, 'FontSize') . ";\n";
				$displayBuf .= "color: " . mysql_result($queryReply, $key, 'FontColor') . ";\n";
				$displayBuf .= "list-style-type: " . mysql_result($queryReply, $key, 'BulletType') . ";\n";
				$displayBuf .= "}\n\n";
				break;
		}
	}
	$displayBuf .= "</style>\n";
	echo($displayBuf);
	
	// Add necessary JavaScript events to any links
	echo("<script type=\"text/javascript\">wordLookup();</script>\n");

	// Close database connection
	@mysql_close($dbLink);
?>