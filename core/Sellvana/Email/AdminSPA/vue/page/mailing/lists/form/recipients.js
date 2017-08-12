define(['sv-mixin-form-tab-grid'], function (SvMixinFormTabGrid) {
    var Component = {
        mixins: [SvMixinFormTabGrid],
        data: function () {
            return {
                grid: this.form.recipients_grid
            }
        }
    };

    return Component;
});