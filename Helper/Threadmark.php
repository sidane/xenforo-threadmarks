<?php

class Sidane_Threadmarks_Helper_Threadmark extends XenForo_Template_Helper_Core
{

  public static function renderThreadmarkFlag($postId, $threadmarks)
  {
    if (count($threadmarks) > 0 && array_key_exists($postId, $threadmarks)) {
      $threadmark = $threadmarks[$postId];
      $threadmarkPhrase = new XenForo_Phrase('threadmark');
      return "<span class='threadmarker' id='post-{$postId}'><strong>{$threadmarkPhrase}:</strong> {$threadmark['label']}</span>";
    }
  }

}
