<?php

namespace SamIT\Yii2\StaticAssets;

use Yii;
use yii\web\AssetManager;

/**
 * Class ReadOnlyAssetManager
 * Asset manager that does not actually (re)publish files. Use it in production when the assets are part of the nginx
 * container
 * @package SamIT\Yii2\StaticAssets
 */
class ReadOnlyAssetManager extends AssetManager
{
    /**
     * @var bool Whether to enable asset development mode.
     */
    public $assetDevelopmentMode = false;

    /**
     * Initialize asset manager
     */
    public function init()
    {
        if ($this->assetDevelopmentMode) {
            $this->forceCopy = true;
        }

        $this->basePath = Yii::getAlias($this->basePath);
        $this->baseUrl = \rtrim(Yii::getAlias($this->baseUrl), '/');
    }

    /**
     * @param string $src
     * @return array|string[]
     */
    protected function publishFile($src)
    {
        if ($this->assetDevelopmentMode) {
            return parent::publishFile($src);
        }

        $dir = $this->hash($src);
        $fileName = \basename($src);
        $dstDir = "{$this->basePath}/{$dir}";
        $dstFile = "{$dstDir}/{$fileName}";

        return [$dstFile, "{$this->baseUrl}/{$dir}/{$fileName}"];
    }

    /**
     * @param string $src
     * @param array $options
     * @return array|string[]
     */
    protected function publishDirectory($src, $options)
    {
        if ($this->assetDevelopmentMode) {
            return parent::publishDirectory($src, $options);
        }

        $dir = $this->hash($src);
        $dstDir = "{$this->basePath}/{$dir}";

        return [$dstDir, "{$this->baseUrl}/{$dir}"];
    }

    /**
     * @param string $path
     * @return mixed|string
     */
    protected function hash($path)
    {
        return \call_user_func(Module::hashCallback(), $path);
    }
}