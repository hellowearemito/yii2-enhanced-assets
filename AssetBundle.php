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
     * {@inheritdoc}
     */
    public function init()
    {
        if (YII_DEBUG) {
            if ($this->devJs !== null) {
                $this->js = [];
                foreach ($this->devJs as $name => $scripts) {
                    if (is_array($scripts)) {
                        $this->js = array_merge($this->js, $scripts);
                    } else {
                        $this->js[] = $scripts;
                    }
                }
            }
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
}
