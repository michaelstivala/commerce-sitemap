<?php

namespace Stivala\Sitemap;

use Craft;
use yii\base\Event;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\services\Plugins;
use craft\events\PluginEvent;
use Stivala\Sitemap\models\Settings;
use craft\events\RegisterUrlRulesEvent;

class Sitemap extends Plugin
{
    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Sitemap::$plugin
     *
     * @var Sitemap
     */
    public static $plugin;

    public $hasCpSection = true;
    public $hasCpSettings = true;

    // table schema version
    public $schemaVersion = '1.0.0';

    /**
     * Return the settings response (if some one clicks on the settings/plugin icon)
     *
     */

    public function getSettingsResponse()
    {
        $url = \craft\helpers\UrlHelper::cpUrl('settings/sitemap');
        return \Craft::$app->controller->redirect($url);
    }

    /**
     * Register CP URL rules
     *
     * @param RegisterUrlRulesEvent $event
     */

    public function registerCpUrlRules(RegisterUrlRulesEvent $event)
    {
        // only register CP URLs if the user is logged in
        if (!\Craft::$app->user->identity) {
            return;
        }
        
        $rules = [
            // register routes for the settings tab
            'settings/sitemap' => [
                'route'=>'commerce-sitemap/settings/index',
                'params'=>['source' => 'CpSettings']],
            'settings/sitemap/save-sitemap' => [
                'route'=>'commerce-sitemap/settings/save-sitemap',
                'params'=>['source' => 'CpSettings']],
        ];
        $event->rules = array_merge($event->rules, $rules);
    }
    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Sitemap::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;
        
        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            [$this, 'registerCpUrlRules']
        );

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['sitemap.xml'] = 'commerce-sitemap/sitemap/index';
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        Craft::info(
            Craft::t(
                'app',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'sitemap/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
