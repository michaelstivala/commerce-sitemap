<?php

namespace Stivala\Sitemap\controllers;

use Craft;
use craft\db\Query;
use craft\web\Controller;
use Stivala\Sitemap\Sitemap;
use Stivala\Sitemap\records\SitemapEntry;
use Stivala\Sitemap\models\SitemapEntryModel;

class SettingsController extends Controller
{
    /**
     * Allows anonymous access to this controller's actions.
     */
    protected $allowAnonymous = false;

    private function _createEntrySectionQuery(): Query
    {
        return (new Query())
        ->select([
            'sections.id',
            'sections.structureId',
            'sections.name',
            'sections.handle',
            'sections.type',
            'count(DISTINCT entries.id) entryCount',
            'count(DISTINCT elements.id) elementCount',
            'sitemap_entries.id sitemapEntryId',
            'sitemap_entries.changefreq changefreq',
            'sitemap_entries.priority priority',
        ])->from(['{{%sections}} sections'])
            ->leftJoin('{{%structures}} structures', '[[structures.id]] = [[sections.structureId]]')
            ->innerJoin('{{%sections_sites}} sections_sites', '[[sections_sites.sectionId]] = [[sections.id]] AND [[sections_sites.hasUrls]] = 1')
            ->leftJoin('{{%entries}} entries', '[[sections.id]] = [[entries.sectionId]]')
            ->leftJoin('{{%elements}} elements', '[[entries.id]] = [[elements.id]] AND [[elements.enabled]] = 1')
            ->leftJoin('{{%stivala_sitemap_entries}} sitemap_entries', '[[sections.id]] = [[sitemap_entries.linkId]] AND [[sitemap_entries.type]] = "section"')

            ->groupBy(['sections.id'])
        ->orderBy(['type' => SORT_ASC], ['name' => SORT_ASC]);
    }

    private function _createProductTypeQuery(): Query
    {
        return (new Query())
        ->select([
            'commerce_producttypes.id',
            'commerce_producttypes.name',
            'commerce_producttypes.handle',
            'count(DISTINCT commerce_products.id) productCount',
            'sitemap_entries.id sitemapEntryId',
            'sitemap_entries.changefreq changefreq',
            'sitemap_entries.priority priority',
        ])->from(['{{%commerce_producttypes}} commerce_producttypes'])
            ->innerJoin('{{%commerce_producttypes_sites}} commerce_producttypes_sites', '[[commerce_producttypes_sites.productTypeId]] = [[commerce_producttypes.id]] AND [[commerce_producttypes_sites.hasUrls]] = 1')
            ->leftJoin('{{%commerce_products}} commerce_products', '[[commerce_producttypes.id]] = [[commerce_products.typeId]]')
            ->leftJoin('{{%stivala_sitemap_entries}} sitemap_entries', '[[commerce_producttypes.id]] = [[sitemap_entries.linkId]] AND [[sitemap_entries.type]] = "producttype"')

            ->groupBy(['commerce_producttypes.id'])
        ->orderBy(['type' => SORT_ASC], ['name' => SORT_ASC]);
    }

