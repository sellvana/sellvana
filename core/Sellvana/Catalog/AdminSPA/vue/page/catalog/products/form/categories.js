    define(['sv-mixin-form-tab-grid'], function (SvMixinFormTabGrid) {
    return {
        mixins: [SvMixinFormTabGrid],
        data: function () {
            return {
                grid: this.form.categories_grid
            }
        },
        created: function () {
            this.form.product.categories = _.cloneDeep(this.grid.config.data);
        },
        methods: {
            doPanelAction: function (act) {
                switch (act.name) {
                    case 'add':
                        this.grid.rows.push({
                            id: -Math.random(),
                            category_id: '',
                            sort_order: ''
                        });
                        break;
                }
            },
            doBulkAction: function (act) {
                switch (act.name) {
                    case 'remove':
                        this.removeSelectedLocalRows();
                        break;
                }
            },
            doRowAction: function (act) {
                switch (act.name) {
                    case 'remove':
                        this.removeCurrentLocalRow(act.row.id);
                        break;

                    default:
                        console.log(act);
                }
            }
        },
        watch: {
            'grid.rows': {
                deep: true,
                handler: function (rows) {
                    if (!this.form.product.categories || _.isEqual(rows, this.form.product.categories)) {
                        return;
                    }
                    this.setTabFlag('edited', true);
                    var dupIds = {}, errors = false;
                    for (var i = 0, l = rows.length; i < l; i++) {
                        if (!rows[i].category_id) {
                            continue;
                        }
                        dupIds[rows[i].category_id] = dupIds[rows[i].category_id] === false; // 1st pass sets false, 2nd pass sets true
                    }
                    for (var i = 0, l = rows.length; i < l; i++) {
                        this.$set(rows[i], 'category_id__error', dupIds[rows[i].category_id] ? this._('Duplicate') : '');
                        if (dupIds[rows[i].category_id]) {
                            errors = true;
                        }
                    }
                    this.form.product.categories = _.cloneDeep(rows);
                    this.setTabFlag('errors', errors);
                }
            }
        }
    };
});