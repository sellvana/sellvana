define(['vue', 'text!sv-comp-tree-node-tpl'], function (Vue, treeNodeTpl) {

    Vue.component('sv-comp-tree-node', {
        template: treeNodeTpl,
        props: {
            node: Object,
            curNode: Object,
            treeId: String
        },
        computed: {
            isFolder: function () {
                return this.node.children && this.node.children.length;
            },
            isActive: function () {
                return this.curNode && this.node.id == this.curNode.id;
            }
        },
        methods: {
            toggle: function () {
                if (this.isFolder) {
                    Vue.set(this.node, 'open', !this.node.open);
                    this.$emit('event', {type: 'toggle', node: this.node});
                }
            },
            select: function () {
                this.$emit('event', {type: 'select', node: this.node});
            },
            addChild: function () {
                this.$emit('event', {type: 'addchild', node: this.node});
            },
            dblclick: function () {
                this.$emit('event', {type: 'dblclick', node: this.node});
            },
            proxyEvent: function (event) {
                this.$emit('event', event);
            }
        }
    });

    return {
        props: {
            tree: Object,
            curNode: Object,
            treeId: String
        },
        methods: {
            proxyEvent: function (event) {
                this.$emit('event', event);
            }
        },
        template: '<ul><sv-comp-tree-node class="tree-root tree-node" :node="tree" :cur-node="curNode" :tree-id="treeId" @event="proxyEvent"></sv-comp-tree-node></ul>'
    }
});