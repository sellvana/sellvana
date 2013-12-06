define(['jquery', 'backbone', 'dynatree', 'fcom.admin', 'jquery.contextmenu'], function ($, Backbone) {
    var $contextMenu = $('<ul id="context-menu" class="contextMenu">');
    $.each(['create', 'test'], function (i, v) {
        $contextMenu.append('<li><a href="#' + v + '">' + v + '</a></li>');
    });


    function bindContextMenu(span) {
        // Add context menu to this node:
        $(span).contextMenu({
            menu: 'context-menu'
        }, function (action, el, pos) {
            // The event was bound to the <span> tag, but the node object
            // is stored in the parent <li> tag
            var node = $.ui.dynatree.getNode(el);
            switch (action) {
                default:
                    alert("Todo: appply action '" + action + "' to node " + node);
            }
        });
    }

    FCom.Admin.DynaTree = Backbone.View.extend({
        initialize: function () {
            var self = this,
                options = this.options;
            this.setElement(options.el);

            this.treeOptions = $.extend({
                initAjax: {
                    url: options.url
                },
                onCreate: function (node, span) {
                    //bindContextMenu(span);
                },
                onClick: function (node, event) {
                    // Close menu on click
                    if ($(".contextMenu:visible").length > 0) {
                        $(".contextMenu").hide();
                        //          return false;
                    }
                },
                onLazyRead: function (node) {
                    node.appendAjax({
                        url: options.url,
                        data: {
                            key: node.data.key,
                            mode: 'all'
                        }
                    })
                },
                onActivate: function (node) {
                    self.$el.trigger('tree:activate', {
                        node: node
                    });
                },
                dnd: {
                    autoExpandMS: 500,
                    preventVoidMoves: true,
                    onDragStart: function (node) {
                        return true;
                    },
                    onDragEnter: function (node, sourceNode, hitMode, ui, draggable) {
                        if (node.isDescendantOf(sourceNode)) {
                            return false;
                        }
                        // Prohibit creating childs in non-folders (only sorting allowed)
                        //if( !node.data.isFolder && hitMode === "over" ){
                        //    return ["after"];
                        //}
                        //return ["before", "after"];
                        return true;
                    },
                    onDrop: function (node, sourceNode, hitMode, ui, draggable) {
                        sourceNode.move(node, hitMode);
                        sourceNode.expand(true);
                        var childKeys = [];
                        if (sourceNode.hasChildren()) {
                            $.each(sourceNode.getChildren(), function (i, n) {
                                childKeys.push(n.key);
                            });
                        }
                        var postData = {
                            node: node.key,
                            newParent: sourceNode.key,
                            siblings: childKeys
                        };
                        console.log(postData);
                        $.post(options.url, postData, function (response, status, xhr) {
                            console.log('tree updated');
                        });
                    }
                }
            }, options.tree);

        },

        render: function () {
            this.$el.dynatree(this.treeOptions);
            this.tree = this.$el.dynatree('getTree');
        }
    });

    return FCom.Admin.DynaTree;
});
