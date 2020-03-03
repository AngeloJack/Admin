<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 30/01/17 15:14
 */

namespace Modules\Admin\Controllers;

use Phact\Application\ModulesInterface;
use Phact\Components\BreadcrumbsInterface;
use Phact\Components\FlashInterface;
use Phact\Exceptions\UnknownPropertyException;
use Phact\Form\ModelForm;
use Phact\Interfaces\AuthInterface;
use Phact\Main\Phact;
use Phact\Module\Module;
use Phact\Orm\Model;
use Phact\Orm\TableManager;
use Phact\Request\HttpRequestInterface;
use Phact\Template\RendererInterface;
use Phact\Translate\Translate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class SettingsController extends BackendController
{
    /** @var BreadcrumbsInterface */
    protected $_breadcrumbs;

    /** @var ModulesInterface */
    protected $_modules;

    /** @var FlashInterface */
    protected $_flash;

    /** @var Translate */
    protected $_translate;

    public $modelsFolder = 'Models';

    public function __construct(
        HttpRequestInterface $request,
        AuthInterface $auth,
        ModulesInterface $modules,
        RendererInterface $renderer,
        FlashInterface $flash = null,
        BreadcrumbsInterface $breadcrumbs = null,
        Translate $translate = null
    )
    {
        $this->_modules = $modules;
        $this->_breadcrumbs = $breadcrumbs;
        $this->_flash = $flash;
        $this->_translate = $translate;

        parent::__construct($request, $auth, $renderer);
    }

    public function index($module)
    {
        $singleModel = false;
        try {
            /** @var Module $module */
            $module = $this->_modules->getModule($module);
            /** @var Model $settingsModel */
            $settingsModel = $module->getSettingsModel();
            if (!$settingsModel) {
                $this->error(404);
            }
            $model = $settingsModel->objects()->get();
            if (!$model) {
                $model = $settingsModel;
            }
        } catch (UnknownPropertyException $e) {
            $classes = [];
            foreach ($this->_modules->getModules() as $moduleName) {
                $path = implode(DIRECTORY_SEPARATOR, [$moduleName->getPath(), $this->modelsFolder]);
                if (is_dir($path)) {
                    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
                    {
                        if ($filename->isDir()) continue;
                        $name = $filename->getBasename('.php');
                        $classes[] = implode('\\', [$moduleName::classNamespace(), $this->modelsFolder, $name]);
                    }
                }
            }
            $models = [];
            foreach ($classes as $class) {
                if (class_exists($class) && is_a($class, Model::class, true)) {
                    $reflection = new ReflectionClass($class);
                    if (!$reflection->isAbstract()) {
                        $models[] = new $class();
                    }
                }
            }
            foreach ($models as $modelClass) {
                $class = $modelClass::className();
                $classParts = explode('\\', $class);
                $name = array_pop($classParts);
                if ($name == $module) {
                    $singleModel = true;
                    $model = $modelClass->objects()->get();
                    $module = $modelClass;
                }
            }
        }

        if (!$module) {
            throw new UnknownPropertyException("Module with name" . $name . " not found");
        }

        /** @var ModelForm $settingsForm */
        $settingsForm = !$singleModel ? $module->getSettingsForm() : $module->getSingleAdminModelForm();
        $settingsForm->setModel($model);
        $settingsForm->setInstance($model);

        if ($this->_breadcrumbs && $this->_translate) {
            $message = '';
            if (!$singleModel) {
                $message = $this->_translate->t('Admin.main', 'Settings of module');
            }
            $this->_breadcrumbs->add($message . ' "' . $module->getVerboseName() . '"');
        }

        if ($this->request->getIsPost() && $settingsForm->fill($_POST, $_FILES) && $settingsForm->valid) {
            $settingsForm->save();
            $module->afterSettingsUpdate();
            if ($this->_flash && $this->_translate) {
                $this->_flash->success($this->_translate->t('Admin.main', 'Changes saved'));
            }
            $this->request->refresh();
        }

        echo $this->render('admin/settings.tpl', [
            'form' => $settingsForm,
            'model' => $model,
            'settingsModule' => $module,
            'singleModel' => $singleModel
        ]);
    }
}