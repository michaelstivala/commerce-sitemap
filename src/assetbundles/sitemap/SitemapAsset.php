<?php

namespace Stivala\Sitemap\assetbundles\Sitemap;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class SitemapAsset extends AssetBundle
{
    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@Stivala/Sitemap/assetbundles/sitemap/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/Sitemap.js',
        ];

        $this->css = [
            'css/Sitemap.css',
        ];

        parent::init();
    }
}
