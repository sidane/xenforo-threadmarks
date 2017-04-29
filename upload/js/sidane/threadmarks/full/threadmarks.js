/**
 * Create the Sidane namespace, if it does not already exist.
 */
var Sidane = Sidane || {};

/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
  $.tools.tooltip.addEffect('RightPreviewTooltip',
    function()
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
        tip.css({
          top: triggerOffset.top + config.offset[0] + this.getTrigger().outerHeight(),
          bottom: 'auto'
        });
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
          arrow.css({
            left: tip.width() - config.offset[1] - arrow.outerWidth(),
            top: 'auto'
          });
        }

      }
      tip.xfFadeIn(XenForo.speed.normal);
    },
    function()
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

    $el.bind({
      mouseenter: function()
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

          var $tipSource = $('#PreviewTooltip');
          var $tipHtml;

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
            XenForo.ajax(
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
            offset: [10, 15]
          }));

          $el.data('tooltip').show(0);

          if (XenForo._PreviewTooltipCache[previewUrl])
          {
            $(XenForo._PreviewTooltipCache[previewUrl])
              .xfInsert('replaceAll', $tipHtml.find('.PreviewContents'), 'show', 0);
          }
        }, 800);
      },

      mouseleave: function()
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

      mousedown: function()
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

  Sidane.ThreadmarkSortTree = function($tree) { this.__construct($tree); };
  Sidane.ThreadmarkSortTree.prototype =
  {
    __construct: function($tree)
    {
      this.$tree = $tree;

      this.$threadmarkList = $tree.parent().find($tree.data('threadmarklist'));

      this.buttons = {
        '$edit': $tree.parent().find($tree.data('editbutton')),
        '$save': $tree.parent().find($tree.data('savebutton'))
      };

      this.urls = {
        'load': $tree.data('loadurl'),
        'sync': $tree.data('syncurl'),
        'index': $tree.data('indexurl')
      };

      this.buttons.$edit.on('click', $.context(this, 'eEditButtonClick'));
      this.buttons.$save.on('click', $.context(this, 'eSaveButtonClick'));

      this.init();
    },

    init: function()
    {
      this.buttons.$save.hide();
    },

    load: function()
    {
      XenForo.ajax(this.urls.load, '', $.context(function(ajaxData)
      {
        this.$threadmarkList.remove();

        this.$tree.jstree({
          'plugins': [
            'dnd',
            'wholerow'
          ],
          'core': {
            'data': ajaxData['tree'],
            'check_callback': true,
            'themes': {
              'icons': false,
              'dots': false
            },
            'animation': 0
          },
          'dnd': {
            'copy': false,
            'drag_selection': true,
            'large_drag_target': true,
            'large_drop_target': true,
            'touch': 'selected',
            'inside_pos': 'last'
          }
        });
      }, this));
    },

    sync: function()
    {
      var requestData = {
        'tree': this.$tree.jstree(true).get_json('#', {'flat': true})
      };

      XenForo.ajax(this.urls.sync, requestData, $.context(function()
      {
        console.log('Tree synchronized');

        var overlay = new XenForo.OverlayLoader(
          $({'href': this.urls.index}),
          false,
          {'speed': XenForo.speed.fast}
        );
        overlay.load();
      }, this));
    },

    eEditButtonClick: function(event)
    {
      event.preventDefault();

      this.buttons.$edit.hide();
      this.buttons.$save.show();

      this.load();
    },

    eSaveButtonClick: function(event)
    {
      event.preventDefault();

      this.sync();
    }
  };

  Sidane.ThreadmarkQuickReply = function($form)
  {
    $form.on('QuickReplyComplete', function() {
      $('#ctrl_threadmark').val('');
      $('#ctrl_threadmark_category_id').val('0');
    });
  };

  // *********************************************************************

  XenForo.register('.ThreadmarkSortTree', 'Sidane.ThreadmarkSortTree');
  XenForo.register('#QuickReply',         'Sidane.ThreadmarkQuickReply');

  if (!XenForo.isTouchBrowser())
  {
    // register tooltip elements for desktop browsers
    XenForo.register('.RightPreviewTooltip', 'XenForo.RightPreviewTooltip');
  }
}(jQuery, this, document);
