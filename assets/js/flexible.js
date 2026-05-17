/**
 * SC Flexible Content — post-edit UI.
 *
 * Handles instance add (via layout dropdown), remove, ↑↓ reorder, and the
 * required input-name reindexing after every change.
 */
(function ($) {
	'use strict';

	function escapeRegExp(str) {
		return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function reindex($wrapper) {
		var prefix = $wrapper.attr('data-input-prefix');
		if (!prefix) return;
		var pattern = new RegExp('(' + escapeRegExp(prefix) + ')\\[(\\d+|__INDEX__)\\]');
		$wrapper.find('> .sc-flexible-instances > .sc-flexible-instance').each(function (i) {
			var $inst = $(this);
			$inst.attr('data-index', i);
			$inst.find('input, select, textarea').each(function () {
				var $el = $(this);
				['name', 'id'].forEach(function (attr) {
					var val = $el.attr(attr);
					if (!val) return;
					$el.attr(attr, val.replace(pattern, '$1[' + i + ']'));
				});
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
})(jQuery);
