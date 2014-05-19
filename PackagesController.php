<?php

namespace mito\assets;

use yii\console\Controller;
use yii\helpers\Json;
use \Yii;

/**
 * This command returns information about asset bundles for grunt.
 */
class PackagesController extends Controller
{
    protected function getPaths()
    {
        $config_path = Yii::getAlias('@app/config');
        $main_config = require($config_path. DIRECTORY_SEPARATOR .'web.php');

        $paths = [ Yii::getAlias('@app') ];


        /** @todo: module classes are namespaced class names, not path aliases */
        if (empty($main_config['modules'])) {
            return $paths;
        }
        $modules = $main_config['modules'];

        foreach ($modules as $config) {
            // merge submodules
            if (is_array($config) && !empty($config['modules'])) {
                $modules = $modules + $config['modules'];
            }
        }

        foreach ($modules as $name => $config) {
            if (is_array($config)) {
                if (!empty($config['basePath'])) {
                    $path = realpath($config['basePath']);
                } elseif (!empty($config['class'])) {
                    $class = new \ReflectionClass($config['class']);
                    $path = dirname($class->getFileName());
                    if ($path === false) {
                        continue;
                    }
                } else {
                    continue;
                }
            } else {
                continue;
            }

            $paths[] = [
                'path' => $path,
                'module' => $name,
            ];
        }

        return $paths;
    }

    /**
     * This command returns information about asset bundles for grunt.
     */
    public function actionIndex()
    {
        $paths = $this->getPaths();

        $ret = [
            'jsfiles' => [],
            'cssfiles' => [],
            'packages' => [],
        ];

        foreach ($paths as $path) {
            if (is_array($path)) {
                $module = $path['module'];
                $path = $path['path'];
            }
            $bundlesFile = $path . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'bundles.php';
            if (!file_exists($bundlesFile)) {
                continue;
            }
            $bundles = include($bundlesFile);
            foreach ($bundles as $bundleName) {
                $bundle = Yii::createObject($bundleName);

                if ($bundle->devPath === null || $bundle->distPath === null) {
                    continue;
                }
                $config = [
                    'sources' => Yii::getAlias($bundle->devPath),
                    'dist' => Yii::getAlias($bundle->distPath),
                ];
                if ($bundle->scssPath !== null) {
                    $config['scssPath'] = $bundle->scssPath;
                    $ret['cssfiles'][] = [
                        'sources' => $config['sources'] . DIRECTORY_SEPARATOR . $config['scssPath'],
                        'dev' => $config['sources'] . DIRECTORY_SEPARATOR . 'css', /** @todo hardcoded */
                        'dist' => $config['dist'] . DIRECTORY_SEPARATOR . 'css',
                    ];
                }
                if (!empty($bundle->devJs)) {
                    foreach ($bundle->devJs as $name => $scripts) {
                        if (!is_array($scripts)) {
                            continue;
                        }
                        $fullpaths = [];
                        foreach ($scripts as $script) {
                            $fullpaths[] = $config['sources'] . DIRECTORY_SEPARATOR . $script;
                        }
                        $destPath = $config['dist'] . DIRECTORY_SEPARATOR . $name;
                        $ret['jsfiles'][] = [
                            'sources' => $fullpaths,
                            'dist' => $destPath,
                        ];
                    }
                }
                if ($bundle->imgPath !== null) {
                    $config['imgPath'] = $bundle->imgPath;
                }
                if ($bundle->fontPath !== null) {
                    $config['fontPath'] = $bundle->fontPath;
                }
                $ret['packages'][] = $config;
            }
        }
        echo Json::encode($ret);
    }
}
