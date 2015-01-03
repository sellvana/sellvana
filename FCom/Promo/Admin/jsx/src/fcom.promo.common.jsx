define(['react', 'jsx!fcom.components'], function (React, Components) {
    var Common = {
        DelBtn: React.createClass({
            render: function () {
                return (
                    <Components.Button className="btn-link btn-delete" onClick={this.props.onClick}
                        type="button" style={ {paddingRight: 10, paddingLeft: 10} }>
                        <span className="icon-trash"></span>
                    </Components.Button>
                );
            }
        }),
        Row: React.createClass({
            render: function () {
                var cls = "form-group condition";
                if (this.props.rowClass) {
                    cls += " " + this.props.rowClass;
                }
                return (<div className={cls}>
                    <div className="col-md-3">
                        <Components.ControlLabel label_class="pull-right">{this.props.label}
                            <Common.DelBtn onClick={this.props.onDelete}/>
                        </Components.ControlLabel>
                    </div>
                {this.props.children}
                </div>);
            }
        }),
        Compare: React.createClass({
            render: function () {
                return (
                    <select className="to-select2 form-control" onChange={this.props.onChange} id={this.props.id}>
                    {this.props.opts.map(function(type){
                        return <option value={type.id} key={type.id}>{type.label}</option>
                    })}
                    </select>
                );
            },
            getDefaultProps: function () {
                return {
                    opts: [
                        {id:"gt", label: "is greater than"},
                        {id:"gte", label: "is greater than or equal to"},
                        {id:"lt", label: "is less than"},
                        {id:"lte", label: "is less than or equal to"},
                        {id:"eq", label: "is equal to"},
                        {id:"neq", label: "is not equal to"}
                    ]
                };
            }
        }),
        AddFieldButton: React.createClass({
            render: function () {
                return (
                    <Components.Button onClick={this.props.onClick} className="btn-link pull-left" type="button" style={ {paddingRight:10, paddingLeft:10} }>
                        <span aria-hidden="true" className="glyphicon glyphicon glyphicon-plus-sign"></span>
                    </Components.Button>
                );
            }
        }),
        select2QueryMixin: {
            select2query: function (options) {
                var self = this;
                var $el = $(options.element);
                var values = $el.data('searches') || [];
                var flags = $el.data('flags') || {};
                var term = options.term || '*';
                var page = options.page;
                console.log(page);
                var data;
                if (flags[term] != undefined && flags[term].loaded == 2) {
                    data = {results: self.searchLocal(term, values, page, 100), more: (flags[term].page > page)};
                    options.callback(data);
                } else {
                    this.search({term: term, page: page, searchedTerms: flags}, this.url, function (result, params) {
                        var more;
                        if (result == 'local') {
                            more = (params.searchedTerms[term].page > params.page) || (params.searchedTerms[term].loaded == 1);
                            data = {results: self.searchLocal(params.term, values, params.page, params.o), more: more};
                            options.callback(data);
                        } else if (result.items !== undefined) {
                            more = params.searchedTerms[term].loaded === 1;
                            data = {results: result.items, more: more};
                            flags[term] = params.searchedTerms[term];
                            values = self.mergeResults(values, data.results, function (item, bitSet) {
                                var inSet = true;
                                if (!bitSet[item.id]) {
                                    inSet = false;
                                    bitSet[item.id] = 1;
                                }
                                return inSet;
                            });
                            $el.data({searches: values, flags: flags});

                            options.callback(data);
                        }
                    })
                }
            },
            mergeResults: function () {
                var result = [], bitSet = {}, arr, len;
                var checker = arguments[arguments.length - 1]; // function to check if item is in set
                if(!$.isFunction(checker)) {
                    throw "Last argument must be a function.";
                }
                for(var i = 0; i < (arguments.length - 1); i++){
                    arr = arguments[i];
                    if(!arr instanceof Array) {
                        continue;
                    }
                    len = arr.length;
                    while (len--) {
                        var itm = arr[len];
                        if (!checker(itm, bitSet)) {
                            result.unshift(itm);
                        }
                    }
                }
                return result;
            },
            search: function (params, url, callback) {
                params.q = params.term || '*'; // '*' means default search
                params.page = params.page || 1;
                params.o = params.limit || 100;

                params.searchedTerms = params.searchedTerms || {};
                if(params.searchedTerms['*'] && params.searchedTerms['*'].loaded == 2) {
                    // if default search already returned all results, no need to go back to server
                    params.searchedTerms[params.term] = params.searchedTerms['*'];
                }
                var termStatus = params.searchedTerms[params.term];
                if (termStatus == undefined || (termStatus.loaded == 1 && termStatus.page < params.page)) { // if this is first load, or there are more pages and we're looking for next page
                    if (termStatus == undefined) {
                        params.searchedTerms[params.term] = {};
                    }
                    $.get(url, {page: params.page, q: params.q, o: params.o})
                        .done(function (result) {
                            if (result.hasOwnProperty('total_count')) {
                                console.log(result['total_count']);
                                var more = params.page * params.o < result['total_count'];
                                params.searchedTerms[params.term].loaded = (more) ? 1 : 2; // 1 means more results to be fetched, 2 means all fetched
                                params.searchedTerms[params.term].page = params.page; // 1 means more results to be fetched, 2 means all fetched
                            }
                            callback(result, params);
                        })
                        .fail(function (result) {
                            callback(result, params);
                        });
                } else if (termStatus.loaded == 2 || (termStatus.page >= params.page)) {
                    callback('local', params); // find results from local storage
                } else {
                    console.error("UNKNOWN search status.")
                }
            },
            searchLocal: function (term, values, page, limit) {
                page = page || 1;
                limit = limit || 100;
                var counted = 0;
                var offset = (page - 1) * limit; // offset from which to start fetching results
                var max = offset + limit;
                var regex;
                if (term != '*') { // * is match all, don't try to search
                    regex = new RegExp(term, 'i');
                }
                var matches = $.grep(values, function (val) {
                    if (counted >= max) { // if already reached goal, don't add any more matches
                        return false;
                    }

                    var test;
                    if (regex) {
                        test = regex.test(val['text']); // if regex and it matches a term
                        if (!test && val.hasOwnProperty('sku')) {
                            test = regex.test(val['sku']);
                        }
                        if (test) {
//                                    console.log(term + ' matches ' + val.text);
                            counted++; // up the counter
                        }
                    } else {
                        counted++; // no regex, just return matching items by position
                        test = true;
                    }
                    return test && counted >= offset && counted < max;// if term is not for this page, skip it
                });
                return matches;
            }
        },
        removeMixin: {
            remove: function () {
                if (this.props.removeAction) {
                    this.props.removeAction(this.props.id);
                } else if (this.props.removeCondition) {
                    this.props.removeCondition(this.props.id);
                }
            }
        }
    };
    return Common;
});
