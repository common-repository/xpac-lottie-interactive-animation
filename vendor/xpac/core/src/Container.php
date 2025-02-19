<?php
namespace XPACGroup\Core\v1_0_1;

abstract class Container
{
    /**
     * @var array
     * */
    private $instances = [];

    /**
     * @var self|null
     */
    private $parent;

    /**
     * Child component
     * @var array
     */
    public $components = [];

	/**
	 * Plugin rest base
	 */
	protected const REST_NAMESPACE = 'xpac-studio/v1';

	/**
	 * Get rest namespace
	 * @return string
	 */
	final public function getRestNamespace(): string
	{
		return self::REST_NAMESPACE;
	}

	/**
	 * Check whether current request is a rest api request
	 *
	 * @return bool
	 */
	final public function isRestApiRequest(): bool
	{
		if (empty($_SERVER['REQUEST_URI'])) {
			return false;
		}

		return false !== strpos($_SERVER['REQUEST_URI'], trailingslashit(rest_get_url_prefix()));
	}

	/**
	 * Get URL to specific rest route
	 * @param string $route The route
	 * @return string
	 */
	final public function getRestRouteUrl(string $route): string
	{
		return trailingslashit(get_rest_url(null, $this->getRestNamespace())) . trim($route, '/');
	}

    /**
     * Get call child components
     * @return array
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @param mixed $name
     * @return bool
     */
    public function hasComponent(string $name): bool
    {
        return isset($this->instances[self::toSnakeCase($name)]);
    }

    /**
     * Get component instance
     * @param string $name  Component key
     * @return mixed|null
     */
    public function getComponent(string $name): ?self
    {
        return $this->instances[self::toSnakeCase($name)] ?? null;
    }

    /**
     * Initialize single component
     * @param string $name Component key
     * @param string|array $component Component body
     */
    final public function initComponent(string $name, $component): void
    {
        if (is_numeric($name)) {
            throw new \RuntimeException('SubComponent name must be declared in components array as key.');
        }

        $name = self::toSnakeCase($name);

        $config = [];
        if (is_array($component)) {
            $config = $component[1];
            $component = $component[0];
        }

        if (class_exists($component) && is_subclass_of($component, self::class)) {
            $this->instances[$name] = $component::init($this, $config);
        }
    }

    /**
     * Init sub-components
     */
    final public function initComponents(): void
    {
        foreach ($this->getComponents() as $name => $component) {
            $this->initComponent($name, $component);
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $prefix = substr($name, 0, 3);
        $key = ltrim(substr($name, 3), '_');

        if ($prefix === 'get' && ($method_name = self::toSnakeCase($key)) && isset($this->instances[$method_name])) {
            return $this->instances[$method_name];
        } elseif (!isset($this->{$name}) || !is_callable([$this, $name])) {
            trigger_error('Call to undefined method ' . static::class . '::' . $name . '()', E_USER_ERROR);
        }

        return $this->{$name};
    }

    /**
     * @param $key
     * @return mixed|self
     */
    public function __get($key)
    {
        return $this->getComponent(self::toSnakeCase($key)) ?? $this->{$key};
    }

    /**
     * @param string $input
     * @return string
     */
    private static function toSnakeCase(string $input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * Early setup component
     *
     * @param  array  $params  Component params
     */
    protected function wakeup(array $params = []): void
    {
    }

    /**
     * @param array|null $params
     */
    protected function main(?array $params = []): void
    {
    }

    /**
     * Component constructor.
     * @param Container|null $parent  Optional: parent component
     * @param array $params  optional: Component params
     */
    protected function __construct(?Container $parent = null, array $params = [])
    {
        $this->parent = $parent;

        $this->wakeup($params);

        $this->initComponents();

        $this->main($params);
    }

    /**
     * @param Container|null $parent
     * @param array $params
     * @return static
     */
    public static function init(?Container $parent = null, array $params = []): self
    {
        return new static($parent, $params);
    }

    /**
     * @return $this|null
     */
    public function getParent(): ?Container
    {
        return $this->parent;
    }
}