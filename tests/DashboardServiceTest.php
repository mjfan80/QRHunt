<?php
/**
 * Dashboard metric tests.
 *
 * @package QRHunt
 */

namespace QRHunt\Tests;

use QRHunt\Model\Dependency;
use QRHunt\Model\DependencyTargetType;
use QRHunt\Model\DependencyType;
use QRHunt\Service\DashboardService;

/**
 * Verifies the essential dashboard counters.
 */
final class DashboardServiceTest extends IntegrationTestCase {
	/**
	 * Includes active, invalid and duplicate scan metrics in the dashboard summary.
	 *
	 * @return void
	 */
	public function test_summary_includes_required_scan_metrics(): void {
		$services      = $this->get_services();
		$path          = $this->create_path();
		$start         = $this->create_checkpoint( (int) $path->get_id() );
		$blocked       = $this->create_checkpoint( (int) $path->get_id() );
		$finish        = $this->create_checkpoint( (int) $path->get_id() );
		$participation = $this->create_participation( self::factory()->user->create(), (int) $path->get_id() );
		$dependency    = new Dependency();
		$dependency->set_type( DependencyType::AFTER );
		$dependency->set_target_type( DependencyTargetType::CHECKPOINT );
		$dependency->set_target_id( (int) $finish->get_post_id() );
		$services['dependency_repository']->save_for_checkpoint( (int) $blocked->get_post_id(), array( $dependency ) );
		$blocked = $services['checkpoint_service']->get_checkpoint_with_dependencies( (int) $blocked->get_post_id() );

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );
		$services['scan_service']->scan_checkpoint( $participation, $start );
		$services['scan_service']->scan_checkpoint( $participation, $start );
		$services['scan_service']->scan_checkpoint( $participation, $blocked );

		$dashboard = new DashboardService(
			$services['path_service'],
			$services['checkpoint_service'],
			$services['group_service'],
			$services['participation_service'],
			$services['event_service']
		);
		$summary = array_column( $dashboard->get_summary(), 'value', 'label' );

		self::assertSame( 1, $summary['Active Paths'] );
		self::assertSame( 1, $summary['Active Participations'] );
		self::assertSame( 3, $summary['Total scans'] );
		self::assertSame( 1, $summary['Invalid scans'] );
		self::assertSame( 1, $summary['Duplicate scans'] );
	}
}
