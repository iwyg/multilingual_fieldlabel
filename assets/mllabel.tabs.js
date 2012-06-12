/* vim: net:ts=4:sw=4:sts=4 */

/**
 * @package Assets
 * @author thomas appel <mail@thomas-appel.com>

 * Displays <a href="http://opensource.org/licenses/gpl-3.0.html">GNU Public License</a>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
(function ($, undefined) {
	/**
	 * Takes an element an transforms it into a tabable area given its
	 * decendent elements are preseted as a selectorstring in the users
	 * options.
	 *
	 * @module Symphony
	 * @class Tabs
	 * @constructor
	 * @param {Object} el			a jQuery nodeobject
	 * @param {Object} [settings]	user defined options to extend default
	 * behaviour
	 */
	var Tabs = (function () {
		var TabConstructor;

		/**
		 * creates the tabs and tabcontentarea
		 *
		 * @method _createTabs()
		 * @access private
		 * @return void
		 */
		function _createTabs() {
			var that = this,
			tabContainer = $('<ul class="tabs"/>'),
			fieldContainer = $('<div class="tabfields"/>'),
			li,  elements = this.element.find(this.settings.elements);
			elements.each(function (index) {
				var el = $(this),
				tabLabel = $.isFunction(that.settings.tabLabel) ? that.settings.tabLabel(el) : that.settings.tabLabel;

				li = $('<li class="tab">' + tabLabel + '</li>');
				li.data('tab-field', el);
				if (index === that.settings.selected) {
					li.addClass('selected');
				} else {
					el.addClass('hidden');
				}
				that.tabs.push(li[0]);
				that.tabfields.push(el[0]);
				tabContainer.append(li);
			});
			tabContainer.insertBefore(elements[0]);
			fieldContainer.append(elements);
			this.element.append(fieldContainer);
		}
		/**
		 * Let the Tabcontainer element listen for cklickevents on tabLabels
		 *
		 * @method _delegateEvents()
		 * @access private
		 * @return void
		 */
		function _delegateEvents() {
			this.element.on('click', '.tab', $.proxy(this.toggleTab, this));
		}

		TabConstructor = function (el, settings) {
			/**
			 * the container element
			 *
			 * @property element
			 * @type	 {Object}
			 * @public
			 */
			this.element = el;
			/**
			 * class settings
			 *
			 * @property settings
			 * @type	 {Object}
			 * @public
			 */
			this.settings = $.extend({}, Tabs.defaults, settings);

			/**
			 * stores all tablabel elemets
			 *
			 * @property tabs
			 * @type	 {Object}
			 * @public
			 */
			this.tabs = $([]);

			/**
			 * stores all tabcontent elemets
			 *
			 * @property tabfields
			 * @type	 {Object}
			 * @public
			 */
			this.tabfields = $([]);

			_createTabs.call(this);
			_delegateEvents.call(this);
		};

		/**
		 * Default settings
		 *
		 * @property defaults
		 * @type	{Object}
		 * @static
		 */
		TabConstructor.defaults = {
			tabLabel: 'label',
			elements: 'input[type=text]',
			selected: 0,
			onTabChange: null
		};

		TabConstructor.prototype = {
			/**
			 *  Fires after a tablabel has benn cklicked.
			 *  Changes the current visible tabcontent an fires a callback
			 *  defined in the useroptions.
			 *
			 *  @method toggleTab()
			 *  @param {Object} event			the event object
			 *  @param {Object} event.target	the tablabel element
			 *  @public
			 *  @return void
			 */
			toggleTab: function (event) {
				event.preventDefault();
				var tab = $(event.target),
				field = tab.data('tab-field');
				this.tabfields.addClass('hidden');
				this.tabs.removeClass('selected');
				field.removeClass('hidden');
				tab.addClass('selected');
				// fire the usercallback if available:
				if ($.isFunction(this.settings.onTabChange)) {
					this.settings.onTabChange(field, tab);
				}
			}
		};
		return TabConstructor;
	}());


	// expose Tabs as a plugin:
	$.fn.mllabeltabs = function (options) {
		return this.each(function () {
			new Tabs($(this), options);
		});
	};
}(this.jQuery));
