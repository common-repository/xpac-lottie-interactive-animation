<?php

namespace XPACGroup\PluginFramework\v1_0_1;

use XPACGroup\Core\v1_0_1\components\AssetsManager;

abstract class Container extends \XPACGroup\Core\v1_0_1\Container
{

    /**
     * Component version
     * @var null|string
     */
    protected $version;

    /**
     * Get component version
     * @return string
     */
    public function getVersion(): string
    {
        $fallback = 'N/A';

        return $this->version ?? ($this->getParent() ? $this->getParent()->getVersion() : $fallback) ?? $fallback;
    }

    /**
     * Assets manager instance
     * @var AssetsManager|null
     */
    private $assets_manager;

    /**
     * Setup assets manager instance
     *
     * @param string  $url  URL to assets root
     * @param string $path  Path to assets root
     * @param string  $version  Version to use
     */
    final public function setupAssetsManager(string $url, string $path, string $version)
    {
        if (!isset($this->assets_manager) && $url && $path && $version) {
            $this->assets_manager = new AssetsManager($url, $path, $version);
        }
    }

    /**
     * Get assets manager instance
     * @return AssetsManager|null
     */
    final public function getAssetsManager(): ?AssetsManager
    {
        return $this->assets_manager ?? ($this->getParent() ? $this->getParent()->getAssetsManager() : null) ?? null;
    }

    /**
     * Component constructor.
     * @param  Container|null  $parent  Optional: parent component
     * @param  array  $params  optional: Component params
     */
    protected function __construct(?Container $parent = null, array $params = [])
    {
        parent::__construct($parent, $params);
        if (isset($this->assets_manager)) {
            $this->assets_manager->run();
        }
    }

}