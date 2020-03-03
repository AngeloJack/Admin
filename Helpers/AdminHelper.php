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

use Modules\Admin\Contrib\AdminMenuInterface;
use Modules\Admin\Contrib\SingleAdminModelInterface;
use Phact\Application\ModulesInterface;
use Phact\Di\ComponentFetcher;
use Phact\Orm\Model;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class AdminHelper
{
    use ComponentFetcher;

    public static function getMenu()
    {
        $menu = [];
        $classes = [];
        $models = [];
        $modelsFolder = 'Models';
        /** @var ModulesInterface $modules */
        $modules = self::fetchComponent(ModulesInterface::class);
        if ($modules) {
            foreach ($modules->getModules() as $name => $module) {
                $items = [];
                if ($module instanceof AdminMenuInterface) {
                    $items = $module->getPublicAdmins();
                }
                $settings = $module->getSettingsModel();
                if ($items || $settings) {
                    $menu[] = [
                        'name' => $module->getVerboseName(),
                        'settings' => $settings,
                        'key' => $name,
                        'class' => get_class($module),
                        'items' => $items
                    ];
                }
                $path = implode(DIRECTORY_SEPARATOR, [$module->getPath(), $modelsFolder]);
                if (is_dir($path)) {
                    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
                    {
                        if ($filename->isDir()) continue;
                        $name = $filename->getBasename('.php');
                        $classes[] = implode('\\', [$module::classNamespace(), $modelsFolder, $name]);
                    }
                }
            }
            foreach ($classes as $class) {
                if (class_exists($class) && is_a($class, Model::class, true)) {
                    $reflection = new ReflectionClass($class);
                    if (!$reflection->isAbstract()) {
                        $models[] = new $class();
                    }
                }
            }
            foreach ($models as $model) {
                if ($model instanceof SingleAdminModelInterface) {
                    $class = $model::className();
                    $classParts = explode('\\', $class);
                    $name = array_pop($classParts);
                    $menu[] = [
                        'name' => $model->getVerboseName(),
                        'settings' => $model,
                        'key' => $name,
                        'class' => get_class($model),
                        'items' => []
                    ];
                }
            }
        }

        return $menu;
    }
}