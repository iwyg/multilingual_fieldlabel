/*
vim: net:ts=4:sw=4:sts=4
*/

/**
 * @package Assets
 * @author thomas appel <mail@thomas-appel.com>

 * Displays <a href="http://opensource.org/licenses/gpl-3.0.html">GNU Public License</a>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
(function ($) {
	$(function () {
		// replace fieldlabel values:
		var input = $('#mllabel-labels'),
		labels = input.data('labels'), label,
		cNodes;
		if (labels) {
			for (var key in labels) {
				if (labels.hasOwnProperty(key)) {
					label = $('#' + key).find('label:first')[0];
					cNodes = label ? label.childNodes : false;
					if (cNodes && cNodes.length) {
						cNodes[0].nodeValue = cNodes[0].nodeValue.replace(/[^(\n|\t\r)]+/, labels[key]);
					}
				}
			}
			input.remove();
		}
	});
}(this.jQuery));
