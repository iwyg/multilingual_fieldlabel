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
		labels = input.data('labels'),
		cNodes;
		if (labels) {
			for (var key in labels) {
				if (labels.hasOwnProperty(key)) {
					var label = $('#' + key).find('label:first');

					if( label.length > 0 ){

						// among the children
						label.contents().each(function() {

							// find Text nodes
							if(this.nodeName === "#text"){

								// only non-empty ones
								if( $.trim(this.textContent) !== '' ){
									this.textContent = labels[key];
								}
							}
						});
					}
				}
			}
			input.remove();
		}
	});
}(this.jQuery));
