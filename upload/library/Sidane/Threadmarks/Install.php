<?php

class Sidane_Threadmarks_Install
{
  const default_menu_limit = 8;

  public static function install($existingAddOn, $addOnData)
  {
    $version = isset($existingAddOn['version_id']) ? $existingAddOn['version_id'] : 0;

    $db = XenForo_Application::get('db');
    $tables_created = false;
    $requireIndexing = array();

    if ($version == 0)
    {
      $requireIndexing['threadmark'] = true;
      $db->query("
        CREATE TABLE IF NOT EXISTS threadmarks (
          threadmark_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
          thread_id INT UNSIGNED NOT NULL,
          post_id INT UNSIGNED NOT NULL,
          user_id int not null default 0,
          post_date int not null default 0,
          position int not null default 0,
          `parent_threadmark_id` int(10) unsigned NOT NULL DEFAULT '0',
          `depth` int(10) unsigned NOT NULL DEFAULT '0',
          message_state enum('visible','moderated','deleted') NOT NULL DEFAULT 'visible',
          edit_count int not null default 0,
          last_edit_date int not null default 0,
          last_edit_user_id int not null default 0,
          label VARCHAR(255) NOT NULL,
          UNIQUE KEY `thread_post_id` (`thread_id`,`post_id`),
          KEY `thread_position` (`thread_id`,`position`),
          KEY `user_id` (`user_id`),
          UNIQUE KEY `post_id` (`post_id`)
        ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
      ");
      $tables_created = true;
    }

    if ($version == 1)
    {
      SV_Utils_Install::dropColumn('xf_thread', 'has_threadmarks');
      SV_Utils_Install::dropIndex('threadmarks', 'thread_id');
    }

    if ($version < 2)
    {
      SV_Utils_Install::addColumn('xf_thread', 'threadmark_count', 'INT UNSIGNED DEFAULT 0 NOT NULL');
      SV_Utils_Install::addIndex('threadmarks', 'thread_post_id', array('thread_id','post_id'));

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
      SV_Utils_Install::modifyColumn('threadmarks', 'label', 'varchar(100)', 'VARCHAR(255) NOT NULL');
    }

    if ($version < 7)
    {
      SV_Utils_Install::dropIndex('threadmarks', 'post_id');
      SV_Utils_Install::addIndex('threadmarks', 'post_id', array('post_id'));
    }

    if ($version <= 9)
    {
      $db->query("
        INSERT IGNORE INTO xf_content_type
            (content_type, addon_id, fields)
        VALUES
            ('threadmark', 'sidaneThreadmarks', '')
      ");

      $db->query("
        INSERT IGNORE INTO xf_content_type_field
            (content_type, field_name, field_value)
        VALUES
            ('threadmark', 'edit_history_handler_class', 'Sidane_Threadmarks_EditHistoryHandler_Threadmark')
           ,('threadmark', 'search_handler_class', 'Sidane_Threadmarks_Search_DataHandler_Threadmark')
           ,('threadmark', 'news_feed_handler_class', 'Sidane_Threadmarks_NewsFeedHandler_Threadmark')
      ");

      SV_Utils_Install::addColumn('threadmarks', 'position', 'int not null default 0');
      SV_Utils_Install::addIndex('threadmarks', 'thread_position', array('thread_id', 'position'));

      SV_Utils_Install::addColumn('threadmarks','user_id', 'int not null default 0');
      $db->query("update threadmarks mark
        join xf_post post on mark.post_id = post.post_id
        set mark.user_id = post.user_id
        where mark.user_id = 0
        ");
      SV_Utils_Install::addIndex('threadmarks', 'user_id', array('user_id'));
      SV_Utils_Install::addColumn('threadmarks', 'post_date', 'int not null default 0');
      $db->query("update threadmarks mark
        join xf_post post on mark.post_id = post.post_id
        set mark.post_date = post.post_date
        where mark.post_date = 0
        ");
      SV_Utils_Install::addColumn('threadmarks', 'message_state', "enum('visible','moderated','deleted') NOT NULL DEFAULT 'visible'");
      SV_Utils_Install::addColumn('threadmarks', 'edit_count', 'int not null default 0');
      SV_Utils_Install::addColumn('threadmarks', 'last_edit_date', 'int not null default 0');
      SV_Utils_Install::addColumn('threadmarks', 'last_edit_user_id', 'int not null default 0');

      SV_Utils_Install::addColumn('xf_thread', 'firstThreadmarkId', 'INT UNSIGNED DEFAULT 0 NOT NULL' );
      SV_Utils_Install::addColumn('xf_thread', 'lastThreadmarkId', 'INT UNSIGNED DEFAULT 0 NOT NULL' );

      XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
    }

    if ($version < 1020002)
    {
      SV_Utils_Install::renameColumn('threadmarks', 'post_date', 'threadmark_date', 'int not null default 0');
      SV_Utils_Install::addColumn('threadmarks', 'parent_threadmark_id', 'INT UNSIGNED DEFAULT 0 NOT NULL');
      SV_Utils_Install::addColumn('threadmarks', 'depth', 'INT UNSIGNED DEFAULT 0 NOT NULL');
    }

    if ($version < 1020002)
    {
      XenForo_Application::defer('Sidane_Threadmarks_Deferred_Cache', array(), null, true);
    }

    // if Elastic Search is installed, determine if we need to push optimized mappings for the search types
    // requires overriding XenES_Model_Elasticsearch
    SV_Utils_Deferred_Search::SchemaUpdates($requireIndexing);
  }

  public static function uninstall()
  {
    SV_Utils_Install::dropColumn('xf_thread', 'has_threadmarks');
    SV_Utils_Install::dropColumn('xf_thread', 'threadmark_count');
    SV_Utils_Install::dropColumn('xf_thread', 'firstThreadmarkId');
    SV_Utils_Install::dropColumn('xf_thread', 'lastThreadmarkId');

    $db = XenForo_Application::get('db');
    $db->query("DROP TABLE IF EXISTS threadmarks");

    $db->query("delete from xf_permission_entry 
        where permission_id in ('sidane_tm_manage', 'sidane_tm_add', 'sidane_tm_delete', 'sidane_tm_edit', 'sidane_tm_menu_limit', 'sidane_tm_view')
    ");
    $db->query("delete from xf_permission_entry_content 
        where permission_id in ('sidane_tm_manage', 'sidane_tm_add', 'sidane_tm_delete', 'sidane_tm_edit', 'sidane_tm_menu_limit', 'sidane_tm_view')
    ");

    $db->query("
      DELETE FROM xf_content_type
      WHERE xf_content_type.addon_id = 'sidaneThreadmarks'
    ");

    $db->query("
      DELETE FROM xf_content_type_field
      WHERE xf_content_type_field.field_value like 'Sidane_Threadmarks_%'
    ");
    XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
  }
}
