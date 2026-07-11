<?php
/**
 * Plugin Name: QRHunt
 * Description: Creates interactive experiences based on QR Code checkpoints.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: QRHunt
 * License: LGPL-3.0-or-later
 * Text Domain: qrhunt
 *
 * @package QRHunt
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook( __FILE__, array( QRHunt\Activation::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( QRHunt\Deactivation::class, 'deactivate' ) );

$qrhunt_plugin = new QRHunt\Plugin();
$qrhunt_plugin->register_hooks();
