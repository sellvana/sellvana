<?php

class FCom_Admin extends BClass
{
    static public function bootstrap()
    {
        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }

        BDb::migrate('FCom_Admin::migrate');

        FCom_Admin_Model_User::i();

        BFrontController::i()
            ->route('GET /', 'FCom_Admin_Controller.index')
            ->route('GET /blank', 'FCom_Admin_Controller.blank')
            ->route('POST /login', 'FCom_Admin_Controller.login_post')
            ->route('GET /logout', 'FCom_Admin_Controller.logout')

            ->route('GET /my_account', 'FCom_Admin_Controller.my_account')
            ->route('GET /reports', 'FCom_Admin_Controller.reports')
            ->route('POST /my_account/personalize', 'FCom_Admin_Controller.personalize')

            ->route('GET /users', 'FCom_Admin_Controller_Users.index')
            ->route('GET|POST /users/grid_data', 'FCom_Admin_Controller_Users.grid_data')
            ->route('GET|POST /users/form/:id', 'FCom_Admin_Controller_Users.form')

            ->route('GET /roles', 'FCom_Admin_Controller_Roles.index')
            ->route('GET|POST /roles/grid_data', 'FCom_Admin_Controller_Roles.grid_data')
            ->route('GET|POST /roles/form/:id', 'FCom_Admin_Controller_Roles.form')
            ->route('GET|POST /roles/form/:id/tree_data', 'FCom_Admin_Controller_Roles.tree_data')

            ->route('GET|POST /media/grid/:do', 'FCom_Admin_Controller_MediaLibrary.grid_data')

            ->route('GET /settings', 'FCom_Admin_Controller_Settings.index')

            ->route('GET /modules', 'FCom_Admin_Controller_Modules.index')
            ->route('GET|POST /modules/grid_data', 'FCom_Admin_Controller_Modules.grid_data')
        ;

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Admin_View_Root'))
            ->view('jqgrid', array('view_class'=>'FCom_Admin_View_Grid'))

            ->view('users-form', array('view_class'=>'FCom_Admin_View_Form'))
            ->view('roles-form', array('view_class'=>'FCom_Admin_View_Form'))

            ->allViews('views')

            ->defaultTheme('FCom_Admin_DefaultTheme')
        ;

        FCom_Admin_Model_Role::i()->createPermission(array(
            'admin/users' => 'Manage Users',
            'admin/roles' => 'Manage Roles and Permissions',
            'admin/settings' => 'Update Settings',
            'admin/modules' => 'Manage Modules',
        ));

        BPubSub::i()->on('BActionController::beforeDispatch', 'FCom_Admin.onBeforeDispatch');

    }

    public function onBeforeDispatch()
    {
    }

    public static function migrate()
    {
        BDb::install('0.1.0', function() {
            $tUser = FCom_Admin_Model_User::table();
            $tPersonalize = FCom_Admin_Model_Personalize::table();
            BDb::run("
CREATE TABLE IF NOT EXISTS {$tUser} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `superior_id` int(10) unsigned DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `phone_ext` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `status` char(1) NOT NULL DEFAULT 'A',
  `tz` varchar(50) NOT NULL DEFAULT 'America/Los_Angeles',
  `locale` varchar(50) NOT NULL DEFAULT 'en_US',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_email` (`email`),
  UNIQUE KEY `UNQ_username` (`username`),
  CONSTRAINT `FK_{$tUser}_superior` FOREIGN KEY (`superior_id`) REFERENCES {$tUser} (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$tPersonalize} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `data_json` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_{$tPersonalize}_user` FOREIGN KEY (`user_id`) REFERENCES {$tUser} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        });

        BDb::upgrade('0.1.0', '0.1.1', function() {
            $tUser = FCom_Admin_Model_User::table();
            $tRole = FCom_Admin_Model_Role::table();

            try {
                BDb::run("
ALTER TABLE {$tUser}
    ADD COLUMN `is_superadmin` TINYINT DEFAULT 0 NOT NULL AFTER `username`
    , ADD COLUMN `role_id` INT NULL AFTER `is_superadmin`
;
                ");
            } catch (Exception $e) { }

            BDb::run("
CREATE TABLE IF NOT EXISTS {$tRole} (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL,
  `permissions_data` TEXT NOT NULL, PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE {$tUser} SET is_superadmin=1;
            ");
        });
    }
}

