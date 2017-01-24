/*
 *
 * Author: Chris 'CJ' Jones (chris.cj.jones@gmail.com)
 * Project: jQuery.fixedTable
 * Date: Wednesday October 09 2013
 * Version: 1.00
 *
 * Example 1 (Individual Table):
 *  jQuery(document).ready(function() {
 *      jQuery('#table_0').fixedTable({
 *          table: {
 *              height: 300,
 *              width: 800
 *          }
 *          
 *      });
 *  });
 *
 * Example 2 (All Tables With Class Name):
 *  jQuery.fixedTableByClassName('fixedTable', {
 *      table: {
 *              height: 300,
 *              width: 800
 *          }
 *          
 *      });
 *  });
 */

/*
 *
 *  The MIT License (MIT)
 *  
 *  Copyright (c) 2013 Chris 'CJ' Jones
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

(function($) {
    $.fixedTableByClassName = function(className, options) {
        options = $.extend({}, $.FixedTable.defaults, options);
        
        $('table').each(function(index, element) {
            if($(element).hasClass(className)) {
                $(element).fixedTable(options)
            }
        });
    }
    
    $.fn.extend({
        fixedTable: function(options) {
            options = $.extend({}, $.FixedTable.defaults, options);
            
            this.each(function() {
                new $.FixedTable(this, options);
            });
            return this;
        }
    });
    
    $.FixedTable = function(element, options) {
        options.table.height = (options.table.height == 0) ? document.scrollHeight : options.table.height;
        options.table.width = (options.table.width == 0) ? document.scrollWidth : options.table.width;
        
        var fixedTableProperties = {};
        
        fixedTableProperties.fixedFirstColumns = {};
        fixedTableProperties.fixedLastColumns = {};
        
        fixedTableProperties.tableBodyCells = {};
        
        fixedTableProperties.fixedFirstColumnCount = 0;
        fixedTableProperties.fixedLastColumnCount = 0;
        
        fixedTableProperties.fixedFirstHeaders = {};
        fixedTableProperties.fixedLastHeaders = {};
        fixedTableProperties.headers = {};
        
        fixedTableProperties.fixedFirstFooters = {};
        fixedTableProperties.fixedLastFooters = {};
        fixedTableProperties.footers= {};
        
        $(element).data('fixedTableProperties', fixedTableProperties);
        
        $.FixedTable.getBody(element);
        
        fixedTableProperties.headers = $.FixedTable.getHeaders(element);
        fixedTableProperties.footers = $.FixedTable.getHeaders(element, 'tfoot');
        
        if(Object.keys(fixedTableProperties.fixedFirstColumns).length > 0) {
            var firstCols = $.FixedTable.createColumns(element);
        }
        
        if(Object.keys(fixedTableProperties.fixedLastColumns).length > 0) {
            var lastCols = $.FixedTable.createColumns(element, 'last');
        }
        
        if(Object.keys(fixedTableProperties.headers).length > 0) {
            var header = $.FixedTable.createHeaders(element, 'header');
        }
        
        if(Object.keys(fixedTableProperties.footers).length > 0) {
            var footer = $.FixedTable.createHeaders(element, 'footer');
        }
        
        var container = $.FixedTable.createOuter(element, options);
        
        var tableContent = document.createElement('div');
        tableContent.id = 'fixed_table_' + element.id + '_table_content';
        tableContent.style.clear = 'both';
        
        if(Object.keys(fixedTableProperties.headers).length > 0)
            container.appendChild(header);
        
        if(Object.keys(fixedTableProperties.fixedFirstColumns).length > 0)
            tableContent.appendChild(firstCols);
        
        var tableBody = $.FixedTable.createBody(element);
        
        tableContent.appendChild(tableBody);
        
        if(Object.keys(fixedTableProperties.fixedLastColumns).length > 0)
            tableContent.appendChild(lastCols);
        
        container.appendChild(tableContent);
        
        if(Object.keys(fixedTableProperties.footers).length > 0)
            container.appendChild(footer);
        
        $(container).insertBefore(element);
        
        var clearElement = document.createElement('div');
        clearElement.style.fontSize = '1px';
        clearElement.style.clear = 'both';
        clearElement.innerHTML = '&nbsp;';
        
        $(clearElement).insertBefore(element);
        
        /* Widths */
        var extraWidth = 0;
        
        if(Object.keys(fixedTableProperties.fixedFirstColumns).length > 0)
            extraWidth = extraWidth + document.getElementById('fixed_table_' + element.id + '_first').scrollWidth;
        
        if(Object.keys(fixedTableProperties.fixedLastColumns).length > 0)
            extraWidth = extraWidth + document.getElementById('fixed_table_' + element.id + '_last').scrollWidth;
        
        var bodyWidth = (options.table.width - extraWidth);
        document.getElementById('fixed_table_' + element.id + '_table_body').style.width = bodyWidth + 'px';
        document.getElementById('fixed_table_' + element.id + '_table_body_table').style.width = element.offsetWidth + 'px';
        
        if(Object.keys(fixedTableProperties.headers).length > 0) {
            document.getElementById('fixed_table_' + element.id + '_header_headers').style.width = bodyWidth + 'px';
            document.getElementById('fixed_table_' + element.id + '_header_headers_table').style.width = element.offsetWidth + 'px';
        }
        
        if(Object.keys(fixedTableProperties.footers).length > 0) {
            document.getElementById('fixed_table_' + element.id + '_footer_headers').style.width = bodyWidth + 'px';
            document.getElementById('fixed_table_' + element.id + '_footer_headers_table').style.width = element.offsetWidth + 'px';
        }
        
        /* Heights */
        var extraHeight = 0;
        
        if(Object.keys(fixedTableProperties.headers).length > 0) 
            extraHeight = extraHeight + document.getElementById('fixed_table_' + element.id + '_header').scrollHeight;
        
        if(Object.keys(fixedTableProperties.footers).length > 0)
            extraHeight = extraHeight + document.getElementById('fixed_table_' + element.id + '_footer').scrollHeight;
        
        var contentHeight = (options.table.height - extraHeight);
        document.getElementById('fixed_table_' + element.id + '_table_content').style.height = contentHeight + 'px';
        document.getElementById('fixed_table_' + element.id + '_table_body').style.height = contentHeight + 'px';
        
        if(Object.keys(fixedTableProperties.fixedFirstColumns).length > 0)
            document.getElementById('fixed_table_' + element.id + '_first').style.height = document.getElementById('fixed_table_' + element.id + '_table_body').clientHeight  + 'px';
        
        if(Object.keys(fixedTableProperties.fixedLastColumns).length > 0)
            document.getElementById('fixed_table_' + element.id + '_last').style.height = document.getElementById('fixed_table_' + element.id + '_table_body').clientHeight  + 'px';
        
        if(document.getElementById('fixed_table_' + element.id + '_table_body_table').offsetHeight > document.getElementById('fixed_table_' + element.id + '_table_body').offsetHeight) {
            var scrollBarSize = (document.getElementById('fixed_table_' + element.id + '_table_body').offsetHeight - document.getElementById('fixed_table_' + element.id + '_table_body').clientHeight);
            
            if(Object.keys(fixedTableProperties.headers).length > 0) {
                document.getElementById('fixed_table_' + element.id + '_header_headers').style.width = (document.getElementById('fixed_table_' + element.id + '_header_headers').offsetWidth - scrollBarSize) + 'px';
                if(Object.keys(fixedTableProperties.fixedLastColumns).length > 0)
                    document.getElementById('fixed_table_' + element.id + '_header_last').style.paddingLeft = scrollBarSize + 'px';
            }
                
            if(Object.keys(fixedTableProperties.footers).length > 0) {
                document.getElementById('fixed_table_' + element.id + '_footer_headers').style.width = (document.getElementById('fixed_table_' + element.id + '_footer_headers').offsetWidth - scrollBarSize) + 'px';
                if(Object.keys(fixedTableProperties.fixedLastColumns).length > 0)
                    document.getElementById('fixed_table_' + element.id + '_footer_last').style.paddingLeft = scrollBarSize + 'px';
            }
        }
        
        $(element).hide();
    }
    
    $.FixedTable.defaults = {
        table: {
            height: 300,
            width: 1000
        }
    }
    
    $.FixedTable.getHeaders = function(element, type) {
        var type = type || 'thead';
        type = type.toLowerCase();
        
        var header = {};
        var headerElement = element.getElementsByTagName(type);
        if(typeof headerElement[0] !== 'undefined') {
            var tr = headerElement[0].getElementsByTagName('tr');
            $(tr).each(function(trIndex, trElement) {
                var th = trElement.getElementsByTagName('th');
                $(th).each(function(thIndex, thElement) {
                    header[trIndex] = header[trIndex] || {};
                    header[trIndex][thIndex] = thElement.innerHTML;
                });
            });
        }
        
        return header;
    }
    
    $.FixedTable.getBody = function(element) {
        var fixedTableProperties = $(element).data('fixedTableProperties');
        var tBody = element.getElementsByTagName('tbody');
        if(typeof tBody[0] !== 'undefined') {
            fixedTableProperties.tableBodyCells = fixedTableProperties.tableBodyCells || {};
            fixedTableProperties.fixedFirstColumns = fixedTableProperties.fixedFirstColumns || {};
            fixedTableProperties.fixedLastColumns = fixedTableProperties.fixedLastColumns || {};
            
            var tr = tBody[0].getElementsByTagName('tr');
            $(tr).each(function(trIndex, trElement) {
                var bodyStarted = false;
                
                var firstIndex = 0;
                var lastIndex = 0;
                var bodyIndex = 0;
                
                $(trElement).children().each(function(index, cell) {
                    var type = $(cell).get(0).tagName.toLowerCase();
                    if(type == 'td') {
                        bodyStarted = true;
                        fixedTableProperties.tableBodyCells[trIndex] = fixedTableProperties.tableBodyCells[trIndex] || {};
                        fixedTableProperties.tableBodyCells[trIndex][bodyIndex] = cell.innerHTML;
                        bodyIndex++;
                    }
                    if(!bodyStarted && type == 'th') {
                        if(trIndex == 0) fixedTableProperties.fixedFirstColumnCount++;
                        fixedTableProperties.fixedFirstColumns[trIndex] = fixedTableProperties.fixedFirstColumns[trIndex] || {};
                        fixedTableProperties.fixedFirstColumns[trIndex][firstIndex] = cell.innerHTML;
                        firstIndex++;
                    }
                    if(bodyStarted && type == 'th') {
                        if(trIndex == 0) fixedTableProperties.fixedLastColumnCount++;
                        fixedTableProperties.fixedLastColumns[trIndex] = fixedTableProperties.fixedLastColumns[trIndex] || {};
                        fixedTableProperties.fixedLastColumns[trIndex][lastIndex] = cell.innerHTML;
                        lastIndex++;
                    }
                });
            });
        }
    }
    
    $.FixedTable.createOuter = function(element, options) {
        var out = document.createElement('div');
        out.id = 'fixed_table_' + element.id + '_container';
        out.style.height = options.table.height + 'px';
        out.style.width = options.table.width + 'px';
        out.style.clear = 'both';
        return out;
    }
    
    $.FixedTable.createHeaders = function(element, type) {
        var fixedTableProperties = $(element).data('fixedTableProperties');
        var type = type || "header";
        type = type.toLowerCase();
        
        var tmpObj = {};
        switch(type) {
            case 'footer':
                tmpObj = fixedTableProperties.footers;
                break;
            case 'header':
            default:
                tmpObj = fixedTableProperties.headers;
                break;
        }
        var startingCell = (Object.keys(tmpObj[0]).length - fixedTableProperties.fixedLastColumnCount);
        
        var container = document.createElement('div');
        container.id = 'fixed_table_' + element.id + '_' + type;
        container.style.overflow = 'hidden';
        container.style.clear = 'both';
        
        if(fixedTableProperties.fixedFirstColumnCount > 0) {
            var fixedFirstColumnHeaders = document.createElement('div');
            fixedFirstColumnHeaders.id = 'fixed_table_' + element.id + '_' + type + '_first';
            fixedFirstColumnHeaders.style.float = 'left';
            var tableHeader = document.createElement('table');
            Object.keys(tmpObj).forEach(function(row_id) {
                var tr = document.createElement('tr');
                Object.keys(tmpObj[row_id]).forEach(function(cell_id) {
                    if(cell_id >= fixedTableProperties.fixedFirstColumnCount) return;
                    var th = document.createElement('th');
                    var cell = tmpObj[row_id][cell_id];
                    th.innerHTML = cell;
                    tr.appendChild(th);
                    delete tmpObj[row_id][cell_id];
                });
                tableHeader.appendChild(tr);
            });
            fixedFirstColumnHeaders.appendChild(tableHeader);
        }
        
        if(fixedTableProperties.fixedLastColumnCount > 0) {
            var fixedLastColumnHeaders = document.createElement('div');
            fixedLastColumnHeaders.id = 'fixed_table_' + element.id + '_' + type + '_last';
            fixedLastColumnHeaders.style.float = 'left';
            var tableFooter = document.createElement('table');
            Object.keys(tmpObj).forEach(function(row_id) {
                var tr = document.createElement('tr');
                Object.keys(tmpObj[row_id]).forEach(function(cell_id) {
                    if(cell_id >= startingCell) {
                        //alert(cell_id)
                        
                        var th = document.createElement('th');
                        var cell = tmpObj[row_id][cell_id];
                        
                        th.innerHTML = cell;
                        tr.appendChild(th);
                        
                        delete tmpObj[row_id][cell_id];
                    }
                });
                tableFooter.appendChild(tr);
            });
            fixedLastColumnHeaders.appendChild(tableFooter);
        }
        
        if(fixedTableProperties.fixedFirstColumnCount > 0)
            container.appendChild(fixedFirstColumnHeaders);
        
        var headerTableContainer = document.createElement('div');
        headerTableContainer.style.overflow = "hidden";
        headerTableContainer.id = 'fixed_table_' + element.id + '_' + type + '_headers';
        headerTableContainer.style.float = 'left';
        
        var headerTable = document.createElement('table');
        headerTable.id = 'fixed_table_' + element.id + '_' + type + '_headers_table';
        
        Object.keys(tmpObj).forEach(function(row_id) {
            var headerTr = document.createElement('tr');
            Object.keys(tmpObj[row_id]).forEach(function(cell_id) {
                var headerTh = document.createElement('th');
                var headerCell = tmpObj[row_id][cell_id];
                headerTh.innerHTML = headerCell;
                headerTr.appendChild(headerTh);
            });
            headerTable.appendChild(headerTr);
        });
        
        headerTableContainer.appendChild(headerTable);
        
        container.appendChild(headerTableContainer);
        if(fixedTableProperties.fixedLastColumnCount > 0)
            container.appendChild(fixedLastColumnHeaders);
        
        return container;
    }
    
    $.FixedTable.createColumns = function(element, type) {
        var fixedTableProperties = $(element).data('fixedTableProperties');
        var type = type || "first";
        type = type.toLowerCase();
        
        var tmpObj = {};
        switch(type) {
            case 'last':
                tmpObj = fixedTableProperties.fixedLastColumns;
                break;
            case 'first':
            default:
                tmpObj = fixedTableProperties.fixedFirstColumns;
                break;
        }
        
        var container = document.createElement('div');
        container.id = 'fixed_table_' + element.id + '_' + type;
        container.style.float = 'left';
        container.style.overflow = 'hidden';
        
        var table = document.createElement('table');
        
        Object.keys(tmpObj).forEach(function(row_id) {
            var columnTr = document.createElement('tr');
            Object.keys(tmpObj[row_id]).forEach(function(cell_id) {
                var columnTh = document.createElement('th');
                var columnCell = tmpObj[row_id][cell_id];
                columnTh.innerHTML = columnCell;
                columnTr.appendChild(columnTh);
            });
            table.appendChild(columnTr);
        });
        
        container.appendChild(table);
        
        return container;
    }
    
    $.FixedTable.createBody = function(element) {
        var fixedTableProperties = $(element).data('fixedTableProperties');
        var container = document.createElement('div');
        container.id = 'fixed_table_' + element.id + '_table_body';
        container.style.float = 'left';
        container.style.overflow = 'auto';
        container.onscroll = function() {
            $.FixedTable.scroll(element)
        }
        
        var table = document.createElement('table');
        table.id = 'fixed_table_' + element.id + '_table_body_table';
        
        Object.keys(fixedTableProperties.tableBodyCells).forEach(function(row_id) {
            var tr = document.createElement('tr');
            Object.keys(fixedTableProperties.tableBodyCells[row_id]).forEach(function(cell_id) {
                var td = document.createElement('td');
                var cell = fixedTableProperties.tableBodyCells[row_id][cell_id];
                td.innerHTML = cell;
                tr.appendChild(td);
            });
            table.appendChild(tr);
        });
        
        container.appendChild(table);
        
        return container;
    }
    
    $.FixedTable.scroll = function(element) {
        var fixedTableProperties = $(element).data('fixedTableProperties');
        
        var bodyScrollLeft = $('#fixed_table_' + element.id + '_table_body').scrollLeft();
        var bodyScrollTop = $('#fixed_table_' + element.id + '_table_body').scrollTop();
        
        if(Object.keys(fixedTableProperties.headers).length > 0) 
            $('#fixed_table_' + element.id + '_header_headers').scrollLeft(bodyScrollLeft);
        
        if(Object.keys(fixedTableProperties.footers).length > 0)
            $('#fixed_table_' + element.id + '_footer_headers').scrollLeft(bodyScrollLeft);
        
        if(Object.keys(fixedTableProperties.fixedFirstColumns).length > 0)
            $('#fixed_table_' + element.id + '_first').scrollTop(bodyScrollTop);
        
        if(Object.keys(fixedTableProperties.fixedLastColumns).length > 0)
            $('#fixed_table_' + element.id + '_last').scrollTop(bodyScrollTop);
    }
})(jQuery);
