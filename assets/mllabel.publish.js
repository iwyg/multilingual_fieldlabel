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
		labels = input.data('labels');

		if (labels) {

			// index
			if( $('body').hasClass('index') ){
				for (var key in labels) {
					if (labels.hasOwnProperty(key) && labels[key] !== '') {
						var field = $('#' + key);

						var orderable_field = $('#' + key).find('> a > span');

						if( orderable_field.length > 0 )
							orderable_field.html(labels[key]);
						else
							field.html(labels[key]);
					}
				}
			}

			// single
			else{
				for (var key in labels) {
					if (labels.hasOwnProperty(key) && labels[key] !== '') {
						var field = $('#' + key);

						// publish tabs
						if( field.hasClass('field-publish_tabs') ){
							field.html(labels[key]);
						}

						else{

							var label = null;

							// image cropper
							if( field.hasClass('field-imagecropper') ){
								label = field.find('p.label');
							}
							else{
								label = field.find('label:first');
							}

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
				}
			}

			input.remove();
		}
	});
}(this.jQuery));
