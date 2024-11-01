<?php

namespace XPACGroup\Core\v1_0_1\components;

/**
 * Class AssetsManager
 * @package XPACGroup\Core\components
 */
final class AssetsManager
{
    /**
     * Whether
     * @var bool
     */
    private $did_run = false;

    /**
     * Assets root URL
     * @var string
     */
    private $url;

    /**
     * Assets root path
     * @var string
     */
    private $path;

    /**
     * All styles
     * @var array
     */
    private $styles = [];

    /**
     * All orphan styles
     * @var array
     */
    private $orphanStyles = [];

    /**
     * All scripts
     * @var array
     */
    private $scripts = [];

    /**
     * All orphan scripts
     * @var array
     */
    private $orphanScripts = [];

    /**
     * Version to use for assets
     * @var string
     */
    private $version;

    /**
     * AssetsManager constructor.
     *
     * @param string $url Assets root URL
     * @param string $path Assets root path
     * @param string $version Version to use for attached assets
     */
    public function __construct(string $url, string $path, string $version)
    {
        $this->url = trailingslashit($url);
        $this->path = $path;
        $this->version = $version;
    }

    /**
     * Get assets root URL
     *
     * @param string $relative Optional: relative url to specific location
     *
     * @return string
     */
    public function getUrl(string $relative = ''): string
    {
        return $this->url ? $this->url . $relative : '';
    }

    /**
     * Get assets root URL with versioning query argument
     *
     * @param string $relative Optional: relative url to specific location
     *
     * @return string
     */
    public function getVersionedUrl(string $relative = ''): string
    {
        return $this->url ? add_query_arg('ver', $this->getVersion(), $this->url . $relative) : '';
    }

    /**
     * Get assets root path
     *
     * @param string $relative Optional: relative path to specific location
     *
     * @return string
     */
    public function getPath(string $relative = ''): string
    {
        return $this->path ? wp_normalize_path($this->path . '/' . $relative) : '';
    }

    /**
     * Get basic configuration for assets item
     * @return array
     */
    private function getAssetBasics(): array
    {
        return [
            'action' => ['init' => 10],
        ];
    }

    /**
     * Extract action name from configuration
     * @param string|int $key  Configuration key for checking
     * @param string|int $value  Configuration value
     * @return string
     */
    private function extractActionName($key, $value): string
    {
        return is_numeric($key) ? $value : $key;
    }

    /**
     * Extract action name from configuration
     * @param string|int $key  Configuration key for checking
     * @param string|int $value  Configuration value
     * @return string|int
     */
    private function extractActionPriority($key, $value)
    {
        return is_numeric($key) ? 10 : $value;
    }

    /**
     * Execute single style
     *
     * @param string $handle Handle name
     * @param array $config Handle config
     */
    private function executeStyle(string $handle, array $config): void
    {
        $actions = (array)$config['action'];
        foreach ($actions as $key => $value) {
            add_action(
                $this->extractActionName($key, $value),
                function () use ($handle) {
                    $config = $this->parseAssetConfig($this->getStyle($handle), $handle);
                    $this->updateStyle($handle, $config);
                    $this->enqueueStyle($handle, $config);
                },
                $this->extractActionPriority($key, $value)
            );
        }
    }

    /**
     * Execute styles
     */
    private function executeStyles(): void
    {
        foreach ($this->getStyles() as $handle => $config) {
            $this->executeStyle($handle, $config);
        }
    }

    /**
     * Add a single style
     *
     * @param string $handle Handle name
     * @param array $config Style configuration
     *
     * @return $this
     */
    public function addStyle(string $handle, array $config): AssetsManager
    {
        if (!isset($this->styles[$handle])) {
            $this->styles[$handle] = wp_parse_args($config, $this->getAssetBasics());
            if ($this->did_run) {
                $this->executeStyle($handle, $this->styles[$handle]);
            }
        }

        return $this;
    }

    /**
     * Update specific style
     *
     * @param string $handle Handle name
     * @param array $config Style configuration
     *
     * @return $this
     */
    public function updateStyle(string $handle, array $config): AssetsManager
    {
        if (isset($this->styles[$handle])) {
            $this->styles[$handle] = wp_parse_args($config, $this->getAssetBasics());
        }

        return $this;
    }

    /**
     * Remove specific style
     *
     * @param string $handle Handle name
     *
     * @return $this
     */
    public function removeStyle(string $handle): AssetsManager
    {
        unset($this->styles[$handle]);

        return $this;
    }

