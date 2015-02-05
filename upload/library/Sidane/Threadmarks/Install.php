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
          label VARCHAR(255) NOT NULL,
          UNIQUE KEY `thread_post_id` (`thread_id`,`post_id`)
        ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
      ");
      $tables_created = true;
    }

    if ($version == 1)
    {
      self::dropColumn('xf_thread', 'has_threadmarks');
      self::dropIndex('threadmarks', 'thread_id');
    }

    if ($version < 2)
    {
      self::addColumn('xf_thread', 'threadmark_count', 'INT UNSIGNED DEFAULT 0 NOT NULL');
      self::addIndex('threadmarks', 'thread_post_id', array('thread_id','post_id'));

      $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
        select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'sidane_tm_manage', permission_value, permission_value_int
        from xf_permission_entry
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
    if ($version < 3)
    {
      self::modifyColumn('threadmarks', 'label', 'varchar(100)', 'VARCHAR(255) NOT NULL');
    }

    XenForo_Application::defer('Sidane_Threadmarks_Deferred_Cache', array(), null, true);
  }

  public static function uninstall()
  {
    self::dropColumn('xf_thread', 'has_threadmarks');
    self::dropColumn('xf_thread', 'threadmark_count');

    $db = XenForo_Application::get('db');
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
  }

  public static function modifyColumn($table, $column, $oldDefinition, $definition)
  {
    $db = XenForo_Application::get('db');
    $hasColumn = false;
    if (empty($oldDefinition))
    {
      $hasColumn = $db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $column);
    }
    else
    {
      $hasColumn = $db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ? and Type = ?', array($column,$oldDefinition));
    }
    
    if($hasColumn)
    {
      $db->query('ALTER TABLE `'.$table.'` MODIFY COLUMN `'.$column.'` '.$definition);
    }
  }

  public static function dropColumn($table, $column)
  {
    $db = XenForo_Application::get('db');
    if ($db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $column))
    {
      $db->query('ALTER TABLE `'.$table.'` drop COLUMN `'.$column.'` ');
    }
  }

  public static function addColumn($table, $column, $definition)
  {
    $db = XenForo_Application::get('db');
    if (!$db->fetchRow('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', $column))
    {
      $db->query('ALTER TABLE `'.$table.'` ADD COLUMN `'.$column.'` '.$definition);
    }
  }

  public static function addIndex($table, $index, array $columns)
  {
    $db = XenForo_Application::get('db');
    if (!$db->fetchRow('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', $index))
    {
      $cols = '(`'. implode('`,`', $columns). '`)';
      $db->query('ALTER TABLE `'.$table.'` add index `'.$index.'` '. $cols);
    }
  }

  public static function dropIndex($table, $index)
  {
    $db = XenForo_Application::get('db');
    if ($db->fetchRow('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', $index))
    {
      $db->query('ALTER TABLE `'.$table.'` drop index `'.$index.'` ');
    }
  } 
}
