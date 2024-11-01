<?php

namespace XPACGroup\Plugin\XPACLottie\blocks;

use WP_Block_Type;
use XPACGroup\PluginFramework\v1_0_1\Component;
use XPACGroup\Plugin\XPACLottie\Bootstrap;

/**
 * Class BaseBlock
 *
 * @package XPACGroup\Plugin\XPACLottie\blocks
 * @method Bootstrap getParent()
 */
abstract class BaseBlock extends Component {

	/**
	 * @var false|WP_Block_Type
	 */
	protected $block = false;

	/**
	 * Entry point
	 *
	 * @param array|null $params
	 */
	protected function main( ?array $params = [] ): void {
		add_action( 'init', [ $this, 'register' ], 9 );
	}

	/**
	 * Get relative file content
	 *
	 * @param string $relative Path to file relative to plugin directory
	 *
	 * @return string
	 */
	final protected function getFileContent( string $relative ): string {
		return (string) @file_get_contents( $this->getParent()->getPluginDirPath( $relative ) );
	}

	/**
	 * Callback to register block
	 *
	 * @hooked in "init" action
	 */
	abstract public function register(): void;
}