!function($, window, document, _undefined)
{
    XenForo.ThreadmarkSortable = function($container)
    {
        var sortUrl = $container.data('sort-url'),
                      itemSelector = 'li.sortableItem';

        if (!sortUrl)
        {
            console.log('No data-sort-url for sortable.');
            return;
        }

        $container.sortable(
        {
            items: itemSelector,
            forcePlaceholderSize: true
        }).bind(
            {
                'sortupdate': function(e)
                {
                    var order = [];

                    $container.find('[data-item-id]').each(function(i)
                    {
                        var $this = $(this),
                            itemId = $this.data('item-id'),
                            parentId = $this.parent().data('parent-id');

                        if (parentId !== undefined)
                        {
                            order[i] = [itemId, parentId];
                        }
                        else
                        {
                            order[i] = itemId;
                        }
                    });

                    // moving across groups can trigger this multiple times
                    if ($container.data('sort-timer'))
                    {
                        clearTimeout($container.data('sort-timer'));
                    }
                    $container.data('sort-timer', setTimeout(function()
                    {
                        XenForo.ajax(
                            sortUrl,
                            { order: order },
                            function(e)
                            {
                                console.info('drag order updated');
                            }
                        );
                    }, 100));
                },
                'dragstart' : function(e)
                {
                    console.log('drag start, %o', e.target);
                },
                'dragend' : function(e) { console.log('drag end'); }
            }
        );
    }

    // Register tooltip elements for desktop browsers
    XenForo.register('.ThreadmarkSortable', 'XenForo.ThreadmarkSortable');
}
(jQuery, this, document);