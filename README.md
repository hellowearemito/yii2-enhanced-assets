Enhanced assets for Yii2
========================

Enhanced assets for Yii2.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist mito/yii2-enhanced-assets "*"
```

or add

```
"mito/yii2-enhanced-assets": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Extend your asset bundles form `\mito\assets\AssetBundle` or `\mito\assets\FallbackAssetBundle`.
Create a `bundles.php` file in each assets directory, that returns an array of asset bundle class names.

Add the following to your console application configuration:

    'controllerMap' => [
        'packages' => 'mito\assets\PackagesController',
        'assetscleanup' => 'mito\assets\CleanupController',
    ],

Add the following to your web application configuration:

    'components' => [
        'assetManager' => [
            'class' => 'mito\assets\AssetManager',
        ],
    ],

Add the following to your console and web application configuration:

    'components' => [
        'bundleManager' => [
            'class' => 'mito\assets\BundleManager',
            'deployBundles' => [],
        ],
    ],

If you have an asset bundle that is not in `bundles.php` and is not a dependency of a bundle in `bundles.php`,
add it to `deployBundles`.
Yii's default bundles are included in `defaultBundles`.

`yii packages` will return an array of all bundles in the `bundles.php` files.

`yii packages/deploy` will check the files in the bundles, and will touch the bundle's base directory if any file is newer than
the base directory. This will cause Yii to publish the new version. You can also use `Yii::$app->bundleManager->deploy()`.

`yii assetscleanup` will delete old published asset bundles.
