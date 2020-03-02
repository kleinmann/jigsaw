<?php

namespace TightenCo\Jigsaw\File;

use Illuminate\Support\Arr;

class ConfigFile
{
    /** @var array */
    public $config;

    public function __construct($configPath, $helpersPath = '')
    {
        $config = file_exists($configPath) ? include $configPath : [];
        $helpers = file_exists($helpersPath) ? include $helpersPath : [];

        $this->config = array_merge($config, $helpers);
        $this->convertStringCollectionsToArray();
    }

    protected function convertStringCollectionsToArray(): void
    {
        $collections = Arr::get($this->config, 'collections');

        if ($collections) {
            $this->config['collections'] = collect($collections)->flatMap(function ($value, $key) {
                return is_array($value) ? [$key => $value] : [$value => []];
            });
        }
    }
}
