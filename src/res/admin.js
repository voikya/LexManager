// Root Function
// (Executes on document ready)
$(function() {
	adm_newlexicon();
	adm_lex_newentry();
	adm_lex_editentry();
	adm_lex_viewall();
	adm_backup();
});


/* PRIMARY FUNCTIONS */
// Each function corresponds to a single page

// adm_newlexicon.php
function adm_newlexicon() {
	attachFieldListControls();
	bindRemoveLink();
	if($.ui) makeDragAndDrops();
	makeNextButtons();
}

// adm_lex_newentry.php
function adm_lex_newentry() {
	attachListControlsForNewEntry();
	attachSubmitControlsForNewEntry();
}

// adm_lex_editentry.php
function adm_lex_editentry() {
	attachSubmitControlsForEditEntry();
}

// adm_lex_viewall.php
function adm_lex_viewall() {
	attachViewLinks();
	attachShowEntriesButtonControls();
}

// adm_backup.php
function adm_backup() {
	attachExportLinks();
}



/* SECONDARY FUNCTIONS */

// On adm_newlexicon.php, add new field functionality to the list of lexicon fields
function attachFieldListControls() {
	$('#addfield')
		.bind('click', function() {
			var newField = "<div class=\"fieldcontainer\"><div class=\"onefield\"><table><tr><td><select><option value=\"text\" selected=\"yes\">Basic Text</option><option value=\"rich\">Rich Text</option><option value=\"list\">List</option><option value=\"hidden\">Hidden</option></select></td><td><input type=\"text\" size=\"50\"></td><td><a href=\"#\" class=\"remove_link\">X</a></td></tr></table></div><div class=\"onefield_break\"></div></div>";
			$('#fields')
				.append(newField);
			$('#addfield')
				.appendTo('#fields');
			$('#toCollation')
				.appendTo('#fields');
			makeDragAndDrops();
			bindRemoveLink();
			return false;
	});
}

// On adm_newlexicon.php, add remove field functionality to the list of lexicon fields
function bindRemoveLink() {
	$('.remove_link')
		.bind('click', function() {
			$(this)
				.parents('div.fieldcontainer')
				.remove();
	});
}

// On adm_newlexicon.php, add drag and drop functionality to the list of lexicon fields
function makeDragAndDrops() {
	$('div.fieldcontainer')
		.not('.idfield')
		.draggable({containment: '#entryview',
				    stack: 'div.fieldcontainer',
					revert: 'invalid'});
	$('div.onefield_break')
		.droppable({accept: 'div.fieldcontainer',
					hoverClass: 'onefield_break_hover',
					drop: function(event, ui) {
						var dropTarget = $(this).parents('div.fieldcontainer');
						var dropSrc = "<div class=\"fieldcontainer\">" + $(ui.draggable).html() + "</div>";
						$(ui.draggable)
							.remove();
						$(dropSrc)
							.insertAfter(dropTarget);
						makeDragAndDrops();
					}});
}

// On adm_newlexicon.php, make each 'Next' button correctly hide the current virtual page and display the next one
function makeNextButtons() {
	$('#toFields')
		.bind('click', function() {
			$('#language_name')
				.hide();
			$('#fields')
				.show();
		});
	$('#toCollation')
		.bind('click', function() {
			$('#fields')
				.hide();
			$('#collation')
				.show();
			var fieldTypes = "";
			var fieldLabels = "";
			$('div.onefield option:selected')
				.each(function() {
					var val = $(this).attr('value');
					fieldTypes = (fieldTypes == "") ? fieldTypes + val : fieldTypes + "\n" + val;
			});
			$('div.onefield input')
				.each(function() {
					var val = $(this).attr('value');
					fieldLabels = (fieldLabels == "") ? fieldLabels + val : fieldLabels + "\n" + val;
		});
			$('#fields')
				.append('<input type="hidden" name="fieldTypes" value="' + fieldTypes + '"><input type="hidden" name="fieldLabels" value="' + fieldLabels + '">');
		});
}

// On adm_lex_newentry.php, add controls for 'list'-type fields to add new list elements
function attachListControlsForNewEntry() {
	$('input.addListInput')
		.bind('click', function() {
			var newField = "<li><input type=\"text\" size=\"50\"></li>";
			$(this)
				.siblings('ol.listinput')
				.append(newField);
		});
}

// On adm_lex_newentry.php, assemble all list items into single fields on submit
function attachSubmitControlsForNewEntry() {
	$('#addentry')
		.submit(function() {
			$('ol.listinput')
				.each(function() {
					var newFieldVal = "";
					$(this)
						.find('input')
						.each(function() {
							var myVal = $(this).attr('value');
							newFieldVal += myVal + "\n";
						});
					var newField = "<input type=\"hidden\" name=\"" + $(this).attr('id') + "\" value=\"" + newFieldVal.substring(0, newFieldVal.length - 1) + "\">\n";
					$('#addentry')
						.append(newField);
				});
			return true;
		});
}

// On adm_lex_editentry.php, assemble all list items into single fields on submit
function attachSubmitControlsForEditEntry() {
	$('#editentry')
		.submit(function() {
			$('ol.listinput')
				.each(function() {
					var newFieldVal = "";
					$(this)
						.find('input')
						.each(function() {
							var myVal = $(this).attr('value');
							newFieldVal += myVal + "\n";
						});
					var newField = "<input type=\"hidden\" name=\"" + $(this).attr('id') + "\" value=\"" + newFieldVal.substring(0, newFieldVal.length - 1) + "\">\n";
					$('#editentry')
						.append(newField);
				});
			return true;
		});
}

// On adm_lex_viewall.php, attach an AJAX call to each 'View' link to display a single record
function attachViewLinks() {
	$('a.viewlink')
		.bind('click', function() {
			var datasource = $(this).attr('href');
			$('#entryview').load(datasource);
			return false;					
		});
}

// On adm_lex_viewall.php, attach a redirect to the "Go" button to reload the page with new display settings
function attachShowEntriesButtonControls() {
	$('#showEntries')
		.bind('click', function() {
			var lexIndex = $('#lexIndex').attr('value');
			var maxEntriesDisplayed = $('#maxEntriesDisplayed').attr('value');
			var startFrom = $('#startFrom').attr('value');
			window.location = 'adm_lex_viewall.php?i=' + lexIndex + '&start=' + startFrom + '&num=' + maxEntriesDisplayed;
		});
}


// On adm_backup.php, attach page redirects to each 'Export' button
function attachExportLinks() {
	$('input.export_sql')
		.bind('click', function() {
			var table = $(this).parent().prev().html();
			window.location = 'adm_export.php?type=sql&table=' + table;
			return false;
		});
}