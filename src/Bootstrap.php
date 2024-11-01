<?php

namespace XPACGroup\Plugin\XPACLottie;

use XPACGroup\PluginFramework\v1_0_1\Plugin;
use XPACGroup\PluginFramework\v1_0_1\Component;
use XPACGroup\Plugin\XPACLottie\blocks\Lottie;

class Bootstrap extends Plugin {
	/**
	 * Child components
	 *
	 * @var Component[]
	 */
	public $components = [
		'lottie' => Lottie::class,
	];

	/**
	 * Entry point
	 *
	 * @param array|null $params Component params
	 */
	protected function main( ?array $params = [] ): void {
//		add_filter( 'block_categories_all', [ $this, 'registerBlockCategories' ] );
		add_filter( 'upload_mimes', [ $this, 'updateMimes' ] );
	}

	/**
	 * Register xpac block category
	 *
	 * @hooked in "block_categories_all" filter
	 *
	 * @param array $categories Current block categories
	 *
	 * @return array
	 */
//	public function registerBlockCategories( array $categories ): array {
//		$xpac_cat = wp_list_filter( $categories, [ 'slug' => 'xpac' ] );
//		if ( empty( $xpac_cat ) ) {
//			array_unshift(
//				$categories,
//				[
//					'slug'  => 'xpac',
//					'title' => __( 'XPAC', 'xpac-lottie' ),
//					'icon'  => null,
//				]
//			);
//		}
//
//		return $categories;
//	}

	/**
	 * Add json to mime types ( for Lottie block )
	 *
	 * @hooked in "upload_mimes" filter
	 *
	 * @param array $mimes Current mime-types
	 *
	 * @return array
	 */
	public function updateMimes( array $mimes ): array {
		$mimes[ 'json' ] = 'text/plain';

		return $mimes;
	}
}