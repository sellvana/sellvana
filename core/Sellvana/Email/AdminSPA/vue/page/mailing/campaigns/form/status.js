define(['sv-mixin-form-tab', 'text!sv-page-mailing-campaigns-form-status-tpl'], function (SvMixinFormTab, tpl) {
    return {
        mixins: [SvMixinFormTab],
        template: tpl,
        data: function () {
            return {
                progressTimer: false
            }
        },
        computed: {
            progressPercent: function () {
                var c = this.form.campaign;
                return c.cnt_total ? Math.ceil(c.cnt_sent / c.cnt_total * 100) : 0;
            }
        },
        methods: {
            startCampaign: function () {
                var vm = this;
                this.sendRequest('POST', 'mailing/campaigns/start', {id: this.form.campaign.id}, function (response) {
                    vm.checkProgress();
                });
            },
            pauseCampaign: function () {
                var vm = this;
                this.sendRequest('POST', 'mailing/campaigns/pause', {id: this.form.campaign.id}, function (response) {
                    vm.checkProgress();
                });
            },
            resumeCampaign: function () {
                var vm = this;
                this.sendRequest('POST', 'mailing/campaigns/resume', {id: this.form.campaign.id}, function (response) {
                    vm.checkProgress();
                });
            },
            stopCampaign: function () {
                var vm = this;
                this.sendRequest('POST', 'mailing/campaigns/stop', {id: this.form.campaign.id}, function (response) {
                    vm.checkProgress();
                });
            },
            checkProgress: function () {
                var vm = this;
                this.sendRequest('GET', 'mailing/campaigns/progress', {id: this.form.campaign.id}, function (response) {
                    if (response.ok) {
                        vm.form.campaign = response.form.campaign;
                        if (vm.progressTimer) {
                            vm.progressTimer = setTimeout(vm.checkProgress, 3000);
                        }
                    }
                })
            }
        },
        created: function () {
			this.progressTimer = setTimeout(this.checkProgress, 3000);
        },
        beforeDestroy: function () {
            if (this.progressTimer) {
                clearTimeout(this.progressTimer);
                this.progressTimer = false;
            }
        }
    }
});