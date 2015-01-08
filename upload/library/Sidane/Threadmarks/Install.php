<?php

class Sidane_Threadmarks_Install
{
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
            $db->query("ALTER TABLE xf_thread DROP COLUMN has_threadmarks");
            $db->query("ALTER TABLE xf_thread DROP INDEX thread_id ");
        }

        if ($version < 2)
        {            
            $db->query("ALTER TABLE xf_thread ADD COLUMN threadmark_count INT UNSIGNED DEFAULT 0 NOT NULL");
            if (!$tables_created)
            {
                $db->query("ALTER TABLE threadmarks add unique index thread_post_id (`thread_id`,`post_id`)");
                $db->query("ALTER TABLE threadmarks add unique index thread_post_id (`thread_id`,`post_id`)");
            }
        }        
        
        XenForo_Application::defer('Sidane_Threadmarks_Deferred_Cache', array(), null, true);
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');
        $db->query("ALTER TABLE xf_thread DROP COLUMN threadmark_count");
        $db->query("DROP TABLE threadmarks");
    }
}
