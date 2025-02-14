(function ($) {
	$.widget("ui.combobox", {
		_create: function() {
			var _self = this
				, options = $.extend({}, this.options, {
				minLength: 0,
				source: function(request, response) {
					if (!request.term.length) {
						response(_self.options.initialValues);
					} else {
						if (typeof _self.options.source === "function") {
							_self.options.source(request, response);
						} else if (typeof _self.options.source === "string") {
							$.ajax({
								url: _self.options.source,
								data: request,
								dataType: "json",
								success: function(data, status) {
									response(data);
								},
								error: function() {
									response([]);
								}
							});
						}
					}
				}
			});

			this.element.autocomplete(options);

			this.button = $("<button type='button'>&nbsp;</button>")
				.attr("tabIndex", -1)
				.attr("title", "Show All Items")
				.insertAfter(this.element)
				.button({
					icons: {
						primary: "ui-icon-triangle-1-s"
					},
				text: false
				})
				.removeClass("ui-corner-all")
				.addClass("ui-corner-right ui-button-icon")
				.click(function() {
					if (_self.element.autocomplete("widget").is(":visible")) {
						_self.element.autocomplete("close");
						return;
					}
					_self.element.autocomplete("search", "");
					_self.element.focus();
			});
		}
	})
})(jQuery);