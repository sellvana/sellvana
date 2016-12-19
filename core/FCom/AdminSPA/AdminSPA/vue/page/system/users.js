define(['sv-comp-grid', 'json!sv-page-system-users-grid-config'], function (SvCompGrid, gridConfig) {
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