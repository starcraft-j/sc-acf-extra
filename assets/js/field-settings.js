/**
 * SCN ACF Repeater — sub-field manager (admin field-group edit screen).
 *
 * Handles Add/Remove rows in the sub-fields list shown inside the repeater
 * field's settings UI.
 */
(function ($) {
	'use strict';

	function reindex($wrapper) {
		$wrapper.find('> .scn-sub-fields-table > tbody > .scn-sub-fields-row').each(function (i) {
			var $row = $(this);
			$row.attr('data-index', i);
			$row.find('input, select, textarea').each(function () {
				var $el = $(this);
				['name', 'id'].forEach(function (attr) {
					var val = $el.attr(attr);
					if (!val) return;
					$el.attr(attr, val.replace(/(\[sub_fields\])\[(?:\d+|__INDEX__)\]/, '$1[' + i + ']'));
				});
			});
		});
	}

	$(document).on('click', '.scn-sub-fields-add', function (e) {
		e.preventDefault();
		var $wrapper = $(this).closest('.scn-sub-fields');
		var tmpl = $wrapper.find('> .scn-sub-fields-row-template').html();
		var nextIndex = $wrapper.find('> .scn-sub-fields-table > tbody > .scn-sub-fields-row').length;
		var html = tmpl.replace(/__INDEX__/g, nextIndex);
		$wrapper.find('> .scn-sub-fields-table > tbody').append(html);
		reindex($wrapper);
	});

	$(document).on('click', '.scn-sub-fields-remove', function (e) {
		e.preventDefault();
		var $wrapper = $(this).closest('.scn-sub-fields');
		$(this).closest('.scn-sub-fields-row').remove();
		reindex($wrapper);
	});

	// Sync the row's data-type attribute with the selected type so the
	// "image only" controls (return_format) show/hide via CSS.
	$(document).on('change', '.scn-sub-fields-type', function () {
		$(this).closest('.scn-sub-fields-row').attr('data-type', $(this).val());
	});
})(jQuery);
