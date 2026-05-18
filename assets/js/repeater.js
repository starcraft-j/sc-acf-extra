/**
 * SC ACF Repeater — admin UI.
 *
 * Responsibilities:
 *   - Append a fresh row (from <template>) when Add Row is clicked
 *   - Remove a row (respects min)
 *   - Drag-to-sort rows via the ≡ handle (jQuery UI sortable)
 *   - Re-index input names AND ids after every row add/remove/reorder so ACF's
 *     client-side validation and label[for] pointers stay coherent
 */
(function ($) {
	'use strict';

	function escapeRegExp(str) {
		return str.replace(/[-.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function reindex($wrapper) {
		var prefix = $wrapper.attr('data-input-prefix');
		if (!prefix) return;
		// `name` form: acf[field_xxx][0][field_yyy]
		var bracketPattern = new RegExp('(' + escapeRegExp(prefix) + ')\\[(\\d+|__INDEX__)\\]');
		// `id` form: ACF replaces brackets with dashes: acf-field_xxx-0-field_yyy.
		// Rewrite the index that immediately follows the wrapper's dash-form prefix.
		var idPrefix = prefix.replace(/\[/g, '-').replace(/\]/g, '');
		var idPattern = new RegExp('(' + escapeRegExp(idPrefix) + ')-(\\d+|__INDEX__)(?=[-"]|$)');

		$wrapper.find('> .sc-repeater-table > tbody > .sc-repeater-row').each(function (i) {
			var $row = $(this);
			$row.attr('data-index', i);
			$row.find('input, select, textarea, label').each(function () {
				var $el = $(this);
				var name = $el.attr('name');
				if (name) $el.attr('name', name.replace(bracketPattern, '$1[' + i + ']'));
				var id = $el.attr('id');
				if (id) $el.attr('id', id.replace(idPattern, '$1-' + i));
				var forAttr = $el.attr('for');
				if (forAttr) $el.attr('for', forAttr.replace(idPattern, '$1-' + i));
			});
		});
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

	// Drag-to-sort. Idempotent — guards against double-init when ACF re-fires
	// 'append' after we already set up this wrapper.
	function initSortable($wrapper) {
		if ($wrapper.data('sc-sortable-init')) return;
		$wrapper.data('sc-sortable-init', true);
		$wrapper.find('> .sc-repeater-table > tbody').sortable({
			items: '> tr.sc-repeater-row',
			handle: '.sc-repeater-handle',
			placeholder: 'sc-repeater-sort-placeholder',
			forcePlaceholderSize: true,
			axis: 'y',
			tolerance: 'pointer',
			// <tr> drags collapse column widths by default; clone with fixed cell
			// widths so the drag image keeps the row's real shape.
			helper: function (e, tr) {
				var $orig = $(tr);
				var $helper = $orig.clone();
				// Duplicate ids on the floating helper would attract jQuery selectors
				// and (worse) wp.media / select2 bindings that key off `#id`.
				// Strip ids on the helper — they only need to exist on the live row.
				$helper.find('[id]').addBack('[id]').removeAttr('id');
				$helper.children().each(function (i) {
					$(this).width($orig.children().eq(i).width());
				});
				return $helper;
			},
			update: function () { reindex($wrapper); }
		});
	}

	$(function () {
		$('.sc-repeater').each(function () { initSortable($(this)); });
	});

	if (typeof acf !== 'undefined' && acf.add_action) {
		acf.add_action('append', function ($el) {
			$el.find('.sc-repeater').addBack('.sc-repeater').each(function () { initSortable($(this)); });
		});
	}
})(jQuery);
