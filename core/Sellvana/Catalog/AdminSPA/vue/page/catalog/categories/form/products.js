define(['sv-mixin-form-tab-grid'], function (SvMixinFormTabGrid) {

    return {
        mixins: [SvMixinFormTabGrid],
        data: function () {
            return {
                grid: {
                    config: this.form.products_grid,
                    rows: this.form.products_grid.config.data
                }
            }
        }
    };
});