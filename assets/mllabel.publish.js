(function ($) {
	$(function () {
		var input = $('#mllabel-labels'),
		labels = input.data('labels'),
		cNodes;
		if (labels) {
			for (var key in labels) {
				if (labels.hasOwnProperty(key)) {
					cNodes = $('#' + key).find('label:first')[0].childNodes;

					if (cNodes.length) {
						cNodes[0].nodeValue = cNodes[0].nodeValue.replace(/[^(\n|\t\r)]+/, labels[key]);
					}
				}
			}
			input.remove();
		}
	});
}(this.jQuery));
