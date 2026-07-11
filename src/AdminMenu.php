<?php
/**
 * Plugin administration menu registration.
 *
 * @package QRHunt
 */

namespace QRHunt;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the QRHunt administration menu.
 */
final class AdminMenu {

	/**
	 * Registers the QRHunt administration menu.
	 *
	 * @return void
	 */
	public function register(): void {
		add_menu_page(
			__( 'QRHunt', 'qrhunt' ),
			__( 'QRHunt', 'qrhunt' ),
			'edit_posts',
			'qrhunt',
			array( $this, 'render_dashboard' ),
			'dashicons-location-alt',
			26
		);

		add_submenu_page(
			'qrhunt',
			__( 'Paths', 'qrhunt' ),
			__( 'Paths', 'qrhunt' ),
			'edit_posts',
			'edit.php?post_type=qrhunt_path'
		);

		add_submenu_page(
			'qrhunt',
			__( 'Checkpoints', 'qrhunt' ),
			__( 'Checkpoints', 'qrhunt' ),
			'edit_posts',
			'edit.php?post_type=qrhunt_checkpoint'
		);
	}

	/**
	 * Renders the placeholder dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'QRHunt', 'qrhunt' ); ?></h1>
		</div>
		<?php
	}
}
