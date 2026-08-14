<?php
/**
 * Dashboard controller.
 *
 * @package QuestUno
 */

namespace QuestUno\Controller;

use QuestUno\CheckpointPostType;
use QuestUno\Model\EventResult;
use QuestUno\PathPostType;
use QuestUno\Service\DashboardService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the QuestUno dashboard page.
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
			<h1><?php esc_html_e( 'QuestUno Dashboard', 'questuno' ); ?></h1>

			<div class="notice notice-info inline">
				<p><?php esc_html_e( 'Overview of the plugin and the latest recorded scans.', 'questuno' ); ?></p>
			</div>

			<div class="metabox-holder">
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Summary', 'questuno' ); ?></span></h2>
					<div class="inside">
						<table class="widefat striped">
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Item', 'questuno' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Count', 'questuno' ); ?></th>
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
					<h2 class="hndle"><span><?php esc_html_e( 'Quick actions', 'questuno' ); ?></span></h2>
					<div class="inside">
						<p>
							<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . PathPostType::POST_TYPE ) ); ?>">
								<?php esc_html_e( 'New Path', 'questuno' ); ?>
							</a>
							<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . CheckpointPostType::POST_TYPE ) ); ?>">
								<?php esc_html_e( 'New Checkpoint', 'questuno' ); ?>
							</a>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . GroupController::PAGE_SLUG ) ); ?>">
								<?php esc_html_e( 'New Group', 'questuno' ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Latest scans', 'questuno' ); ?></span></h2>
					<div class="inside">
						<table class="widefat striped">
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Date', 'questuno' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Path', 'questuno' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Checkpoint', 'questuno' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Result', 'questuno' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $recent_events ) ) : ?>
									<tr>
										<td colspan="4"><?php esc_html_e( 'No scans recorded yet.', 'questuno' ); ?></td>
									</tr>
								<?php else : ?>
									<?php foreach ( $recent_events as $event ) : ?>
										<tr>
											<td><?php echo esc_html( $event['created_at'] ); ?></td>
											<td><?php echo esc_html( $event['path_name'] ); ?></td>
											<td><?php echo esc_html( $event['checkpoint_name'] ); ?></td>
											<td><?php echo esc_html( $this->get_event_result_label( $event['result'] ) ); ?></td>
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

	/**
	 * Gets the localized label for an Event result.
	 *
	 * @param string $result Event result.
	 * @return string
	 */
	private function get_event_result_label( string $result ): string {
		$labels = array(
			EventResult::ACCEPTED                => __( 'Accepted', 'questuno' ),
			EventResult::DUPLICATE               => __( 'Duplicate', 'questuno' ),
			EventResult::BEFORE_FAILED           => __( 'Before Failed', 'questuno' ),
			EventResult::AFTER_FAILED            => __( 'After Failed', 'questuno' ),
			EventResult::PATH_CLOSED             => __( 'Path Closed', 'questuno' ),
			EventResult::PARTICIPATION_CANCELLED => __( 'Participation Cancelled', 'questuno' ),
		);

		return $labels[ $result ] ?? $result;
	}
}
