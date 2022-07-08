<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 24.09.2018
 * Time: 12:13
 */

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\YiiAsset;
use yii\bootstrap\BootstrapAsset;

class ManagementAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $js = [
        'js/globals.js',
        'js/management.js',
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
    ];
}