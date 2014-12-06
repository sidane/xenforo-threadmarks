<?php

class Sidane_Threadmarks_Listener_Hook
{

  public static function templateCreate($templateName, array &$params, XenForo_Template_Abstract $template)
  {
    if ($templateName == 'page_nav')
    {
      $template->preloadTemplate('page_nav_threadmarks');
    }
  }

  public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
  {
    if ($hookName == 'post_pagination_links') {
      $contents .= $template->create('page_nav_threadmarks', $hookParams);
    }
  }

}