    private function _createCategoryQuery(): Query
    {
        return (new Query())
            ->select([
                'categorygroups.id',
                'categorygroups.name',
                'count(DISTINCT entries.id) entryCount',
                'count(DISTINCT elements.id) elementCount',
                'sitemap_entries.id sitemapEntryId',
                'sitemap_entries.changefreq changefreq',
                'sitemap_entries.priority priority',
            ])
            ->from(['{{%categories}} categories'])
            ->innerJoin('{{%categorygroups}} categorygroups', '[[categories.groupId]] = [[categorygroups.id]]')
            ->innerJoin('{{%categorygroups_sites}} categorygroups_sites', '[[categorygroups_sites.groupId]] = [[categorygroups.id]] AND [[categorygroups_sites.hasUrls]] = 1')
            ->leftJoin('{{%entries}} entries', '[[categories.id]] = [[entries.sectionId]]')
            ->leftJoin('{{%elements}} elements', '[[entries.id]] = [[elements.id]] AND [[elements.enabled]] = 1')
            ->leftJoin('{{%stivala_sitemap_entries}} sitemap_entries', '[[categorygroups.id]] = [[sitemap_entries.linkId]] AND [[sitemap_entries.type]] = "category"')
            ->groupBy(['categorygroups.id'])
            ->orderBy(['name' => SORT_ASC]);
    }

// Public Methods
// =========================================================================

/**
* Handle a request going to our plugin's index action URL,
* e.g.: actions/sitemap/default
*
* @return mixed
*/
    public function actionIndex(): craft\web\Response
    {
        $this->requireLogin();

        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();

        $source = (isset($routeParameters['source'])?$routeParameters['source']:'CpSection');


        $allSections = Craft::$app->getSections()->getAllSections();

        $allSections = $this->_createEntrySectionQuery()->all();
        $allStructures = [];

        if (is_array($allSections)) {
            foreach ($allSections as $section) {
                $allStructures[] = [
                    'id' => $section['id'],
                    'type' => $section['type'],
                    'heading' => $section['name'],
                    'enabled' => ($section['sitemapEntryId'] > 0 ? true : false),
                    'elementCount' => $section['elementCount'],
                    'changefreq' => ($section['sitemapEntryId'] > 0 ? $section['changefreq'] : 'weekly'),
                    'priority' => ($section['sitemapEntryId'] > 0 ? $section['priority'] : 0.5),
                ];
            }
        }

        $allCategories = $this->_createCategoryQuery()->all();
        $allCategoryStructures = [];
        if (is_array($allCategories)) {
            foreach ($allCategories as $category) {
                $allCategoryStructures[] = [
                    'id' => $category['id'],
                    'type' => 'category',
                    'heading' => $category['name'],
                    'enabled' => ($category['sitemapEntryId'] > 0 ? true : false),
                    'elementCount' => $category['elementCount'],
                    'changefreq' => ($category['sitemapEntryId'] > 0 ? $category['changefreq'] : 'weekly'),
                    'priority' => ($category['sitemapEntryId'] > 0 ? $category['priority'] : 0.5),
                ];
            }
        }

        $result = $this->_createProductTypeQuery()->all();
        $allProductTypes = [];
        if (is_array($result)) {
            foreach ($result as $productTypes) {
                $allProductTypes[] = [
                    'id' => $productTypes['id'],
                    'type' => 'productTypes',
                    'heading' => $productTypes['name'],
                    'enabled' => ($productTypes['sitemapEntryId'] > 0 ? true : false),
                    'elementCount' => $productTypes['productCount'],
                    'changefreq' => ($productTypes['sitemapEntryId'] > 0 ? $productTypes['changefreq'] : 'weekly'),
                    'priority' => ($productTypes['sitemapEntryId'] > 0 ? $productTypes['priority'] : 0.5),
                ];
            }
        }

        $variables = [
            'settings' => Sitemap::$plugin->getSettings(),
            'source' => $source,
            'pathPrefix' => ($source == 'CpSettings' ? 'settings/': ''),
            'allStructures' => $allStructures,
            'allCategories' => $allCategoryStructures,
            'allProductTypes' => $allProductTypes,
        ];

        return $this->renderTemplate('commerce-sitemap/settings', $variables);
    }

    /**
     * Called when saving the settings.
     *
     * @return Response
     */
    public function actionSaveSitemap(): craft\web\Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();
        $request = Craft::$app->getRequest();