    /**
     * Check whether orphan style handle exists
     * @param string $handle Handle name
     *
     * @return bool
     */
    public function hasOrphanStyle(string $handle): bool
    {
        return in_array($handle, $this->orphanStyles);
    }

    /**
     * Register and enqueue orphan inline style
     *
     * @param string $handle Name of the stylesheet
     * @param string $data String containing the CSS styles to be added.
     */
    public function addOrphanStyle(string $handle, string $data): void
    {
        if (!$this->hasOrphanStyle($handle)) {
            $this->orphanStyles[] = $handle;
            wp_register_style($handle, false);
            wp_enqueue_style($handle);
            wp_add_inline_style($handle, $data);
        }
    }

    /**
     * Get specific style
     *
     * @param string $handle Handle name
     *
     * @return array|null
     */
    public function getStyle(string $handle): ?array
    {
        return $this->styles[$handle] ?? null;
    }

    /**
     * Get all styles
     * @return array
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * Execute single script
     *
     * @param string $handle Handle name
     * @param array $config Handle config
     */
    private function executeScript(string $handle, array $config): void
    {
        $actions = (array)$config['action'];
        foreach ($actions as $key => $value) {
            add_action(
                $this->extractActionName($key, $value),
                function () use ($handle) {
                    $config = $this->parseAssetConfig($this->getScript($handle), $handle);
                    $this->updateScript($handle, $config);
                    $this->enqueueScript($handle, $config);
                },
                $this->extractActionPriority($key, $value)
            );
        }
    }

    /**
     * Execute scripts
     */
    private function executeScripts(): void
    {
        foreach ($this->getScripts() as $handle => $config) {
            $this->executeScript($handle, $config);
        }
    }

    /**
     * Add a single script
     *
     * @param string $handle Handle name
     * @param array $config Script configuration
     *
     * @return $this
     */
    public function addScript(string $handle, array $config): AssetsManager
    {
        if (!isset($this->scripts[$handle])) {
            $this->scripts[$handle] = wp_parse_args($config, $this->getAssetBasics());
            if ($this->did_run) {
                $this->executeScript($handle, $this->scripts[$handle]);
            }
        }

        return $this;
    }

    /**
     * Update specific script
     *
     * @param string $handle Handle name
     * @param array $config Script configuration
     *
     * @return $this
     */
    public function updateScript(string $handle, array $config): AssetsManager
    {
        if (isset($this->scripts[$handle])) {
            $this->scripts[$handle] = wp_parse_args($config, $this->getAssetBasics());
        }

        return $this;
    }

    /**
     * Remove specific script
     *
     * @param string $handle Handle name
     *
     * @return $this
     */
    public function removeScript(string $handle): AssetsManager
    {
        unset($this->scripts[$handle]);

        return $this;
    }

    /**
     * Check whether orphan script handle exists
     * @param string $handle Handle name
     *
     * @return bool
     */
    public function hasOrphanScript(string $handle): bool
    {
        return in_array($handle, $this->orphanScripts);
    }

    /**
     * Register and enqueue orphan inline script
     *
     * @param string $handle Name of the script
     * @param string $data String containing the JavaScript to be added
     * @param bool $in_footer Whether to enqueue the script before </body> instead of in the <head>
     */
    public function addOrphanScript(string $handle, string $data, bool $in_footer = false): void
    {
        if (!$this->hasOrphanScript($handle)) {
            $this->orphanScripts[] = $handle;
            wp_register_script($handle, false, [], false, $in_footer);
            wp_enqueue_script($handle);
            wp_add_inline_script($handle, $data);
        }
    }

    /**
     * Localize script
     *
     * @param string $handle Handle name
     * @param string $name Variable name to use
     * @param mixed $data Localization data
     *
     * @return $this
     */
    public function localizeScript(string $handle, string $name, $data): AssetsManager
    {
        if (isset($this->scripts[$handle])) {
            $handle_data = $this->scripts[$handle]['data'] ?? [];
            $handle_data[$name] = isset($handle_data[$name]) ? array_merge(
                $handle_data[$name],
                $data
            ) : $data;
            $this->scripts[$handle]['data'] = $handle_data;
        }

        return $this;
    }

    /**
     * Get specific script
     *
     * @param string $handle Handle name
     *
     * @return array|null
     */
    public function getScript(string $handle): ?array
    {
        return $this->scripts[$handle] ?? null;
    }

