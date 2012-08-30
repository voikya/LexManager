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
	// index.php
	// 
	// Purpose: The primary means for the general public to access the lexicon
	// Inputs: 
	//     'i' (GET, mandatory): the index of the lexicon in the "lexinfo" table
	//
	//////

	// Ensure mandatory GET inputs are set, else end execution
	if(isset($_GET['i'])) {
		$lexIndex = mysql_real_escape_string($_GET['i']);		   
	} else {
		die('<p class=\"statictext warning\">Error: No index provided.</p>');
	}
	
	// Import configuration
	if(!file_exists('cfg/lex_config.php')) {
		die("<p class=\"statictext warning\">Error: Missing configuration file</p>");
	} else {
		include('cfg/lex_config.php');
	}

	// Connect to MySQL database
	$dbLink = mysql_connect($LEX_serverName, $LEX_adminUser, $LEX_adminPassword);
    @mysql_select_db($LEX_databaseName) or die("      <p class=\"statictext warning\">Unable to connect to database.</p>\n");
    $charset = mysql_query("SET NAMES utf8");

	// Retrieve the language name and alphabet from 'lexinfo'
	$queryReply = mysql_query("SELECT `Name`, `Alphabet` FROM `lexinfo` WHERE `Index_ID`=" . $lexIndex . ";");
	$curLex = mysql_result($queryReply, 0, 'Name');
	$alphabet = mysql_result($queryReply, 0, 'Alphabet');
?>
<!DOCTYPE HTML>
<html>
	<head>
    	<title><?php echo($curLex); ?> Lexicon</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="res/lex_core.css">
        <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="res/favicon.ico">
        <link rel="apple-touch-icon" href="res/apple-touch-icon.png">
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
        <script type="text/javascript" src="res/lex.js"></script>
    </head>
    <body>
    	<div id="lexindex"><?php echo($lexIndex); ?></div>
    	<div id="content">
        	<div id="topbar">
                <div id="search">
                	<form id="searchform" action="">
               		 	<input type="text" size="50" id="searchbox" name="searchbox" />
                        <input type="image" src="res/search.png" value="Submit" id="submit" /><br/>
                    </form>
                    <a href="advanced.php?i=<?php echo($lexIndex); ?>" id="advanced">Advanced</a>
                </div>
            	<a href="index.php?i=<?php echo($lexIndex); ?>" class="title"><?php echo($curLex); ?> Lexicon</a><br/>
                <table class="alphabet">
                	<tr>
                    	<?php
							// Split the alphabet into an array of individual letters, then output the alphabetical navigation
							$displayBuf = "";
							$alphabetArray = explode(" ", $alphabet);
							foreach($alphabetArray as $letter) {
								$displayBuf .= "<td><a href=\"" . $lexIndex . "\" class=\"alpha\">" . $letter . "</a></td>";
							}
							echo($displayBuf . "\n");
                    	?>
                    </tr>
                </table>
            </div>
            <div id="main">
	        	<div id="leftbar">

	            </div>
	            <div id="entryview">
	            	<p class="statictext">Enter a search term in the box above, or select a letter to browse.</p>
                	<p class="statictext">For more advanced search options, please head over to the <a href="advanced.php?i=<?php echo($lexIndex); ?>">Advanced Query</a> page.</p>

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