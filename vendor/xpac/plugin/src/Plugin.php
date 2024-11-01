<?php

namespace XPACGroup\PluginFramework\v1_0_1;

use RuntimeException;

abstract class Plugin extends Container
{
    /**
     * Main plugin file
     * @var string
     * */
    private $plugin_file;

    /**
     * Run plugin on mu plugins loaded action
     * Generate MU plugin and run plugin instance earlier
     *
     * @var bool
     */
    protected $early_init = false;

    /**
     * Main singleton instance of class
     * @var array
     * */
    private static $instances = [];

    /**
     * @param null $plugin_file
     *
     * @return static
     */
    public static function instance($plugin_file = null): self
    {
        if (!isset(self::$instances[static::class])) {
            self::$instances[static::class] = new static($plugin_file);
        }

        return self::$instances[static::class];
    }

    /**
     * Bootstrap constructor.
     *
     * @param $plugin_file
     */
    protected function __construct($plugin_file)
    {
        // Configure MU components
        unset($this->components['assetsManager']);

        // Setup plugin main file
        $this->plugin_file = $plugin_file;

        // Setup hooks
        if (function_exists('register_activation_hook')) {
            register_activation_hook($this->getPluginFile(), [$this, 'onActivate']);
        }

        if (function_exists('register_deactivation_hook')) {
            register_deactivation_hook($this->getPluginFile(), [$this, 'onDeactivate']);
        }

        add_action('init', [$this, 'loadTextDomain']);

        parent::__construct();
    }

    /**
     * Get plugin data
     * @param  string  $prop  Optional: specific property key
     *
     * @return array|mixed|null
     */
    public function getPluginData(string $prop = '')
    {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data($this->plugin_file, false, false);
        return $prop ? ($plugin_data[$prop] ?? null) : $plugin_data;
    }

    /**
     * Get plugin version
     * @return string
     */
    public function getVersion(): string
    {
        if (defined('XPAC_DEV_MODE') && XPAC_DEV_MODE) {
            $version = time();
        } else {
            static $version = null;
            if (!$version) {
                $version = $this->getPluginData('Version');
            }
        }

        return $version;
    }

    /**
     * @return string
     */
    public function getMUPluginName(): string
    {
        return $this->getPluginData('Name');
    }

    /**
     * Generate MU plugin file
     * @return bool
     */
    protected function generateMUPluginFile(): bool
    {
        if (!file_exists(WPMU_PLUGIN_DIR)
            && !mkdir($concurrentDirectory = WPMU_PLUGIN_DIR, 0777, true)
            && !is_dir($concurrentDirectory)
        ) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $mu = WPMU_PLUGIN_DIR . '/' . $this->getMUPluginName() . '.php';
        $content = '<?php' . PHP_EOL;
        $content .= ' // This is auto generated file' . PHP_EOL;
        $content .= 'include_once WP_PLUGIN_DIR."/' . $this->getPluginBasename() . '";';
        return (bool)file_put_contents($mu, $content);
    }

    /**
     * Remove MU plugin file
     * @return bool
     */
    protected function removeMUPluginFile(): bool
    {
        $mu = WPMU_PLUGIN_DIR . '/' . $this->getMUPluginName() . '.php';
        return unlink($mu);
    }

    /**
     * Gets the basename of a plugin
     * @return string The name of a plugin
     */
    public function getPluginBasename(): string
    {
        return plugin_basename($this->getPluginFile());
    }

    /**
     * Get plugin main file
     * @return mixed
     */
    public function getPluginFile(): string
    {
        return $this->plugin_file;
    }

    /**
     * Get URL to plugin specific file
     * @param string $relative File url relative to plugin main URL
     * @return string
     */
    public function getPluginDirUrl(string $relative = ''): string
    {
        return plugin_dir_url($this->getPluginFile()) . $relative;
    }

    /**
     * Get path to plugin specific file
     * @param string $relative File path relative to plugin main URL
     * @return string
     */
    public function getPluginDirPath(string $relative = ''): string
    {
        return wp_normalize_path(plugin_dir_path($this->getPluginFile()) . $relative);
    }

    /**
     * Callback to execute on plugin activation
     * @return void
     */
    public function onActivate(): void
    {
        if ($this->early_init) {
            $this->generateMUPluginFile();
        }
    }

    /**
     * Callback to execute on plugin deactivation
     * @return void
     */
    public function onDeactivate(): void
    {
        if ($this->early_init) {
            $this->removeMUPluginFile();
        }
    }

    /**
     * Callback to load plugin text domain
     * @hooked in "init" action
     */
    final public function loadTextDomain(): void
    {
        if ($text_domain = $this->getPluginData('TextDomain')) {
            load_plugin_textdomain(
                $text_domain,
                false,
                dirname($this->getPluginBasename()) . '/languages'
            );
        }
    }

}
