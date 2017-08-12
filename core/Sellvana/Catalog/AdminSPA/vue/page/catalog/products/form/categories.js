define(['sv-mixin-form-tab-grid'], function (SvMixinFormTabGrid) {
    return {
        mixins: [SvMixinFormTabGrid],
        data: function () {
            return {
                grid: this.form.categories_grid
            }
        }
    };
});