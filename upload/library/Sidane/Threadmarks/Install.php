<?php

class Sidane_Threadmarks_Install
{
  public static function install($existingAddOn, $addOnData)
  {
    $db = XenForo_Application::get('db');

    $db->query("
      CREATE TABLE IF NOT EXISTS threadmarks (
        threadmark_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
        thread_id INT UNSIGNED NOT NULL,
        post_id INT UNSIGNED NOT NULL,
        label VARCHAR(100) NOT NULL,
        UNIQUE KEY `thread_post_id` (`thread_id`,`post_id`)
      ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
    ");

    if ($existingAddOn['version_id'] <= 1) 
    {
        try
        {
          $db->query("ALTER TABLE xf_thread DROP COLUMN has_threadmarks;");
        }
        catch (Zend_Db_Exception $e) {}
    }
    
    if ($existingAddOn['version_id'] < 2) 
    {    
        try
        {
          $db->query("ALTER TABLE xf_thread ADD COLUMN threadmark_count INT UNSIGNED DEFAULT 0 NOT NULL");
        }
        catch (Zend_Db_Exception $e) {} 
    }    
  }

  public static function uninstall()
  {
    $db = XenForo_Application::get('db');
    $db->query("ALTER TABLE xf_thread DROP COLUMN threadmark_count");
    $db->query("DROP TABLE threadmarks");
  }
}
