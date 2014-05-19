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
    ],
