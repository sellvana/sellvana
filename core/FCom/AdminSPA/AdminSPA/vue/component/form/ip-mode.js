define(['vue', 'text!sv-comp-form-ip-mode-tpl'], function (Vue) {
    var IpMode = {
        data: function () {
            return {
                ipmodes_value: []
            }
        },
        computed: {
            ipModes: function () {
                return [
                    {id: 'DEBUG'},
                    {id: 'DEVELOPMENT'},
                    {id: 'STAGING'},
                    {id: 'PRODUCTION'},
                    {id: 'RECOVERY'},
                    {id: 'DISABLED'}
                ];
            }
        },
        methods: {
            addMode: function () {
                this.ipmodes_value.push({ip: '', mode: 'DEBUG'});
            },
            delMode: function (m) {
                var i, l;
                for (i = 0, l = this.ipmodes_value.length; i < l; i++) {
                    if (m.ip === this.ipmodes_value[i].ip) {
                        this.ipmodes_value.splice(i, 1);
                        break;
                    }
                }
            }
        },
        watch: {
            ipmodes_value: function (value) {
                var i, l, str = [];
                for (i = 0, l = value.length; i < l; i++) {
                    str.push(value[i].ip + ':' + value[i].mode);
                }
                this.$emit('input', str.join("\n"));
            }
        }
    };

    Vue.component('sv-comp-form-ip-mode', IpMode);

    return IpMode;
});