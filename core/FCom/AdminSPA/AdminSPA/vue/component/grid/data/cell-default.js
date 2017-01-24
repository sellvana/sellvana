define([], function () {
    return {
        props: ['grid', 'row', 'col'],
        template: '<td>{{row[col.field]}}</td>'
    };
});