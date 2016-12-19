define(['sv-comp-grid'/*, 'json!sv-page-sales-orders-grid-config'*/], function (SvCompGrid/*, gridConfig*/) {
    var gridConfig = {
        id: 'sales/orders',
        data_url: 'https://127.0.0.1/sellvana/admin-spa/sales/orders/data',
        columns: [
            {type: 'select-checkbox'},
            {type: 'actions'},
            {field: 'id', label: 'ID'},
            {
                field: 'state_overall', label: 'Overall State', options: [
                {value: 'pending', label: 'Pending'},
                {value: 'processing', label: 'Processing'},
                {value: 'shipped', label: 'Shipped'}
            ]
            }
        ],
        filters: [
            {field: 'id'},
            {field: 'state_overall', type: 'multiselect'}
        ],
        export: {
            format_options: [
                {value: 'csv', label: 'CSV'}
            ]
        },
        pager: {
            pagesize_options: [5, 10, 20, 50, 100]
        }
    };
    return {
        data: {
            grid: {
                config: gridConfig
            }
        },
        components: {
            'sv-comp-grid': SvCompGrid
        }
    };
});