<?php
/**
 * Plugin Name: XPAC Lottie Interactive Animations
 * Description: A powerful tool to add impressive animations to your website created in a Wordpress native editor, optimized for performance and Full Site Editing.
 * Version: 1.0.0
 * Author: XPAC
 * Author URI: https://novembit.com
 * License: GPLv3
 * Text Domain: xpac-lottie
 */

defined('ABSPATH') || exit;

include_once __DIR__ . '/vendor/autoload.php';

\XPACGroup\Plugin\XPACLottie\Bootstrap::instance(__FILE__);