/**
 * SC ACF Repeater — admin UI.
 *
 * Responsibilities:
 *   - Append a fresh row (from <template>) when Add Row is clicked
 *   - Remove a row (respects min)
 *   - Re-index inputs (name="field[0][sub]" -> name="field[1][sub]") on changes
 *
 * Intentionally light. No drag-and-drop sort yet (Stage 4 in the roadmap).
 */
(function ($) {
	'use strict';

	function reindex($wrapper) {
		var prefix = $wrapper.attr('data-input-prefix');
		if (!prefix) return;
		var pattern = new RegExp('(' + escapeRegExp(prefix) + ')\\[(\\d+|__INDEX__)\\]');
		$wrapper.find('> .sc-repeater-table > tbody > .sc-repeater-row').each(function (i) {
			var $row = $(this);
			$row.attr('data-index', i);
			$row.find('input, select, textarea').each(function () {
				var $el = $(this);
				['name', 'id', 'data-name'].forEach(function (attr) {
					var val = $el.attr(attr);
					if (!val) return;
					$el.attr(attr, val.replace(pattern, '$1[' + i + ']'));
				});
			});
		});
	}

	function escapeRegExp(str) {
		return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function rowCount($wrapper) {
		return $wrapper.find('> .sc-repeater-table > tbody > .sc-repeater-row').length;
	}

	$(document).on('click', '.sc-repeater-add', function (e) {
		e.preventDefault();
		var $wrapper = $(this).closest('.sc-repeater');
		var max = parseInt($wrapper.data('max'), 10) || 0;
		if (max > 0 && rowCount($wrapper) >= max) return;

		var template = $wrapper.find('> .sc-repeater-row-template').html();
		var nextIndex = rowCount($wrapper);
		var html = template.replace(/__INDEX__/g, nextIndex);
		$wrapper.find('> .sc-repeater-table > tbody').append(html);
		reindex($wrapper);

		// Let ACF wire up any complex sub-field types (image, select2, etc).
		if (typeof acf !== 'undefined' && acf.do_action) {
			acf.do_action('append', $wrapper.find('> .sc-repeater-table > tbody > .sc-repeater-row:last-child'));
		}
	});

	$(document).on('click', '.sc-repeater-remove-btn', function (e) {
		e.preventDefault();
		var $wrapper = $(this).closest('.sc-repeater');
		var min = parseInt($wrapper.data('min'), 10) || 0;
		if (rowCount($wrapper) <= min) return;
		$(this).closest('.sc-repeater-row').remove();
		reindex($wrapper);
	});
})(jQuery);
