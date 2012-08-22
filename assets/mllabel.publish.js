/*
vim: net:ts=4:sw=4:sts=4
*/

/**
 * @package Assets
 * @author thomas appel <mail@thomas-appel.com>

 * Displays <a href="http://opensource.org/licenses/gpl-3.0.html">GNU Public License</a>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
(function ($, Symphony) {
	function checkNodeContent() {
		return this.nodeType === 3 && $.trim(this.nodeValue).length;
	}

	function replaceNodeValue(textNodes, string) {
		if ( string == '' ) return;
		textNodes.each(function () {
			this.nodeValue = this.nodeValue.replace(/[^(\n|\t\r)]+/, string);
		});
	}

	$(function () {
		// replace fieldlabel values:
		var input = $('#mllabel-labels'),
		labels = input.data('labels'),
		label,
		field,
		cNodes,
		textNode,
		key,
		sortable,
		isIndexPage = Symphony.Context.get().env.page === 'index' ? true: false;

		if (labels) {
			for (key in labels) {
				if (labels.hasOwnProperty(key)) {
					field = $('#' + key);
					if (isIndexPage) {
						sortable = field.find('a span');
						cNodes = sortable.length ? sortable.contents() : field.contents();
						if (cNodes.length) {
							replaceNodeValue(cNodes.filter(checkNodeContent), labels[key]);
						}
					} else {
						if (field.hasClass('field-publish_tabs')) {
							field.text(labels[key]);
						} else {
							cNodes = field.find('label:first').contents();
							if (cNodes.length) {
								replaceNodeValue(cNodes.filter(checkNodeContent), labels[key]);
							}
						}
					}
				}
			}
			input.remove();
		}
	});
} (this.jQuery, this.Symphony));
