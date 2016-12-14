define(['text!' + Sellvana.modules.FCom_AdminSPA.src_root + '/AdminSPA/vue/component/header.html'], function(headerTpl) {
    return {
        props: ['navs'],
        template: headerTpl
    }
})