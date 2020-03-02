<?php

namespace TightenCo\Jigsaw\Loaders;

use Illuminate\Support\Collection;
use TightenCo\Jigsaw\SiteData;

class DataLoader
{
    /** @var CollectionDataLoader */
    private $collectionDataLoader;

    public function __construct(CollectionDataLoader $collectionDataLoader)
    {
        $this->collectionDataLoader = $collectionDataLoader;
    }

    public function loadSiteData(Collection $config): SiteData
    {
        return SiteData::build($config);
    }

    public function loadCollectionData(SiteData $siteData, string $source): array
    {
        return $this->collectionDataLoader->load($siteData, $source);
    }
}
