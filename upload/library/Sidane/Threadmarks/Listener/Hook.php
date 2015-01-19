<?php

class Sidane_Threadmarks_Listener_Hook
{

  public static function templateCreate($templateName, array &$params, XenForo_Template_Abstract $template)
  {
    if ($templateName == 'page_nav')
    {
      $template->preloadTemplate('page_nav_threadmarks');
      $template->preloadTemplate('new_threadmark_control');
    }
  }

  public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
  {
    if ($hookName == 'post_private_controls') {
      $contents .= $template->create('new_threadmark_control', $hookParams);
    }

    if ($hookName == 'thread_view_pagenav_before' && $hookParams['thread']['threadmark_count']) {
      $contents .= $template->create('threadmarks_css', $hookParams);;
    }
  }

}