    /**
     * Get all scripts
     * @return array
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * Get configured version
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Parse single asset configuration
     *
     * @param array $config Configuration to process
     * @param string $handle Handle name
     * @return array
     */
    private function parseAssetConfig(array $config, string $handle): array
    {
        if (isset($config['callback']) && is_callable($config['callback'])) {
            $callback_args = array_diff_key($config, array_flip(['action', 'callback']));
            $user_config = call_user_func($config['callback'], $callback_args, $handle);
            if ($user_config) {
                $config = array_merge($config, (array)$user_config);
            }
        }
        unset($config['action'], $config['callback']);

        return $config;
    }

    /**
     * Run component
     */
    public function run(): void
    {
        if ((!empty($this->getStyles()) || !empty($this->getScripts())) && !$this->getUrl()) {
            trigger_error('Asset manager\'s $url property in not configured', E_USER_ERROR);
        }

        $this->executeStyles();
        $this->executeScripts();
        $this->did_run = true;
    }

    /**
     * Callback method to edit style tag and modify its attributes
     * @hooked in "style_loader_tag" filter
     *
     * @param string $tag Current tag
     * @param string $handle Style handle name
     *
     * @return string
     */
    public function editStyleLoaderTag(string $tag, string $handle): string
    {
        $styles = array_filter($this->getStyles(), function ($style) {
            return isset($style['attributes']) && !empty($style['attributes']);
        });

        if (isset($styles[$handle])) {
            $attrs = [];
            foreach ($styles[$handle]['attributes'] as $name => $value) {
                if (is_numeric($name) || !in_array($name, ['id', 'href'])) {
                    if (in_array($name, ['rel', 'media'])) {
                        $tag = preg_replace('/\s' . $name . '=("([^"]+)"|\'([^\']+)\')/', '', $tag);
                        if (!$value) {
                            continue;
                        }
                    }
                    $attrs[] = is_numeric($name) ? $value : ($name . '="' . $value . '"');
                }
            }

            if (!empty($attrs)) {
                $attrs[] = '/>';
                $tag = str_replace('/>', join(' ', $attrs), $tag);
            }
        }

        return $tag;
    }

