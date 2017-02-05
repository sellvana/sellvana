define(['vue', 'text!sv-comp-form-ip-mode-tpl'], function (Vue, ipModeTpl) {
    var IpMode = {
        template: ipModeTpl,
        props: ['value'],
        data: function () {
            return {
                default_mode: '',
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
            },
            parseValue: function () {
                var modes = this.value ? this.value.split("\n") : [], p;
                this.default_mode = modes[0] || 'DEBUG';
                this.ipmodes_value = [];
                for (var i = 1; i < modes.length; i++) {
                    p = modes[i].split(':');
                    this.ipmodes_value.push({ip: p[0], mode: p[1]});
                }
            },
            emitInput: function () {
                var i, l, str = [this.default_mode], v;
                for (i = 0, l = this.ipmodes_value.length; i < l; i++) {
                    str.push(this.ipmodes_value[i].ip + ':' + this.ipmodes_value[i].mode);
                }
                v = str.join("\n");
                this.$emit('input', v);
            }
        },
        created: function () {
            this.parseValue();
        },
        watch: {
            value: {
                deep: true,
                handler: function () {
                    this.parseValue();
                }
            },
            default_mode: function () {
                this.emitInput();
            },
            ipmodes_value: {
                deep: true,
                handler: function () {
                    this.emitInput();
                }
            }
        }
    };

    Vue.component('sv-comp-form-ip-mode', IpMode);

    return IpMode;
});