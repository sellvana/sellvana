define(['backbone','mariontte','memento'],
    function(Backbone,Marionette,Memento){
        var GeneralInfoModel = Bakcbone.Model.extend({
            //url: "yourbackend"
            initialize: function(){
                var memento = new Backbone.Memento(this);
                _.extend(this, memento);
            },
            defaults : {
                productName : "",
                urlKey : "",
                localSKU : "",
                shortDescription : "",
                longDescription : "",
                netWeight : "",
                shippingWeight : "",
                hideProduct : "No"
            },
            schema: {
                productName :       'Text',
                urlKey :            'Text',
                localSKU:           'Text',
                shortDescription:   'Text',
                longDescription:    'Text',
                netWeight:          'Text',
                shippingWeight:     'Text',
                hideProduct:        { type : 'Select', options :['Yes', 'No']}
            }
        });
        return GeneralInfoModel;
    });
