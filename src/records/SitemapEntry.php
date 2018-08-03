<?php

namespace Stivala\Sitemap\records;

use Craft;
use craft\db\ActiveRecord;
use Stivala\Sitemap\Sitemap;

class SitemapEntry extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%stivala_sitemap_entries}}';
    }
}
