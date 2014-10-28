<?php

namespace mito\assets;

use Yii;
use yii\console\Controller;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * This command returns information about asset bundles for grunt.
 */
class PackagesController extends Controller
{
    /**
     * @var path to main config file
     */
    public $configPath;

    /**
     * @var array names of bundles to always check when deploying
     */
    public $deployBundles = [
        'yii\validators\ValidationAsset',
        'yii\validators\PunycodeAsset',
        'yii\widgets\MaskedInputAsset',
        'yii\widgets\ActiveFormAsset',
        'yii\widgets\PjaxAsset',
        'yii\captcha\CaptchaAsset',
        'yii\grid\GridViewAsset',
        'yii\web\JqueryAsset',
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapPluginAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapThemeAsset',
    ];

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['configPath'] // global for all actions
        );
    }

    /**
     * Loads the config file
     * @return loaded config
     */
    public function loadConfigFile()
    {
        if (!isset($this->configPath)) {
            $configPath = Yii::getAlias('@app/config') . DIRECTORY_SEPARATOR . 'web.php';
        } else {
            $configPath = $this->configPath;
        }
        return require($configPath);
    }

    /**
     * Return paths to assets.
     * @return array
     */
    public function getPaths()
    {
        $mainConfig = $this->loadConfigFile();

        $paths = [[
            'path' => Yii::getAlias('@app'),
            'module' => '_app',
        ]];


        /** @todo: module classes are namespaced class names, not path aliases */
        if (empty($mainConfig['modules'])) {
            return $paths;
        }
        $modules = $mainConfig['modules'];

        foreach ($modules as $config) {
            // merge submodules
            if (is_array($config) && !empty($config['modules'])) {
                $modules = $modules + $config['modules'];
            }
        }

        foreach ($modules as $name => $config) {
            if (is_array($config)) {
                if (!empty($config['basePath'])) {
                    $path = realpath(Yii::getAlias($config['basePath']));
                    if ($path === false) {
                        continue;
                    }
                } elseif (!empty($config['class'])) {
                    try {
                        $class = new \ReflectionClass($config['class']);
                    } catch (\ReflectionException $e) {
                        continue;
                    }
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
     * Instantiates bundle by name and its dependencies.
     * Inserts the bundle into $bundles.
     *
     * @param string $bundleName
     * @param array $bundles
     */
    protected function collectBundle($bundleName, &$bundles)
    {
        if (isset($bundles[$bundleName])) {
            return;
        }
        $bundle = Yii::createObject($bundleName);

        $bundles[$bundleName] = $bundle;

        foreach ($bundle->depends as $dependsName) {
            $this->collectBundle($dependsName, $bundles);
        }
    }

    /**
     * This command checks if any of the package files are newer than their directory,
     * and touches the directory to force Yii to publish it again.
     */
    public function actionDeploy()
    {
        $paths = $this->getPaths();

        $bundles = [];

        foreach ($paths as $pathConfig) {
            $module = $pathConfig['module'];
            $path = $pathConfig['path'];
            $bundlesFile = $path . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'bundles.php';
            if (!file_exists($bundlesFile)) {
                continue;
            }
            $bundleNames = include($bundlesFile);
            foreach ($bundleNames as $bundleName) {
                $this->collectBundle($bundleName, $bundles);
            }
        }

        foreach ($this->deployBundles as $bundleName) {
            $this->collectBundle($bundleName, $bundles);
        }

        foreach ($bundles as $bundle) {
            if ($bundle instanceof AssetBundle && $bundle->distPath !== null) {
                $directory = Yii::getAlias($bundle->distPath);
            } elseif ($bundle->sourcePath !== null) {
                $directory = Yii::getAlias($bundle->sourcePath);
            } else {
                continue;
            }

            $files = [];

            foreach (array_merge($bundle->js, $bundle->css) as $file) {
                if (Url::isRelative($file)) {
                    $files[] = $file;
                }
            }

            $dirtime = @filemtime($directory);

            if ($dirtime === false) {
                continue;
            }

            foreach ($files as $file) {
                echo "Checking $directory/$file\n";
                $time = @filemtime($directory . '/' . $file);
                if ($time === false) {
                    continue;
                }
                if ($time > $dirtime) {
                    echo "Touching $directory\n";
                    touch($directory);
                    clearstatcache();
                    continue 2;
                }
            }
        }
    }

    /**
     * This command returns information about asset bundles for grunt.
     */
    public function actionIndex()
    {
        $paths = $this->getPaths();

        $ret = [
            'packages' => [],
        ];

        foreach ($paths as $pathConfig) {
            $module = $pathConfig['module'];
            $path = $pathConfig['path'];
            $bundlesFile = $path . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'bundles.php';
            if (!file_exists($bundlesFile)) {
                continue;
            }
            $bundles = include($bundlesFile);
            foreach ($bundles as $bundleName) {
                $bundle = Yii::createObject($bundleName);

                if (!$bundle instanceof AssetBundle) {
                    continue;
                }

                if ($bundle->devPath === null || $bundle->distPath === null) {
                    continue;
                }
                $config = [
                    'sources' => Yii::getAlias($bundle->devPath),
                    'dist' => Yii::getAlias($bundle->distPath),
                    'module' => $module,
                ];
                $cssSourcePaths = [];
                if ($bundle->scssPath !== null) {
                    $config['scssPath'] = $bundle->scssPath;
                    $cssSourcePaths = [$bundle->scssPath];
                }
                if (is_array($bundle->cssSourcePaths)) {
                    $cssSourcePaths = array_unique(array_merge($cssSourcePaths, $bundle->cssSourcePaths));
                }
                if (count($cssSourcePaths)) {
                    $config['cssfiles'][] = [
                        'sources' => $cssSourcePaths,
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
                        $config['jsfiles'][] = [
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
