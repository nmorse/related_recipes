jQuery(document).ready(function($) {

	$('.related-recipes-select').change(function() {
		var select = $(this),
				container = $('#related-recipes'),
				id = select.val(),
				title = this.options[this.options.selectedIndex].text;

		if ($('#related-recipes-' + id).length == 0) {
			container.prepend('<div class="related-recipes" id="related-recipes-' + id + '"><input type="hidden" name="related-recipes[]" value="' + id + '"><span class="related-recipes-title">' + title + '</span><a href="#">Delete</a></div>');
		}
	});

	$('.related-recipes a').on('click', function() {
		var div = $(this).parent();

		div.css('background-color', '#ff0000').fadeOut('normal', function() {
			div.remove();
		});
		return false;
	});

	$('#related-recipes').sortable();

});
