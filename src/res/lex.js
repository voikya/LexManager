$(function() {
	adjustSearch();
	alphaQueries();
	wordLookup();
});

function adjustSearch() {
	var defaultText='Search...';
	$('#searchbox')
		.attr('value', defaultText)
		.bind('focus', function() {
			if ($(this).attr('value') == defaultText) {
				$(this)
					.attr('value', '')
					.css({'font-style': 'normal', 'color': 'black'});
			}
				
		})
		.bind('blur', function() {
			if ($(this).attr('value') != defaultText) {
				$(this)
					.attr('value', defaultText)
					.css({'font-style': 'italic', 'color': '#999'});
			}
		});
	$('#searchform')
		.submit(function() {
			var query = $('#searchbox').attr('value');
			var lexindex = $('#lexindex').html();
			$('#leftbar').load('query.php?i=' + lexindex + '&q=' + query);
			return false;
		});
}

function alphaQueries() {
	$('.alpha')
		.bind('click', function() {
			var letter = $(this).text();
			var lex = $(this).attr('href');
			var datasource = 'query.php?i=' + lex + '&a=' + letter;
			$('#leftbar')
				.load(datasource)
				.scrollTop(0);
			return false;
		});
}

function wordLookup() {
	$('a.entrylink')
		.not('.external')
		.unbind()
		.bind('click', function() {
			var datasource = $(this).attr('href');
			$('#entryview').load(datasource);
			return false;
		});
	$('a.searchedentrylink')
		.unbind()
		.bind('click', function() {
			var index = $(this).attr('id');
			var query = $('#query').text();
			var datasource = 'view.php?i=' + index + '&s=' + query;
			$('#entryview').load(datasource);
			return false;
		});
}