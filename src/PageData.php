<?php

namespace TightenCo\Jigsaw;

class PageData extends IterableObject
{
    public static function withPageMetaData(IterableObject $siteData, array $meta): self
    {
        $pageData = new static($siteData->except('page'));
        $pageData->put('page', (new PageVariable($siteData->page))->put('_meta', new IterableObject($meta)));

        return $pageData;
    }

    public function setPageVariableToCollectionItem(string $collectionName, string $itemName): void
    {
        $this->put('page', $this->get($collectionName)->get($itemName));
    }

    public function setExtending(string $templateToExtend): void
    {
        $this->page->_meta->put('extending', $templateToExtend);
    }

    public function setPagePath(string $path): void
    {
        $this->page->_meta->put('path', $path);
        $this->updatePageUrl();
    }

    public function updatePageUrl(): void
    {
        $this->page->_meta->put('url', rightTrimPath($this->page->getBaseUrl()) . '/' . trimPath($this->page->getPath()));
    }
}
