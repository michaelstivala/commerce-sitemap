<?php

namespace Stivala\Sitemap\assetbundles\indexcpsection;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class IndexCPSectionAsset extends AssetBundle
{
    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@Stivala/Sitemap/assetbundles/indexcpsection/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/Index.js',
        ];

        $this->css = [
            'css/Index.css',
        ];

        parent::init();
    }
}
