<?php

class Sidane_Threadmarks_Install
{
  public static function install($existingAddOn, $addOnData)
  {
    if ($existingAddOn['version_id'] > 1) {
      return;
    }

    $db->query("
      CREATE TABLE IF NOT EXISTS threadmarks (
        threadmark_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
        thread_id INT UNSIGNED NOT NULL,
        post_id INT UNSIGNED NOT NULL,
        label VARCHAR(100) NOT NULL,
        KEY thread_id (thread_id)
      ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
    ");

    $db = XenForo_Application::get('db');
    $db->query("ALTER TABLE xf_thread ADD COLUMN has_threadmarks INT UNSIGNED DEFAULT 0 NOT NULL AFTER prefix_id");
  }

  public static function uninstall()
  {
    $db = XenForo_Application::get('db');
    $db->query("ALTER TABLE xf_thread DROP COLUMN has_threadmarks");
  }
}

?>
