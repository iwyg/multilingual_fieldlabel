/* vim: net:ts=4:sw=4:sts=4 */

/**
 * @package Assets
 * @author thomas appel <mail@thomas-appel.com>

 * Displays <a href="http://opensource.org/licenses/gpl-3.0.html">GNU Public License</a>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
(function ($) {

	// get the tablabel value
	function getTabLabel(el) {
		return el.data('tab-label');
	}

	// fires when a tab has changed
	function tabChangeCallback(tabcontent, tab) {
		tabcontent.focus();
	}
	// add the additional fields for available languages
	function addLableFields(settings, labels) {
		var instance = $(this),
		header = instance.find('.content:first'),
		label = header.find('label:first'),
		field = label.find('input[type=text]'),
		fieldClone, fname, llabelName;

		label.addClass('mllabel-tabs');

		field.data('tab-label', settings.sys_lang);

		for (var i = 0, l = settings.additional_lang.length; i < l; i++) {
			fieldClone = field.clone();
			fieldClone.data('tab-label', settings.additional_lang[i]);
			fieldClone.val('');
			llabelName = 'label-' + settings.additional_lang[i];

			fieldClone.val(labels.hasOwnProperty(field[0].value) ? labels[field[0].value].labels[llabelName] : '');
			fieldClone[0].name = field[0].name.replace(/label/, llabelName);
			label.append(fieldClone);
		}

		// setup the tabarea
		label.mllabeltabs({
			tabLabel: getTabLabel,
			onTabChange: tabChangeCallback
		});
	}


	$(function () {
		var labels = $('#mllabel-labels').data('values'),
		settings = $('#mllabel-settings').data('settings');

		function setup() {
			addLableFields.call(this, settings, labels);
		}

		$('form').find('ol > li.instance').each(setup);

		$('form > fieldset > .frame').on('constructstop.duplicator', function (e) {
			setup.call(e.target);
		});
	});
}(this.jQuery));
