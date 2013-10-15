define(['jquery', 'underscore', 'backbone', 'fcom.pushclient', 'exports','fcom.adminchat','slimscroll'], function($, _, Backbone, PushClient, exports,AdminChat,slimscroll)
{
    var setScrollable = function(selector) {
    if (selector == null) {
      selector = $(".scrollable");
    }
    if (jQuery().slimScroll) {
      return selector.each(function(i, elem) {
        return $(elem).slimScroll({
          height: $(elem).data("scrollable-height"),
          start: $(elem).data("scrollable-start") || "top"
        });
      });
    }
  };



    ChatUserList = {
        Models: {},
        Collections: {},
        Views: {}
    }

    //User Model
    ChatUserList.Models.User = Backbone.Model.extend({
    });

    //A List of Users
    ChatUserList.Collections.Users = Backbone.Collection.extend({
        model: ChatUserList.Models.User
    });

    //View for all user list
    ChatUserList.Views.Users = Backbone.View.extend({
        el: '#adminUserList',
        render: function(){
            this.collection.each(this.addOne, this);
            return this;
        },
        addOne: function(user)
        {
            var userView = new ChatUserList.Views.User({model: user});
            this.$el.append(userView.render().el);
        }
    });

    //View for a user
    ChatUserList.Views.User = Backbone.View.extend({
        tagName: 'li',
        template: _.template($('#userTemplate').html()),
        events: {
            'click' :'initChat'
        },
        initChat: function(){
            AdminChat.start({user: this.model.get('username')});
        },
        render: function(){
            this.$el.html(this.template(this.model.toJSON()));
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
    });

    //A List of items
    ChatWindows.Collections.Items = Backbone.Collection.extend({
        model: ChatWindows.Models.Item
    });

    //Chat window model(for a single window)
    ChatWindows.Models.Win = Backbone.Model.extend({
        defaults: {
            index: 0
        }
    });

    //A List of windows
    ChatWindows.Collections.Wins = Backbone.Collection.extend({
        model: ChatWindows.Models.Win
    });


    //view for multipul chat windows(when user create several chat sessions)
    ChatWindows.Views.Main = Backbone.View.extend({
        el: '#adminChatMain',
        initialize: function(){
            this.collection.on('add', this.addOne, this);
        },
        render: function(){
            this.collection.each(this.addOne, this);
            return this;
        },
        addOne: function(win)
        {
            var chatItems = new ChatWindows.Collections.Items([]);
            var chatWin = new ChatWindows.Views.Window({model:win,collection: chatItems});
            loadedWins[loadedWins.length]={channel:win.attributes.channel, win:chatWin};
            this.$el.append(chatWin.render().el);
        }
    })

    //view for chat window
    ChatWindows.Views.Window = Backbone.View.extend({

        template: _.template($('#chatWinTemplate').html()),
        initialize: function(){
            this.collection.on('add', this.addOne, this);
        },
        events: {
            'click .btn.box-collapse' :'toggleChatWin',
            'click .btn.box-remove' :'closeChatWin',
            'submit' :'say'
        },
        toggleChatWin: function(e){
            alert('abc');
            var box = this.$el.find(".box");
            box.toggleClass("box-collapsed");
            e.preventDefault();
            return false;
        },
        closeChatWin: function(){
            leave({channel: this.model.get('channel')});

            this.undelegateEvents();
            this.$el.removeData().unbind();
            this.remove();
            this.model.destroy();
            Backbone.View.prototype.remove.call(this);
            console.log(chatWins);
        },
        say: function(ev)
        {
            ev.preventDefault();
            say({channel:this.model.get('channel'), text:this.$el.find('#message_body').val()});
            this.$el.find('#message_body').val('');
        },
        render: function(){
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        },
        addOne: function(item)
        {
            var chatItem = new ChatWindows.Views.Item({model: item});
            console.log(chatItem.render().el);
            this.$el.find('ul:first').append(chatItem.render().el);

            scrollable = this.$el.find(".scrollable");
            $(scrollable).slimScroll({
                scrollTo: scrollable.prop('scrollHeight') + "px"
            });
            this.$el.find('ul li:last').effect("highlight", {}, 500);

        }
    });

    //view for single chat line(item)
    ChatWindows.Views.Item = Backbone.View.extend({
        template: _.template($('#chatItemTempate').html()),
        tagName: 'li',
        className: 'message',
        render: function(){
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        }
    });

    var chatWins = new ChatWindows.Collections.Wins([]), username;
    var chatMainWin = new ChatWindows.Views.Main({collection:chatWins});
    var loadedWins=[];
    function init(options) {
        username = options.username;
        PushClient.send({channel:'adminuser', signal:'subscribe'});
        PushClient.send({channel:'adminuser', signal:'init'})
        PushClient.send({channel:'adminchat', signal:'init'})
        status({status:options.status})
    }

    // send to server
    function status(options) {
        PushClient.send({channel:'adminuser', signal:'status', status:options.status});
    }

    function start(options) {
        PushClient.send({channel:'adminchat', signal:'start', user:options.user});
    }

    function invite(options) {
        PushClient.send({channel:'adminchat', signal:'invite', user:options.user});
    }

    function say(options) {
        //add message to dom
        PushClient.send({channel:options.channel, signal:'say', text:options.text});
    }

    function leave(options) {
        PushClient.send({channel:options.channel, signal:'leave'});
        //close_window(options.channel);
    }


    function show_window(chat)
    {
        if(_.contains(_.pluck(loadedWins,'channel'), chat.channel))
            return;

        chat.index=loadedWins.length;
        var chatWin= new ChatWindows.Models.Win(chat);
        chatWins.add(chatWin);

    }

    function close_window(channel)
    {
        chatWindows[channel].$container.remove();
        delete chatWindows[channel];
    }

    function add_history(msg)
    {
        console.log(loadedWins);
        console.log(msg);
        var json=_.where(loadedWins,{channel:msg.channel});
        if(json[0])
        {
            var chatItem= new ChatWindows.Models.Item(msg);
            json[0].win.collection.add(chatItem);
        }

        /*var $h = chatWindows[msg.channel].$history, h = $h.get(0);
        $h.append($('<div>').html(msg.text));
        h.scrollTop = h.scrollHeight;*/
    }

    PushClient.listen({ channel: 'adminuser', callback: channel_adminuser });

    // receive from server
    PushClient.listen({ channel: 'adminchat', callback: channel_adminchat });
    PushClient.listen({ regexp: /^adminchat:(.*)$/, callback: channel_adminchat });

    function channel_adminuser(msg)
    {
        if (channel_adminuser.signals[msg.signal]) {
            channel_adminuser.signals[msg.signal](msg);
        }
    }
    channel_adminuser.signals = {
        status: function(msg) {

            var users = new ChatUserList.Collections.Users(msg.users);
            var userView = new ChatUserList.Views.Users({collection: users});
            userView.render();
            _.each(msg.users, function(user) {
                if (user.username === username) {
                    return;
                }
                if (!msg.init) {
                    $.bootstrapGrowl(user.username+' is '+user.status, { type:'success', align:'center', width:'auto' });
                }
            })
        }
    }

    function channel_adminchat(msg)
    {
        if (channel_adminchat.signals[msg.signal]) {
            channel_adminchat.signals[msg.signal](msg);
        }
    }
    channel_adminchat.signals = {
        chats: function(msg) {
            _.each(msg.chats, function(chat) {
                show_window(chat);
            })
        },
        start: function(msg) {
            show_window({channel:msg.channel});
        },
        say: function(msg) {

            show_window({channel:msg.channel});
            msg.username=curUser;
            add_history(msg);
        },
        join: function(msg) {
            show_window({channel:msg.channel});
            add_history({channel:msg.channel, text: 'joined', username:msg.username});
        },
        leave: function(msg) {
            show_window({channel:msg.channel});
            add_history({channel:msg.channel, text: ' left', username:msg.username});
        },
        close: function(msg) {
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

