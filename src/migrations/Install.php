<?php

namespace Stivala\Sitemap\migrations;

use Craft;
use craft\db\Migration;
use craft\config\DbConfig;
use Stivala\Sitemap\Sitemap;

class Install extends Migration
{
    /**
     * @var string The database driver to use
     */
    public $driver;

    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

    // sitemap_sitemaprecord table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%stivala_sitemap_entries}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%stivala_sitemap_entries}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                // Custom columns in the table
                    'linkId' => $this->integer()->notNull(),
                    'type' => $this->string(30)->notNull()->defaultValue(''),
                    'priority' => $this->double(2)->notNull()->defaultValue(0.5),
                    'changefreq' => $this->string(30)->notNull()->defaultValue(''),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
    // sitemap_sitemaprecord table
        $this->createIndex(
            $this->db->getIndexName(
                '{{%stivala_sitemap_entries}}',
                ['type', 'linkId'],
                true
            ),
            '{{%stivala_sitemap_entries}}',
            ['type', 'linkId'],
            true
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%stivala_sitemap_entries}}');
    }
}
