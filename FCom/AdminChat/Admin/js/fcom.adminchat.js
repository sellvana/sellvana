define(['jquery', 'underscore', 'backbone', 'fcom.pushclient', 'exports', 'fcom.adminchat', 'slimscroll', 'timeago'], function ($, _, Backbone, PushClient, exports, AdminChat, slimscroll, timeago) {
    function playDing() {
        
        document.getElementById("sound").innerHTML = '<audio autoplay="autoplay"><source src="' + dingPath + '" type="audio/wav" /><embed hidden="true" autostart="true" loop="false" src="' + dingPath + '" /></audio>';
    }

    var setScrollable = function (selector) {
        if (selector == null) {
            selector = $(".scrollable");
        }
        if (jQuery().slimScroll) {
            return selector.each(function (i, elem) {
                return $(elem).slimScroll({
                    height: $(elem).data("scrollable-height"),
                    start: $(elem).data("scrollable-start") || "top"
                });
            });
        }
    };

    var setTimeAgo = function (selector) {
        if (selector == null) {
            selector = $(".timeago");
        }
        if (jQuery().timeago) {
            jQuery.timeago.settings.allowFuture = true;
            jQuery.timeago.settings.refreshMillis = 60000;
            selector.timeago();

            return selector.addClass("in");
        }
    };

    var username = '';

    ChatUserList = {
        Models: {},
        Collections: {},
        Views: {}
    }

    //User Model
    ChatUserList.Models.User = Backbone.Model.extend({
        defaults: {
            unreadCount: 0
        },
        incUnread: function () {
            this.set('unreadCount', this.get('unreadCount')+1);
        },
        decUnread: function () {
            this.set('unreadCount', this.get('unreadCount')-1);
        }

    });

    //A List of Users
    ChatUserList.Collections.Users = Backbone.Collection.extend({
        model: ChatUserList.Models.User,        
        findModelByName: function (username) {
            for (var i=0;i<this.models.length;i++) {
                if (this.models[i].get('username') === username) {                                        
                    return this.models[i];
                }
            }                

            return false;            
        }
    });

    //View for a current login user status 
    ChatUserList.Views.Status = Backbone.View.extend({
        el: 'li#adminStatus',
        template: _.template($('#statusTemplate').html()),
        events: {
            'change #adminuser-status': 'changeStatus',
            'click #adminuser-status': 'preventDefault'
        },
        initialize: function () {
            this.model.on('change', this.render, this);
        },
        render: function () {
            this.$el.html(this.template(this.model.toJSON()));
            this.$el.find('#adminuser-status').val(this.model.get('status'));

            return this;
        },
        changeStatus: function (ev) {
            var status = this.$el.find('select#adminuser-status:first').val();
            AdminChat.status({
                status: status
            });

            return true;
        },
        preventDefault: function (ev) {

            ev.stopPropagation();
        }
    });
    //View for all user list
    ChatUserList.Views.Users = Backbone.View.extend({
        el: '#adminUserList',
        initialize: function () {
            this.collection.on('add', this.addOne, this);
            this.collection.on('change', this.updateUnread, this);
        },
        render: function () {
            this.collection.each(this.addOne, this);

            return this;
        },
        addOne: function (user) {
            var userView = new ChatUserList.Views.User({
                model: user
            });
            this.$el.append(userView.render().el);
        },
        updateUnread: function() {
            var total = 0;
            
            this.collection.each(function(userModel){
                total += userModel.get('unreadCount');
            });

            if (total>0) {
                $('span#totalUnreads').css('display', 'inline');
                $('span#totalUnreads').html(total);
            } else {
                $('span#totalUnreads').css('display', 'none');
            }
        }
    });

    //View for a user
    ChatUserList.Views.User = Backbone.View.extend({
        tagName: 'li',
        template: _.template($('#userTemplate').html()),
        initialize: function () {
            this.model.on('change', this.render, this);
        },
        events: {
            'click': 'initChat'
        },
        initChat: function () {
            AdminChat.start({
                user: this.model.get('username')
            });
        },
        render: function () {            
            this.$el.html(this.template(this.model.toJSON()));
            if (this.model.get('unreadCount')>0)
            {
                var html = this.model.get('unreadCount') + ' unread';
                if (this.model.get('unreadCount')>1)
                    html += 's';

                this.$el.find('span.badge').css('display', 'inline');
                this.$el.find('span.badge').html(html);
            } else {
                this.$el.find('span.badge').css('display', 'none');
            }
            return this;
        }
    });



    ChatWindows = {
        Models: {},
        Collections: {},
        Views: {}
    }

    //Chat item model
    ChatWindows.Models.Item = Backbone.Model.extend({
        defaults: {
            unread: false,
        }
    });

    //A List of items
    ChatWindows.Collections.Items = Backbone.Collection.extend({
        model: ChatWindows.Models.Item
    });

    //Chat window model(for a single window)
    ChatWindows.Models.Win = Backbone.Model.extend({
        defaults: {
            index: 0,
            collapsed: false,
            unreadCount: 0,
            badgeDisplay: 'none'
        }
    });

    //A List of windows
    ChatWindows.Collections.Wins = Backbone.Collection.extend({
        model: ChatWindows.Models.Win
    });


    //view for multipul chat windows(when user create several chat sessions)
    ChatWindows.Views.Main = Backbone.View.extend({
        el: '#adminChatMain',
        initialize: function () {
            this.collection.on('add', this.addOne, this);
            this.collection.on('remove', this.updatePosition, this)
        },
        updatePosition: function () {
            var index = 0;
            _.each(this.collection.models, function (model) {
                model.set('index', index);
                index++;
            });
        },
        render: function () {
            this.collection.each(this.addOne, this);

            return this;
        },
        addOne: function (win) {
            var chatItems = new ChatWindows.Collections.Items([]);
            var chatWin = new ChatWindows.Views.Window({
                model: win,
                collection: chatItems
            });
            loadedWins[loadedWins.length] = {
                channel: win.attributes.channel,
                win: chatWin
            };
            this.$el.append(chatWin.render().el);
        }
    })

    //view for chat window
    ChatWindows.Views.Window = Backbone.View.extend({

        template: _.template($('#chatWinTemplate').html()),
        initialize: function () {
            this.collection.on('add', this.addOne, this);
            this.model.on('change', this.updateHeader, this);
        },
        events: {
            'click .btn.box-collapse': 'toggleChatWin',
            'click .btn.box-remove': 'closeChatWin',
            'submit': 'say'
        },
        toggleChatWin: function (e) {
            var box = this.$el.find(".box");

            box.toggleClass("box-collapsed");
            this.model.set('collapsed', box.hasClass("box-collapsed"));

            if (!this.model.get('collapsed')) {
                this.collection.each(function (item) {
                    if (item.get('unread')) {
                        item.set('unread', false);
                        this.addOne(item);

                        var userModel = userView.collection.findModelByName(item.get('username'));
                        console.log(userModel);
                        if (userModel!==false) {
                            userModel.decUnread();    
                        }
                        
                    }
                }, this);
                this.model.set('unreadCount', 0);
                this.model.set('badgeDisplay', 'none');
                notifyStatus({
                    channel: this.model.get('channel'),
                    status: 'open'
                });

                
            } else {
                notifyStatus({
                    channel: this.model.get('channel'),
                    status: 'minize'
                });
            }
            e.preventDefault();

            return false;
        },
        updateHeader: function () {
            this.$el.find("div.chat.chat-fixed").css('margin-right', (this.model.get('index') * 300) + 'px');

            this.$el.find("span.badge").css('display', this.model.get('badgeDisplay'));
            if (this.model.get('collapsed')) {
                var badgeText = this.model.get('unreadCount') + '&nbsp;unread';
                if (this.model.get('unreadCount') > 1) {
                    badgeText += "s";
                }
                this.$el.find("span.badge").html(badgeText);
            }

        },
        closeChatWin: function () {

            notifyStatus({
                channel: this.model.get('channel'),
                status: 'close'
            });

            loadedWins = _.reject(loadedWins, function (obj) {
                return obj.channel === this.model.get('channel');
            }, this);


            this.undelegateEvents();
            this.$el.removeData().unbind();
            this.remove();
            this.model.destroy();
            Backbone.View.prototype.remove.call(this);
            console.log(loadedWins);
        },
        say: function (ev) {
            ev.preventDefault();
            var msg_id = 'id' + (new Date()).getTime();
            var msg_item = new ChatWindows.Models.Item({
                channel: this.model.get('channel'),
                username: username,
                text: this.$el.find('#message_body').val(),
                msg_id: msg_id,
                time: -1
            });
            this.collection.add(msg_item);
            say({
                channel: this.model.get('channel'),
                text: this.$el.find('#message_body').val(),
                msg_id: msg_id
            });
            this.$el.find('#message_body').val('');
        },
        render: function () {
            this.$el.html(this.template(this.model.toJSON()));
            this.collection.each(this.addOne, this);

            if (this.model.get('collapsed')) {
                this.$el.find('div.box').addClass('box-collapsed');
            }

            return this;
        },
        addOne: function (item) {
            if (!item.get('unread')) {
                //this.$el.find('div.box.box-collapsed').removeClass('box-collapsed');

                var chatItem = new ChatWindows.Views.Item({
                    model: item
                });
                
                this.$el.find('ul:first').append(chatItem.render().el);

                scrollable = this.$el.find(".scrollable");
                $(scrollable).slimScroll({
                    scrollTo: scrollable.prop('scrollHeight') + "px"
                });

                this.$el.find('ul li:last').effect("highlight", {}, 500);
                playDing();
            }

        },
        messageSent: function (msg) {
            var itemModel = this.collection.findWhere({
                msg_id: msg.msg_id
            });

            if (itemModel) {
                itemModel.set('time', msg.time);

                return true;
            } else
                return false;
        }

    });

    months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    //view for single chat line(item)
    ChatWindows.Views.Item = Backbone.View.extend({
        template: _.template($('#chatItemTempate').html()),
        tagName: 'li',
        className: 'message',
        initialize: function () {
            this.model.on('change', this.render, this);
        },
        render: function () {
            this.$el.html(this.template(this.model.toJSON()));
            if (this.model.get('time') !== 'undefined' && this.model.get('time') !== -1) {
                var date = new Date();
                timeago = this.$el.find(".timeago");
                timeago.attr('title', this.model.get('time'));
                timeago.html("" + months[date.getMonth()] + " " + (date.getDate()) + ", " + (date.getFullYear()) + " " + (date.getHours()) + ":" + (date.getMinutes()));
                setTimeAgo(timeago);
            }

            if (this.model.get('time') === -1) {
                this.$el.find('i').attr('class', 'icon-spinner');
            }

            return this;
        }
    });

    var chatWins = new ChatWindows.Collections.Wins([]),
        username;
    var chatMainWin = new ChatWindows.Views.Main({
        collection: chatWins
    });
    var loadedWins = [];

    function init(options) {
        console.log(options);
        username = options.username;
        PushClient.send({
            channel: 'adminuser',
            signal: 'subscribe'
        });
        PushClient.send({
            channel: 'adminuser',
            signal: 'init'
        })
        PushClient.send({
            channel: 'adminchat',
            signal: 'init'
        })
        status({
            status: options.status
        })
    }

    // send to server
    function status(options) {
        PushClient.send({
            channel: 'adminuser',
            signal: 'status',
            status: options.status
        });
    }

    function start(options) {
        PushClient.send({
            channel: 'adminchat',
            signal: 'start',
            user: options.user
        });
    }

    function invite(options) {
        PushClient.send({
            channel: 'adminchat',
            signal: 'invite',
            user: options.user
        });
    }

    function say(options) {
        //add message to dom
        PushClient.send({
            channel: options.channel,
            signal: 'say',
            text: options.text,
            msg_id: options.msg_id
        });
    }

    function notifyStatus(options) {
        PushClient.send({
            channel: options.channel,
            signal: 'notify',
            status: options.status
        });
    }

    function leave(options) {
        PushClient.send({
            channel: options.channel,
            signal: 'leave'
        });
        //close_window(options.channel);
    }


    function show_window(chat) {
        if (chat.status && chat.status === 'close') {
            return;        
        }

        if (_.contains(_.pluck(loadedWins, 'channel'), chat.channel)) {
            return;
        }

        chat.index = loadedWins.length;
        var chatModel = new ChatWindows.Models.Win(chat);

        if (chat.status && chat.status === 'minize') {
            chatModel.set('collapsed', true);
        }

        chatWins.add(chatModel);
    }

    function close_window(channel) {
        chatWindows[channel].$container.remove();
        delete chatWindows[channel];
    }

    function add_history(msg, checkUnread) {        
        checkUnread = typeof checkUnread !== 'undefined' ? checkUnread : true;
        
        var json = _.where(loadedWins, {
            channel: msg.channel
        });
        if (json[0])
            if (!(msg.msg_id && json[0].win.messageSent(msg))) {
                var winView = json[0].win;
                var chatItem = new ChatWindows.Models.Item(msg);

                if (checkUnread && winView.model.get('collapsed')) {
                    winView.model.set('unreadCount', winView.model.get('unreadCount') + 1);
                    winView.model.set('badgeDisplay', 'inline');
                    chatItem.set('unread', true);

                    var userModel = userView.collection.findModelByName(msg.username);                    
                    if (userModel!==false) {
                        userModel.incUnread();
                    }
                }
                json[0].win.collection.add(chatItem);
            }
    }

    PushClient.listen({
        channel: 'adminuser',
        callback: channel_adminuser
    });

    // receive from server
    PushClient.listen({
        channel: 'adminchat',
        callback: channel_adminchat
    });
    PushClient.listen({
        regexp: /^adminchat:(.*)$/,
        callback: channel_adminchat
    });

    function channel_adminuser(msg) {
        if (channel_adminuser.signals[msg.signal]) {
            channel_adminuser.signals[msg.signal](msg);
        }
    }
    var users = new ChatUserList.Collections.Users([]);
    var userView = new ChatUserList.Views.Users({
        collection: users
    });

    var statusModel = new ChatUserList.Models.User({
        username: username,
        status: 'online'
    });
    var statusView = new ChatUserList.Views.Status({
        model: statusModel
    });
    statusView.render();

    channel_adminuser.signals = {
        status: function (msg) {
            _.each(msg.users, function (user) {

                if (user.username === username) {
                    statusModel.set('status', user.status);

                    return;
                }
                var temps = users.where({
                    username: user.username
                });
                if (temps.length > 0) {
                    temps[0].set("status", user.status);
                } else {
                    users.add(user);
                }

                if (!msg.init) {
                    $.bootstrapGrowl(user.username + ' is ' + user.status, {
                        type: 'success',
                        align: 'center',
                        width: 'auto'
                    });
                }
            });
        }
    }

    function channel_adminchat(msg) {
        if (channel_adminchat.signals[msg.signal]) {
            channel_adminchat.signals[msg.signal](msg);
        }
    }
    channel_adminchat.signals = {
        chats: function (msg) {
            _.each(msg.chats, function (chat) {
                show_window(chat);
                _.each(chat.history, function (history) {
                    history.channel = chat.channel;
                    add_history(history, false);
                });
            })
        },
        start: function (msg) {
            
            show_window({
                channel: msg.channel
            });
        },
        say: function (msg) {

            show_window({
                channel: msg.channel
            });
            add_history(msg);
        },
        join: function (msg) {
            show_window({
                channel: msg.channel
            });
            add_history({
                channel: msg.channel,
                text: 'joined',
                username: msg.username
            });
        },
        leave: function (msg) {
            show_window({
                channel: msg.channel
            });
            add_history({
                channel: msg.channel,
                text: ' left',
                username: msg.username
            });
        },
        close: function (msg) {
            //close_window(msg.channel);
        }
    }

    _.extend(exports, {
        init: init,
        status: status,
        start: start,
        invite: invite,
        say: say,
        leave: leave
    });
});