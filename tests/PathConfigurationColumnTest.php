<?php
/**
 * Path configuration list column tests.
 *
 * @package QuestUno
 */

namespace QuestUno\Tests;

use QuestUno\Controller\PathController;

/**
 * Verifies Path configuration summaries in the administration list.
 */
final class PathConfigurationColumnTest extends IntegrationTestCase {
	/**
	 * Shows diagnostics only for published Paths.
	 *
	 * @return void
	 */
	public function test_renders_diagnostics_only_for_published_paths(): void {
		$services    = $this->get_services();
		$controller  = new PathController(
			$services['path_service'],
			$services['checkpoint_service'],
			$services['path_configuration_validator']
		);
		$draft_path  = $this->create_path( array( 'status' => 'draft' ) );
		$active_path = $this->create_path();

		ob_start();
		$controller->render_list_column( 'questuno_configuration', (int) $draft_path->get_post_id() );
		$draft_output = (string) ob_get_clean();

		ob_start();
		$controller->render_list_column( 'questuno_configuration', (int) $active_path->get_post_id() );
		$active_output = (string) ob_get_clean();

		self::assertSame( '', $draft_output );
		self::assertStringContainsString( '✕ 2 errors', $active_output );
	}

	/**
	 * Includes warnings alongside blocking errors in the compact summary.
	 *
	 * @return void
	 */
	public function test_renders_warning_count_with_blocking_errors(): void {
		$services   = $this->get_services();
		$controller = new PathController(
			$services['path_service'],
			$services['checkpoint_service'],
			$services['path_configuration_validator']
		);
		$path       = $this->create_path();
		$checkpoint = $this->create_checkpoint( (int) $path->get_id() );

		ob_start();
		$controller->render_list_column( 'questuno_configuration', (int) $path->get_post_id() );
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '✕ 2 errors', $output );
		self::assertStringContainsString( '⚠ 1 warning', $output );
	}

	/**
	 * Renders the warning-only summary when the Path remains publishable.
	 *
	 * @return void
	 */
	public function test_renders_warning_only_summary_for_a_publishable_path(): void {
		$services   = $this->get_services();
		$controller = new PathController(
			$services['path_service'],
			$services['checkpoint_service'],
			$services['path_configuration_validator']
		);
		$path       = $this->create_path();
		$start      = $this->create_checkpoint( (int) $path->get_id() );
		$ordinary   = $this->create_checkpoint( (int) $path->get_id() );
		$finish     = $this->create_checkpoint( (int) $path->get_id() );

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );

		ob_start();
		$controller->render_list_column( 'questuno_configuration', (int) $path->get_post_id() );
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '⚠ 1 warning', $output );
		self::assertStringNotContainsString( '✕', $output );
		self::assertStringNotContainsString( $services['checkpoint_service']->get_checkpoint_title( (int) $ordinary->get_post_id() ), $output );
	}

	/**
	 * Renders the success summary when no diagnostics are present.
	 *
	 * @return void
	 */
	public function test_renders_ok_summary_when_no_diagnostics_are_present(): void {
		$services   = $this->get_services();
		$controller = new PathController(
			$services['path_service'],
			$services['checkpoint_service'],
			$services['path_configuration_validator']
		);
		$path       = $this->create_path();
		$start      = $this->create_checkpoint( (int) $path->get_id() );
		$finish     = $this->create_checkpoint( (int) $path->get_id() );

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );

		ob_start();
		$controller->render_list_column( 'questuno_configuration', (int) $path->get_post_id() );
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '✓ OK', $output );
	}
}