    /**
     * Enqueue single style
     *
     * @param string $handle Handle name
     * @param array $config Handle configuration
     */
    private function enqueueStyle(string $handle, array $config): void
    {
        $config = wp_parse_args(
            $config,
            [
                'url' => '',
                'deps' => [],
                'asset' => '',
                'version' => $this->getVersion(),
                'media' => 'all',
                'external' => false,
                'attributes' => [],
                'preload' => [],
                'register' => false,
                'withPath' => false,
                'deferAs' => ''
            ]
        );

        if (!$config['url']) {
            return;
        }

        if (is_array($config['preload']) && !empty($config['preload'])) {
            $preload_config = array_merge($config, [
                'preload' => [],
                'attributes' => array_merge($config['preload'], [
                    'rel' => 'preload'
                ]),
                'register' => false,
                'withPath' => false,
                'deferAs' => ''
            ]);
            $this->addStyle("{$handle}-preload", $preload_config);
            $this->enqueueStyle("{$handle}-preload", $preload_config);
        }

        if (is_array($config['attributes']) && !empty($config['attributes'])) {
            add_filter('style_loader_tag', [$this, 'editStyleLoaderTag'], 10, 2);
        }

        $config['dependencies'] = $config['deps'];
        unset($config['deps']);
        if ($config['asset']) {
            $assets = [];
            $asset_file = $this->getPath($config['asset']);
            if (file_exists($asset_file)) {
                $assets = include($asset_file);
            }
            $config = $this->mergeRecursive($config, $assets);
        }

        if (!!$config['register']) {
            wp_register_style(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getUrl($config['url'])),
                (array)$config['dependencies'],
                $config['version'],
                $config['media']
            );
        } else {
            wp_enqueue_style(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getUrl($config['url'])),
                (array)$config['dependencies'],
                $config['version'],
                $config['media']
            );
        }

        if (!!$config['withPath'] && !$config['external']) {
            wp_style_add_data($handle, 'path', $this->getUrl($config['url']));
        }

        if ($config['deferAs'] && class_exists('\XPACGroup\Plugin\Optimizations\StylePrioritizer')) {
            \XPACGroup\Plugin\Optimizations\StylePrioritizer::mark($handle, $config['deferAs']);
        }
    }

    /**
     * Callback method to edit script tag and modify its attributes
     * @hooked in "script_loader_tag" filter
     *
     * @param string $tag Current tag
     * @param string $handle Script handle name
     *
     * @return string
     */
    public function editScriptLoaderTag(string $tag, string $handle): string
    {
        $scripts = array_filter($this->getScripts(), function ($script) {
            return isset($script['attributes']) && !empty($script['attributes']);
        });

        if (isset($scripts[$handle])) {
            $attrs = [];
            $inline_script_attrs = ['before' => [], 'after' => []];
            foreach ($scripts[$handle]['attributes'] as $name => $value) {
                switch (true) {
                    case ('inline-before' === $name):
                        $inline_script_attrs['before'] = $value;
                        break;
                    case ('inline-after' === $name):
                        $inline_script_attrs['after'] = $value;
                        break;
                    case is_numeric($name):
                    case !in_array($name, ['id', 'src']):
                        $attrs[] = is_numeric($name) ? $value : ($name . '="' . $value . '"');
                        break;
                }
            }

            if (!empty($attrs)) {
                array_unshift($attrs, '');
                $tag = preg_replace('/><\/script>/', join(' ', $attrs) . '$0', $tag);
            }

            foreach (['before', 'after'] as $position) {
                if (!empty($inline_script_attrs[$position])) {
                    $attrs = [];
                    foreach ($inline_script_attrs[$position] as $name => $value) {
                        $attrs[] = is_numeric($name) ? $value : ($name . '="' . $value . '"');
                    }
                    array_unshift($attrs, '');
                    $tag = preg_replace(
                        "/id=[\"|']{$handle}-js-{$position}[\"|']/",
                        '$0' . join(' ', $attrs),
                        $tag
                    );
                }
            }
        }

        return $tag;
    }

    private function mergeRecursive(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => & $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeRecursive($merged[$key], $value);
            } else if (is_numeric($key)) {
                if (!in_array($value, $merged)) {
                    $merged[] = $value;
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Enqueue single script
     *
     * @param string $handle Handle name
     * @param array $config Handle configuration
     */
    private function enqueueScript(string $handle, array $config): void
    {
        $config = wp_parse_args(
            $config,
            [
                'url' => '',
                'deps' => [],
                'asset' => '',
                'version' => $this->getVersion(),
                'in_footer' => false,
                'external' => false,
                'attributes' => [],
                'preload' => [],
                'data' => [],
                'register' => false,
            ]
        );

        if (!$config['url']) {
            return;
        }

        if (is_array($config['preload']) && !empty($config['preload'])) {
            $preload_config = array_merge($config, [
                'preload' => [],
                'attributes' => array_merge($config['preload'], [
                    'rel' => 'preload'
                ]),
                'register' => false,
                'data' => []
            ]);
            $this->addScript("{$handle}-preload", $preload_config);
            $this->enqueueScript("{$handle}-preload", $preload_config);
        }

        if (is_array($config['attributes']) && !empty($config['attributes'])) {
            add_filter('script_loader_tag', [$this, 'editScriptLoaderTag'], 10, 2);
        }

        $config['dependencies'] = $config['deps'];
        unset($config['deps']);
        if ($config['asset']) {
            $assets = [];
            $asset_file = $this->getPath($config['asset']);
            if (file_exists($asset_file)) {
                $assets = include($asset_file);
            }
            $config = $this->mergeRecursive($config, $assets);
        }

        // 1. Register / Enqueue
        if (!!$config['register']) {
            wp_register_script(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getUrl($config['url'])),
                (array)$config['dependencies'],
                $config['version'],
                $config['in_footer']
            );
        } else {
            wp_enqueue_script(
                $handle,
                (!!$config['external'] ? $config['url'] : $this->getUrl($config['url'])),
                (array)$config['dependencies'],
                $config['version'],
                $config['in_footer']
            );
        }

        // 2. Localize
        foreach ($config['data'] as $name => $data) {
            wp_localize_script($handle, $name, $data);
        };
    }

    /**
     * Register script dependencies
     * @param string $handle Script handle name
     * @param string|array $deps Dependencies handles names
     */
    public function registerScriptDependency(string $handle, $deps): void
    {
        if (!empty($deps) && ($script = wp_scripts()->registered[$handle] ?? null)) {
            $script->deps = array_unique(array_merge($script->deps, (array)$deps));
        }
    }

    /**
     * Register block script dependencies
     * @param string $block_name Block name
     * @param bool $editor Whether to use editor or front script
     * @param string|array $deps Dependencies handles names
     */
    public function registerBlockScriptDependency(string $block_name, bool $editor, $deps): void
    {
        if ($instance = \WP_Block_Type_Registry::get_instance()->get_registered($block_name)) {
            $handle = 'wp-block-library';
            if (0 !== strpos($block_name, 'core/')) {
                $handle = $instance->{($editor ? 'editor_' : '') . 'script'};
            }
            $this->registerScriptDependency($handle, $deps);
        }
    }

}