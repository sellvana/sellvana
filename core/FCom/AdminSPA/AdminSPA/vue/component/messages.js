define(['sv-hlp'], function (SvHlp) {
    return {
        mixins: [SvHlp.mixins.common],
        template: '<div class="messages-block"><div class="messages-container">'
            + '<div v-for="m in messages" class="message" :class="m.type + \'-message\'" @click="closeMessage(m)">'
                + '<span v-html="m.text"></span>'
                + '<a href="#" class="remove" @click.prevent="closeMessage(m)"><i class="fa fa-times"></i></a>'
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
    }
});