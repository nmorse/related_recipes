jQuery(document).ready(function($) {

	$('.related-articles-select').change(function() {
		var select = $(this),
				container = $('#related-articles'),
				id = select.val(),
				title = this.options[this.options.selectedIndex].text;

		if ($('#related-articles-' + id).length == 0) {
			container.prepend('<div class="related-articles" id="related-articles-' + id + '"><input type="hidden" name="related-articles[]" value="' + id + '"><span class="related-articles-title">' + title + '</span><a href="#">Delete</a></div>');
		}
	});

	$('.related-articles a').on('click', function() {
		var div = $(this).parent();

		div.css('background-color', '#ff0000').fadeOut('normal', function() {
			div.remove();
		});
		return false;
	});

	$('#related-articles').sortable();

});
