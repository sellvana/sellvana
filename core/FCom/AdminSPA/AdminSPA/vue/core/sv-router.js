define(['vue', 'vue-router', 'sv-app-data'], function (Vue, VueRouter, SvAppData) {
    Vue.use(VueRouter);

    function routeView(args) {
        return function (resolve, reject) {
            require(args, function (component, template) {
//console.log(args, component, template);
                if (!component) {
                    component = {};
                }
                if (template) {
                    component.template = template;
                }
                resolve(component);
            });
        }
    }

    var routes = [];
    for (var i in SvAppData.routes) {
        var r = SvAppData.routes[i], route = {path: r.path, component: routeView(r.require)};
        if (r.children) {
            route.children = [];
            for (var j in r.children) {
                var child = r.children[j];
                route.children.push({path: child.path, component: routeView(child.require)});
            }
        }
        routes.push(route);
    }

    var router = new VueRouter({
        routes: routes
    });

    return router;
})