define(['vue', 'sv-hlp'], function (Vue, SvHlp) {
    var SvCompMessages = {
        mixins: [SvHlp.mixins.common],
        template: '<div class="notifications-block"><div class="notifications-block__container">'
            + '<div v-for="m in messages" class="notifications-block__text" :class="m.type + \'-notification\'" @click="closeMessage(m)">'
                + '<span v-html="m.text"></span>'
                + '<a href="#" class="notifications-block__remove" @click.prevent="closeMessage(m)"><i class="fa fa-times"></i></a>'
            + '</div></div></div>',
        computed: {
            messages: function () {
                return this.$store.state.messages;
            }
        },
        methods: {
            closeMessage: function (m) {
                this.$store.commit('removeMessage', m);
            }
        }
    };

    Vue.component('sv-comp-messages', SvCompMessages);

    return SvCompMessages;
});