        $sitemapSections = $request->getBodyParam('sitemapSections');
        // filter the enabled sections
        $allSectionIds = [];
        if (is_array($sitemapSections)) {
            foreach ($sitemapSections as $key => $entry) {
                if ($entry['enabled']) {
                    // filter section id from key

                    $id = (int)str_replace('id:', '', $key);
                    if ($id > 0) {
                        // find the entry, else add one
                        $sitemapEntry = SitemapEntry::find()->where(['linkId' => $id, 'type' => 'section'])->one();
                        if (!$sitemapEntry) {
                            // insert / update this section
                            $sitemapEntry = new SitemapEntry();
                        }
                        $sitemapEntry->linkId = $id;
                        $sitemapEntry->type = 'section';
                        $sitemapEntry->priority = $entry['priority'];
                        $sitemapEntry->changefreq = $entry['changefreq'];
                        $sitemapEntry->save();
                        array_push($allSectionIds, $id);
                    }
                }
            }
        }
        // remove all sitemaps not in the id list
        if (count($allSectionIds) == 0) {
            SitemapEntry::deleteAll(['type' => 'section']);
        } else {
            foreach (SitemapEntry::find()->where(['type' => 'section'])->andWhere(['NOT IN','linkId',$allSectionIds])->all() as $entry) {
                $entry->delete();
            }
        }

        // now save the sitemapCategories
        $sitemapCategories = $request->getBodyParam('sitemapCategories');
        // filter the enabled sections
        $allCategoryIds = [];
        if (is_array($sitemapCategories)) {
            foreach ($sitemapCategories as $key => $entry) {
                if ($entry['enabled']) {
                    // filter section id from key

                    $id = (int)str_replace('id:', '', $key);
                    if ($id > 0) {
                        // find the entry, else add one
                        $sitemapEntry = SitemapEntry::find()->where(['linkId' => $id, 'type' => 'category'])->one();
                        if (!$sitemapEntry) {
                            // insert / update this section
                            $sitemapEntry = new SitemapEntry();
                        }
                        $sitemapEntry->linkId = $id;
                        $sitemapEntry->type = 'category';
                        $sitemapEntry->priority = $entry['priority'];
                        $sitemapEntry->changefreq = $entry['changefreq'];
                        $sitemapEntry->save();
                        array_push($allCategoryIds, $id);
                    }
                }
            }
        }
        // remove all sitemaps not in the id list
        if (count($allCategoryIds) == 0) {
            SitemapEntry::deleteAll(['type' => 'category']);
        } else {
            foreach (SitemapEntry::find()->where(['type' => 'category'])->andWhere(['NOT IN','linkId',$allCategoryIds])->all() as $entry) {
                $entry->delete();
            }
        }

        // Save the product types
        $sitemapProductTypes = $request->getBodyParam('sitemapProductTypes');
        // filter the enabled sections
        $allSectionIds = [];
        if (is_array($sitemapProductTypes)) {
            foreach ($sitemapProductTypes as $key => $entry) {
                if ($entry['enabled']) {
                    // filter section id from key

                    $id = (int)str_replace('id:', '', $key);
                    if ($id > 0) {
                        // find the entry, else add one
                        $sitemapEntry = SitemapEntry::find()->where(['linkId' => $id, 'type' => 'producttype'])->one();
                        if (!$sitemapEntry) {
                            // insert / update this section
                            $sitemapEntry = new SitemapEntry();
                        }
                        $sitemapEntry->linkId = $id;
                        $sitemapEntry->type = 'producttype';
                        $sitemapEntry->priority = $entry['priority'];
                        $sitemapEntry->changefreq = $entry['changefreq'];
                        $sitemapEntry->save();
                        array_push($allSectionIds, $id);
                    }
                }
            }
        }
        // remove all sitemaps not in the id list
        if (count($allSectionIds) == 0) {
            SitemapEntry::deleteAll(['type' => 'producttype']);
        } else {
            foreach (SitemapEntry::find()->where(['type' => 'producttype'])->andWhere(['NOT IN','linkId',$allSectionIds])->all() as $entry) {
                $entry->delete();
            }
        }

        return $this->actionIndex();
    }
}
