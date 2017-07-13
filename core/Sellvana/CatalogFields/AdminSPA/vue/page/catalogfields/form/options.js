define(['sv-comp-grid', 'text!sv-page-catalog-fields-form-options-tpl', 'json!sv-page-catalog-fields-form-options-config'],
	function (SvCompGrid, tabFieldOptionsTpl, fieldOptionsGridConfig) {

	return {
		props: {
			form: {
				type: Object
			}
		},
		data: function () {
			if (fieldOptionsGridConfig.data_url && this.form.field && this.form.field.id) {
				fieldOptionsGridConfig.data_url = fieldOptionsGridConfig.data_url.supplant({id: this.form.field.id});
			}
			return {
				grid: {
					config: fieldOptionsGridConfig
				}
			}
		},
		template: tabFieldOptionsTpl,
		components: {
			'sv-comp-grid': SvCompGrid
		}
	};
});