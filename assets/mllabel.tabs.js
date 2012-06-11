(function ($) {
	var Tabs = (function () {
		var Constructor;

		function _createTabs() {
			var that = this,
			tabContainer = $('<ul class="tabs"/>'),
			li,  elements = this.element.find(this.settings.elements);
			this.tabs = $([]);
			this.tabfields = $([]);
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
		}
		function _delegateEvents() {
			this.element.on('click', '.tab', $.proxy(this.toggleTab, this));
		}
		Constructor = function (el, settings) {
			this.element = el;
			this.settings = $.extend({}, Tabs.defaults, settings);
			_createTabs.call(this);
			_delegateEvents.call(this);
		};

		Constructor.prototype = {
			toggleTab: function (event) {
				event.preventDefault();
				var tab = $(event.target),
				field = tab.data('tab-field');
				this.tabfields.addClass('hidden');
				this.tabs.removeClass('selected');
				field
					.removeClass('hidden')
					.focus();
				tab.addClass('selected');
			}
		};
		return Constructor;
	}());


	Tabs.defaults = {
		tabLabel: 'label',
		elements: 'input[type=text]',
		selected: 0
	};

	$.fn.mllabeltabs = function (options) {
		return this.each(function () {
			new Tabs($(this), options);
		});
	};
}(this.jQuery));
