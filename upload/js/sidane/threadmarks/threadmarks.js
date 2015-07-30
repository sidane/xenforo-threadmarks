!function($, window, document, _undefined)
{
    $.tools.tooltip.addEffect('RightPreviewTooltip',
    function(callback)
    {
        var triggerOffset = this.getTrigger().offset(),
            config = this.getConf(),
            css = {
                top: 'auto',
                bottom: $(window).height() - triggerOffset.top + config.offset[0]
            },
            narrowScreen = ($(window).width() < 480);

        if (narrowScreen)
        {
            css.left = Math.min(50, triggerOffset.left + config.offset[1]);
        }
        else
        {
            css.right = $('html').width() - this.getTrigger().outerWidth() - triggerOffset.left - config.offset[1];
            css.left = 'auto';
        }

        var tip = this.getTip();
        tip.css(css);

        var posY = $(window).scrollTop() - triggerOffset.top + config.offset[0] + tip.outerHeight();
        if (posY >= 0)
        {
            tip.css({ top: triggerOffset.top + config.offset[0] + this.getTrigger().outerHeight(), bottom: 'auto'});
        }

        if (!narrowScreen)
        {
            var arrow = tip.find('.arrow');
            if (posY >= 0)
            {
                arrow.hide();
            }
            else
            {
                arrow.css({left: tip.width() - config.offset[1] - arrow.outerWidth(), top:'auto'});
            }

        }
        tip.xfFadeIn(XenForo.speed.normal);
    },
    function(callback)
    {
        this.getTip().xfFadeOut(XenForo.speed.fast);
    });

    XenForo.RightPreviewTooltip = function($el)
    {
        var hasTooltip, previewUrl, setupTimer;

        if (!parseInt(XenForo._enableOverlays))
        {
            return;
        }

        if (!(previewUrl = $el.data('previewurl')))
        {
            console.warn('Preview tooltip has no preview: %o', $el);
            return;
        }

        $el.find('[title]').andSelf().attr('title', '');

        $el.bind(
        {
            mouseenter: function(e)
            {
                if (hasTooltip)
                {
                    return;
                }

                setupTimer = setTimeout(function()
                {
                    if (hasTooltip)
                    {
                        return;
                    }

                    hasTooltip = true;

                    var $tipSource = $('#PreviewTooltip'),
                        $tipHtml,
                        xhr;

                    if (!$tipSource.length)
                    {
                        console.error('Unable to find #PreviewTooltip');
                        return;
                    }

                    console.log('Setup preview tooltip for %s', previewUrl);

                    $tipHtml = $tipSource.clone()
                        .removeAttr('id')
                        .addClass('xenPreviewTooltip')
                        .appendTo(document.body);

                    if (!XenForo._PreviewTooltipCache[previewUrl])
                    {
                        xhr = XenForo.ajax(
                            previewUrl,
                            {},
                            function(ajaxData)
                            {
                                if (XenForo.hasTemplateHtml(ajaxData))
                                {
                                    XenForo._PreviewTooltipCache[previewUrl] = ajaxData.templateHtml;

                                    $(ajaxData.templateHtml).xfInsert('replaceAll', $tipHtml.find('.PreviewContents'));
                                }
                                else
                                {
                                    $tipHtml.remove();
                                }
                            },
                            {
                                type: 'GET',
                                error: false,
                                global: false
                            }
                        );
                    }

                    $el.tooltip(XenForo.configureTooltipRtl({
                        predelay: 500,
                        delay: 0,
                        effect: 'RightPreviewTooltip',
                        fadeInSpeed: 'normal',
                        fadeOutSpeed: 'fast',
                        tip: $tipHtml,
                        position: 'bottom right',
                        offset: [ 10, 15 ]
                    }));

                    $el.data('tooltip').show(0);

                    if (XenForo._PreviewTooltipCache[previewUrl])
                    {
                        $(XenForo._PreviewTooltipCache[previewUrl])
                            .xfInsert('replaceAll', $tipHtml.find('.PreviewContents'), 'show', 0);
                    }
                }, 800);
            },

            mouseleave: function(e)
            {
                if (hasTooltip)
                {
                    if ($el.data('tooltip'))
                    {
                        $el.data('tooltip').hide();
                    }

                    return;
                }

                if (setupTimer)
                {
                    clearTimeout(setupTimer);
                }
            },

            mousedown: function(e)
            {
                // the click will cancel a timer or hide the tooltip
                if (setupTimer)
                {
                    clearTimeout(setupTimer);
                }

                if ($el.data('tooltip'))
                {
                    $el.data('tooltip').hide();
                }
            }
        });
    };
    if (!XenForo.isTouchBrowser())
    {
        // Register tooltip elements for desktop browsers
        XenForo.register('.RightPreviewTooltip', 'XenForo.RightPreviewTooltip');
    }
}
(jQuery, this, document);