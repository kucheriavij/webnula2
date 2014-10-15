(function($){
    var originalPos = null;

    var fixHelperDimensions = function(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    };

    /**
     * Returns the key values of the currently checked rows.
     * @param id string the ID of the grid view container
     * @param action string the action URL for save sortable rows
     * @param column_id string the ID of the column
     * @param data string the custom POST data
     * @return array the key values of the currently checked rows.
     */
    $.fn.yiiGridView.sortable = function (id, callback, data)
    {
        if (data == null)
            data = {};

        var originalData = $.extend(true, {}, data);
        var grid = $('#'+id) ;
        $("tbody", grid).sortable({
            items: 'tr',
            handle: 'a.handler',
            cursor: 'move',
            revert: true,
            helper: fixHelperDimensions,
            update: function(e,ui){
                // update keys
                var tr = $(ui.item);
                var next = $(ui.item).next().attr('id');
                var prev = $(ui.item).prev().attr('id');

                var position = 'before';

                if (next === undefined){
                    if (prev === undefined) return;

                    next = prev;
                    position = 'after';
                }
                data['id'] = tr.attr('id');
                data['position'] = position;
                data['next'] = next;

                var settings = $.fn.yiiGridView.settings[id];
                $.fn.yiiGridView.update(id,
                {
                    type:'POST',
                    url:tr.attr('href'),
                    data:data,
                    success:function(data){
                        var $data = $('<div>' + data + '</div>');

                        $.each(settings.ajaxUpdate, function (i, el) {
                            var updateId = '#' + el;
                            $(updateId).replaceWith($(updateId, $data));
                        });
                        if (settings.afterAjaxUpdate !== undefined) {
                            settings.afterAjaxUpdate(id, data);
                        }
                        $.fn.yiiGridView.sortable(id, null, originalData);
                    },
                    complete:function(){
                        grid.removeClass('grid-view-loading');
                        if($.isFunction(callback))
                        {
                            callback(sortOrder);
                        }
                    }
                });
            }
        }).disableSelection();
    };
})(jQuery);