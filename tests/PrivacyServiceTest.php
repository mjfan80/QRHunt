<?php
/**
 * Privacy settings tests.
 *
 * @package QRHunt
 */

namespace QRHunt\Tests;

use QRHunt\Service\PrivacyService;

/**
 * Verifies independent opt-in collection of Event data.
 */
final class PrivacyServiceTest extends IntegrationTestCase {
	/**
	 * Restores privacy settings after every test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( PrivacyService::OPTION_NAME );
		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] );
		parent::tear_down();
	}

	/**
	 * Leaves both values null by default and records them independently when enabled.
	 *
	 * @return void
	 */
	public function test_collects_ip_address_and_user_agent_independently(): void {
		$services                    = $this->get_services();
		$path                        = $this->create_path();
		$start                       = $this->create_checkpoint( (int) $path->get_id() );
		$middle                      = $this->create_checkpoint( (int) $path->get_id() );
		$finish                      = $this->create_checkpoint( (int) $path->get_id() );
		$participation               = $this->create_participation( self::factory()->user->create(), (int) $path->get_id() );
		$privacy_service             = new PrivacyService();
		$_SERVER['REMOTE_ADDR']      = '203.0.113.42';
		$_SERVER['HTTP_USER_AGENT']  = 'QRHunt Test Agent';
		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );

		self::assertNull( $privacy_service->get_ip_address() );
		self::assertNull( $privacy_service->get_user_agent() );
		$services['scan_service']->scan_checkpoint( $participation, $start );
		$events = $services['event_service']->get_events_by_participation( (int) $participation->get_id() );
		self::assertNull( $events[0]->get_ip_address() );
		self::assertNull( $events[0]->get_user_agent() );

		update_option( PrivacyService::OPTION_NAME, array( 'record_ip_address' => true, 'record_user_agent' => false ) );
		self::assertSame( '203.0.113.42', $privacy_service->get_ip_address() );
		self::assertNull( $privacy_service->get_user_agent() );
		$services['scan_service']->scan_checkpoint( $participation, $middle );
		$events = $services['event_service']->get_events_by_participation( (int) $participation->get_id() );
		self::assertSame( '203.0.113.42', $events[0]->get_ip_address() );
		self::assertNull( $events[0]->get_user_agent() );

		update_option( PrivacyService::OPTION_NAME, array( 'record_ip_address' => false, 'record_user_agent' => true ) );
		self::assertNull( $privacy_service->get_ip_address() );
		self::assertSame( 'QRHunt Test Agent', $privacy_service->get_user_agent() );
		$services['scan_service']->scan_checkpoint( $participation, $start );
		$events = $services['event_service']->get_events_by_participation( (int) $participation->get_id() );
		self::assertNull( $events[0]->get_ip_address() );
		self::assertSame( 'QRHunt Test Agent', $events[0]->get_user_agent() );
	}
}
