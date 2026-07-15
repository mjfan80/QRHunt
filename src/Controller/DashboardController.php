<?php
/**
 * Dashboard controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\CheckpointPostType;
use QRHunt\PathPostType;
use QRHunt\Service\DashboardService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the QRHunt dashboard page.
 */
final class DashboardController {

	/** @var DashboardService */
	private $dashboard_service;

	/**
	 * Creates a dashboard controller.
	 *
	 * @param DashboardService $dashboard_service Dashboard service.
	 */
	public function __construct( DashboardService $dashboard_service ) {
		$this->dashboard_service = $dashboard_service;
	}

	/**
	 * Renders the dashboard page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$summary       = $this->dashboard_service->get_summary();
		$recent_events = $this->dashboard_service->get_recent_events( 10 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'QRHunt Dashboard', 'qrhunt' ); ?></h1>

			<div class="notice notice-info inline">
				<p><?php esc_html_e( 'Overview of the plugin and the latest recorded scans.', 'qrhunt' ); ?></p>
			</div>

			<div class="metabox-holder">
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Summary', 'qrhunt' ); ?></span></h2>
					<div class="inside">
						<table class="widefat striped">
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Item', 'qrhunt' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Count', 'qrhunt' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $summary as $card ) : ?>
									<tr>
										<td><?php echo esc_html( $card['label'] ); ?></td>
										<td><?php echo esc_html( (string) $card['value'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>

				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Quick actions', 'qrhunt' ); ?></span></h2>
					<div class="inside">
						<p>
							<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . PathPostType::POST_TYPE ) ); ?>">
								<?php esc_html_e( 'New Path', 'qrhunt' ); ?>
							</a>
							<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . CheckpointPostType::POST_TYPE ) ); ?>">
								<?php esc_html_e( 'New Checkpoint', 'qrhunt' ); ?>
							</a>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . GroupController::PAGE_SLUG ) ); ?>">
								<?php esc_html_e( 'New Group', 'qrhunt' ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Latest scans', 'qrhunt' ); ?></span></h2>
					<div class="inside">
						<table class="widefat striped">
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Date', 'qrhunt' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Path', 'qrhunt' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Checkpoint', 'qrhunt' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Result', 'qrhunt' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $recent_events ) ) : ?>
									<tr>
										<td colspan="4"><?php esc_html_e( 'No scans recorded yet.', 'qrhunt' ); ?></td>
									</tr>
								<?php else : ?>
									<?php foreach ( $recent_events as $event ) : ?>
										<tr>
											<td><?php echo esc_html( $event['created_at'] ); ?></td>
											<td><?php echo esc_html( $event['path_name'] ); ?></td>
											<td><?php echo esc_html( $event['checkpoint_name'] ); ?></td>
											<td><?php echo esc_html( $event['result'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
