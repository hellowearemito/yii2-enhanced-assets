<?php

namespace mito\assets;

/**
 * Modified version of Yii's AssetBundle with support for
 * separate development and production assets
 */
class AssetBundle extends \yii\web\AssetBundle
{
    /**
     * @var array list of development js files
     * If this is not null, it will overwrite $js
     * If an element is an array, the javascript files in that array
     * will be compiled to the javascript file specified in the element's key
     *
     * For example:
     *
     * ```php
     * public $devJs = [
     *     'js/combined.js' => [ 'js/file1.js', 'js/file2.js' ],
     * ];
     * ```
     *
     */
    public $devJs = null;
    /**
     * @var string|null relative path to transpiled js files
     *
     * If this is not null, js files in devJs will be transpiled
     * into this directory and the asset bundle will load the transpiled
     * files instead of the source files in devJs.
     * The directory structure will be kept, e.g.
     * js/file1.js will be transpiled into $transpiledJsPath/js/file1.js.
     */
    public $transpiledJsPath = null;
    /**
     * @var array|null list of development css files
     * If this is not null, it will overwrite $css
     */
    public $devCss = null;
    /**
     * @var string the base directory for development assets
     *
     * You can use either a directory or an alias of the directory.
     */
    public $devPath = null;
    /**
     * @var string the base directory for production assets
     *
     * You can use either a directory or an alias of the directory.
     */
    public $distPath = null;
    /**
     * @var string relative path to images
     *
     * Images in this directory will be optimized and copied to the production path
     * by the build process
     */
    public $imgPath = null;
    /**
     * @var string relative path to fonts
     *
     * Files in this directory will be copied to the production path
     * by the build process
     */
    public $fontPath = null;
    /**
     * @var array list of paths (relative to base directory) to copy from development directory to production directory on build
     */
    public $otherPaths = [];
    /**
     * @var string relative path to scss files
     *
     * files in this directory will be compiled to css files in the css directory
     * @deprecated use $cssSourcePaths instead
     */
    public $scssPath = null;

    /**
     * @var array relative paths to css source files (scss, less etc.)
     *
     * files in these directory will be compiled to css files in the css directory
     */
    public $cssSourcePaths = [];

    /**
     * @var array|null extra parameters to pass to gulp.
     */
    public $extraParams;

    private $_distJs = [];

    private $_distCss = [];

    /**
     * Map development js files to their
     * transpiled versions by appending $transpiledJsPath.
     *
     * @param string|array $scripts js file paths
     *
     * @return string|array transpiled js file paths
     */
    protected function mapTranspiled($scripts)
    {
        if (!$this->transpiledJsPath) {
            return $scripts;
        }
        if (is_array($scripts)) {
            foreach ($scripts as &$script) {
                $script = $this->transpiledJsPath . '/' . $script;
            }
        } else {
            $scripts = $this->transpiledJsPath . '/' . $scripts;
        }
        return $scripts;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if (YII_DEBUG) {
            $this->_distJs = $this->js;
            if ($this->devJs !== null) {
                $this->js = [];
                foreach ($this->devJs as $name => $scripts) {
                    $scripts = $this->mapTranspiled($scripts);
                    if (is_array($scripts)) {
                        $this->js = array_merge($this->js, $scripts);
                    } else {
                        $this->js[] = $scripts;
                    }
                }
            }
            $this->_distCss = $this->css;
            if ($this->devCss !== null) {
                $this->css = $this->devCss;
            }
            if ($this->devPath !== null) {
                $this->sourcePath = $this->devPath;
            }
        } else {
            if ($this->distPath !== null) {
                $this->sourcePath = $this->distPath;
            }
        }
        parent::init();
    }

    /**
     * Returns production js files, even if the environment is in debug mode (YII_DEBUG is true)
     * @return array js files
     */
    public function getDistJs()
    {
        if (YII_DEBUG) {
            return $this->_distJs;
        }
        return $this->js;
    }

    /**
     * Returns production css files, even if the environment is in debug mode (YII_DEBUG is true)
     * @return array css files
     */
    public function getDistCss()
    {
        if (YII_DEBUG) {
            return $this->_distCss;
        }
        return $this->css;
    }
}
