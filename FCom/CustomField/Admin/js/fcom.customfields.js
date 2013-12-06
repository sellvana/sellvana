define(['jquery', 'underscore', 'backbone', 'marionette'], function ($, _, Backbone, Marionette) {

    var config = {};

    function CustomFields(options) {
        _.extend(config, options);
        console.log('test');

        var fieldsCollection = new CustomFields.Collections.Fields([
            {field_name: 'TEST', value: 'VALUE'}
        ]);

        var fieldsView = new CustomFields.Views.Fields({
            collection: fieldsCollection
        });
    }

    _.extend(CustomFields, {
        Models: {},
        Collections: {},
        Views: {}
    });

    CustomFields.Models.Field = Backbone.Model.extend({
        initialize: function () {

        }
    });

    CustomFields.Collections.Fields = Backbone.Collection.extend({
        model: CustomFields.Models.Field
    });

    CustomFields.Views.Field = Backbone.Marionette.ItemView.extend({
        template: '#products-customfield-template',
        tagName: 'li',
        ui: {
            value: '.value'
        },
        events: {
            'click .destroy': 'destroy'
        },
        modelEvents: {
            'change': 'render'
        },
        onRender: function () {

        },
        destroy: function () {
            this.model.destroy();
        }
    });

    CustomFields.Views.Fields = Backbone.Marionette.CompositeView.extend({
        el: '#product-edit-customfields',
        itemView: CustomFields.Views.Field,
        itemViewContainer: '#products-customfield-fields',
        ui: {
            availSets: '#product-edit-available-sets',
            //addSet: '#product-edit-add-set',
            availFields: '#product-edit-available-fields'
            //addField: '#product-edit-add-field',
        },
        events: {
            'click #product-edit-add-set': 'onSetAdd',
            'click #product-edit-add-field': 'onFieldAdd'
        },
        collectionEvents: {
            'all': 'update'
        },
        onRender: function () {
            console.log('onRender');
            this.update();
        },
        update: function () {
            console.log('update');
        },
        onSetAdd: function () {
            console.log(this.availSets.val())
        },
        onFieldAdd: function () {
            console.log(this.availFields.val())
        }
    });

    return CustomFields;
});
