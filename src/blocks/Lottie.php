<?php

namespace XPACGroup\Plugin\XPACLottie\blocks;

use XPACGroup\Plugin\XPACLottie\Bootstrap;

/**
 * Class Lottie
 *
 * @package XPACGroup\Plugin\XPACLottie\blocks
 * @method Bootstrap getParent()
 */
class Lottie extends BaseBlock {

	/**
	 * Callback to register block based in its metadata
	 *
	 * @hooked in "init" action
	 */
	public function register(): void {
		$this->block = register_block_type(
			$this->getParent()->getPluginDirPath( 'dist/blocks/lottie' )
		);
		if($this->block) {
			if ( is_admin() ) {
				wp_deregister_script( $this->block->script );
			} else {
				// ensure script is loaded in footer
				wp_scripts()->add_data( $this->block->script, 'group', 1 );
			}
			wp_set_script_translations( $this->block->editor_script, 'xpac-lottie' );
		}
	}
}