<?php

namespace mito\assets;

use mito\assets\AssetBundle;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/**
 * FallbackAssetBundle supports using a different asset bundle's
 * javascript files if the main asset bundle's js file fails to load.
 */
class FallbackAssetBundle extends AssetBundle
{
    /**
     * @var string classname of the fallback asset bundle
     */
    public $fallback = null;
    /**
     * @var string javascript expression that should be falsy if the main asset failed to load
     */
    public $check = null;

    /**
     * {@inheritdoc}
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);
        if ($this->fallback !== null && $this->check !== null) {
            $fallback = $view->getAssetManager()->getBundle($this->fallback);
            $scripts = '';
            foreach ($fallback->js as $js) {
                if (strpos($js, '/') !== 0 && strpos($js, '://') === false) {
                    $scripts .= Html::jsFile($fallback->baseUrl . '/' . $js, [], $fallback->jsOptions);
                } else {
                    $scripts .= Html::jsFile($js, [], $fallback->jsOptions);
                }
            }

            $position = isset($fallback->jsOptions['position']) ? $fallback->jsOptions['position'] : View::POS_END;
            $view->jsFiles[$position][] = Html::script(
                $this->check." || document.write(" . Json::encode($scripts) . ");",
                ['type' => 'text/javascript']
            );
        }
    }
}
