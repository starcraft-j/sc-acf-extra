/**
 * SC Flexible Content — post-edit UI.
 *
 * Handles instance add (via layout dropdown), remove, ↑↓ reorder, and the
 * required input-name reindexing after every change.
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
		var idPrefix = prefix.replace(/\[/g, '-').replace(/\]/g, '');
		var idPattern = new RegExp('(' + escapeRegExp(idPrefix) + ')-(\\d+|__INDEX__)(?=[-"]|$)');

		$wrapper.find('> .sc-flexible-instances > .sc-flexible-instance').each(function (i) {
			var $inst = $(this);
			$inst.attr('data-index', i);
			$inst.find('input, select, textarea, label').each(function () {
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

	$(document).on('click', '.sc-flexible-add-btn', function (e) {
		e.preventDefault();
		var $wrapper = $(this).closest('.sc-flexible');
		var $select = $wrapper.find('> .sc-flexible-controls > .sc-flexible-add-select');
		var layoutName = $select.val();
		if (!layoutName) return;

		var $template = $wrapper.find('> template.sc-flexible-layout-template[data-layout="' + layoutName + '"]');
		if (!$template.length) return;

		var nextIndex = $wrapper.find('> .sc-flexible-instances > .sc-flexible-instance').length;
		var html = $template.html().replace(/__INDEX__/g, nextIndex);
		var $newInst = $(html);
		$wrapper.find('> .sc-flexible-instances').append($newInst);
		reindex($wrapper);
		$select.val('');

		// Let ACF wire up any complex sub-field types (image, select2 etc.).
		if (typeof acf !== 'undefined' && acf.do_action) {
			acf.do_action('append', $newInst);
		}
	});

	$(document).on('click', '.sc-flexible-instance-remove', function (e) {
		e.preventDefault();
		var $wrapper = $(this).closest('.sc-flexible');
		$(this).closest('.sc-flexible-instance').remove();
		reindex($wrapper);
	});

	$(document).on('click', '.sc-flexible-instance-up', function (e) {
		e.preventDefault();
		var $wrapper = $(this).closest('.sc-flexible');
		var $inst = $(this).closest('.sc-flexible-instance');
		var $prev = $inst.prev('.sc-flexible-instance');
		if (!$prev.length) return;
		$inst.insertBefore($prev);
		reindex($wrapper);
	});

	$(document).on('click', '.sc-flexible-instance-down', function (e) {
		e.preventDefault();
		var $wrapper = $(this).closest('.sc-flexible');
		var $inst = $(this).closest('.sc-flexible-instance');
		var $next = $inst.next('.sc-flexible-instance');
		if (!$next.length) return;
		$inst.insertAfter($next);
		reindex($wrapper);
	});

	// Drag-to-sort via the ≡ handle in each instance header. ↑↓ buttons remain
	// for keyboard / touch accessibility.
	function initSortable($wrapper) {
		if ($wrapper.data('sc-sortable-init')) return;
		$wrapper.data('sc-sortable-init', true);
		$wrapper.find('> .sc-flexible-instances').sortable({
			items: '> .sc-flexible-instance',
			handle: '.sc-flexible-instance-handle',
			placeholder: 'sc-flexible-sort-placeholder',
			forcePlaceholderSize: true,
			axis: 'y',
			tolerance: 'pointer',
			update: function () { reindex($wrapper); }
		});
	}

	$(function () {
		$('.sc-flexible').each(function () { initSortable($(this)); });
	});

	if (typeof acf !== 'undefined' && acf.add_action) {
		acf.add_action('append', function ($el) {
			$el.find('.sc-flexible').addBack('.sc-flexible').each(function () { initSortable($(this)); });
		});
	}
})(jQuery);
