<?php

class Sidane_Threadmarks_Install
{
  const default_menu_limit = 8;

  public static function install($existingAddOn, $addOnData)
  {
    $version = isset($existingAddOn['version_id']) ? $existingAddOn['version_id'] : 0;

    $db = XenForo_Application::get('db');
    $tables_created = false;

    if ($version == 0)
    {
      $db->query("
        CREATE TABLE IF NOT EXISTS threadmarks (
          threadmark_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
          thread_id INT UNSIGNED NOT NULL,
          post_id INT UNSIGNED NOT NULL,
          label VARCHAR(100) NOT NULL,
          UNIQUE KEY `thread_post_id` (`thread_id`,`post_id`)
        ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
      ");
      $tables_created = true;
    }

    if ($version == 1)
    {
      if ($db->fetchRow("SHOW COLUMNS FROM xf_thread WHERE Field = ?", 'has_threadmarks'))
      {
        $db->query("ALTER TABLE xf_thread DROP COLUMN has_threadmarks");
      }
      if ($db->fetchRow("SHOW INDEX FROM threadmarks WHERE Column_name = ?", 'thread_id'))
      {
        $db->query("ALTER TABLE threadmarks DROP INDEX thread_id ");
      }
    }

    if ($version < 2)
    {
      if (!$db->fetchRow("SHOW COLUMNS FROM xf_thread WHERE Field = ?", 'threadmark_count'))
      {
        $db->query("ALTER TABLE xf_thread ADD COLUMN threadmark_count INT UNSIGNED DEFAULT 0 NOT NULL");
      }
      if (!$db->fetchRow("SHOW INDEX FROM threadmarks WHERE Key_name = ?", 'thread_post_id'))
      {
          $db->query("ALTER TABLE threadmarks add unique index thread_post_id (`thread_id`,`post_id`)");
      }

      $db->query("insert ignore into xf_permission_entry_content (content_type, content_id, user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
        select distinct content_type, content_id, user_group_id, user_id, convert(permission_group_id using utf8), 'sidane_tm_manage', permission_value, permission_value_int
        from xf_permission_entry_content
        where permission_group_id = 'forum' and permission_id in ('warn','editAnyPost','deleteAnyPost')
        ");

      $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
        select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'sidane_tm_add', permission_value, permission_value_int
        from xf_permission_entry
        where permission_group_id = 'forum' and permission_id in ('postReply')
        ");

      $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
        select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'sidane_tm_delete', permission_value, permission_value_int
        from xf_permission_entry
        where permission_group_id = 'forum' and permission_id in ('deleteOwnPost')
        ");

      $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
        select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'sidane_tm_edit', permission_value, permission_value_int
        from xf_permission_entry
        where permission_group_id = 'forum' and permission_id in ('editOwnPost')
        ");

      $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
        select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'sidane_tm_menu_limit', 'use_int', ".self::default_menu_limit."
        from xf_permission_entry
        where permission_group_id = 'forum' and  permission_id in ('viewContent')
        ");

      $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
        select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'sidane_tm_view', permission_value, permission_value_int
        from xf_permission_entry
        where permission_group_id = 'forum' and  permission_id in ('viewContent')
        ");
    }

    XenForo_Application::defer('Sidane_Threadmarks_Deferred_Cache', array(), null, true);
  }

  public static function uninstall()
  {
/*  
    $db = XenForo_Application::get('db');
    if ($db->fetchRow("SHOW COLUMNS FROM xf_thread WHERE Field = ?", 'threadmark_count'))
    {
      $db->query("ALTER TABLE xf_thread DROP COLUMN threadmark_count");
    }
    $db->query("DROP TABLE IF EXISTS threadmarks");

    $db->delete('xf_permission_entry', "permission_id = 'sidane_tm_manage'");
    $db->delete('xf_permission_entry', "permission_id = 'sidane_tm_add'");
    $db->delete('xf_permission_entry', "permission_id = 'sidane_tm_delete'");
    $db->delete('xf_permission_entry', "permission_id = 'sidane_tm_edit'");
    $db->delete('xf_permission_entry', "permission_id = 'sidane_tm_menu_limit'");
    $db->delete('xf_permission_entry', "permission_id = 'sidane_tm_view'");

    $db->delete('xf_permission_entry_content', "permission_id = 'sidane_tm_manage'");
    $db->delete('xf_permission_entry_content', "permission_id = 'sidane_tm_add'");
    $db->delete('xf_permission_entry_content', "permission_id = 'sidane_tm_delete'");
    $db->delete('xf_permission_entry_content', "permission_id = 'sidane_tm_edit'");
    $db->delete('xf_permission_entry_content', "permission_id = 'sidane_tm_menu_limit'");
    $db->delete('xf_permission_entry_content', "permission_id = 'sidane_tm_view'");
*/    
  }
}
