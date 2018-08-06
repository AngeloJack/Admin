<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 03/10/16 09:47
 */

namespace Modules\Admin\Helpers;

use Phact\Helpers\Paths;
use Phact\Main\Phact;

class AdminHelper
{
    public static function getMenu()
    {
        $menu = [];
        $modules = Phact::app()->getModulesConfig();

        foreach ($modules as $name => $config) {
            if (isset($config['class'])) {
                $class = $config['class'];
                $moduleMenu = $class::getAdminMenu();
                $settings = $class::getSettingsModel();
                if ($moduleMenu || $settings) {
                    $menu[] = [
                        'name' => $class::getVerboseName(),
                        'settings' => $settings,
                        'key' => $name,
                        'class' => $class,
                        'items' => $moduleMenu
                    ];
                }
            }
        }

        return $menu;
    }
}