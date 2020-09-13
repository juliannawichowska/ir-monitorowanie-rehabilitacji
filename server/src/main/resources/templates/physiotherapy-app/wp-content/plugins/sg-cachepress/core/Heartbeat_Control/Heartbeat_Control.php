<?php
namespace SiteGround_Optimizer\Heartbeat_Control;

/**
 * SG Heartbeat_Control main plugin class
 */
class Heartbeat_Control {

	/**
	 * Create a {@link Heartbeat_Control} instance.
	 *
	 * @since 5.6.0
	 */
	public function __construct() {
		// Bail if the setting is disabled.
		if ( 0 === intval( get_option( 'siteground_optimizer_heartbeat_control', 0 ) ) ) {
			return;
		}

		if ( @strpos( $_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php' ) ) {
			return;
		}

		$this->options = array(
			'post' => array(
				'status'   => intval( get_option( 'siteground_optimizer_heartbeat_post_status', 0 ) ),
				'interval' => intval( get_option( 'siteground_optimizer_heartbeat_post_interval', 0 ) ),
			),
			'dashboard' => array(
				'status'   => intval( get_option( 'siteground_optimizer_heartbeat_dashboard_status', 0 ) ),
				'interval' => intval( get_option( 'siteground_optimizer_heartbeat_dashboard_interval', 0 ) ),
			),
			'frontend' => array(
				'status'   => intval( get_option( 'siteground_optimizer_heartbeat_frontend_status', 0 ) ),
				'interval' => intval( get_option( 'siteground_optimizer_heartbeat_frontend_interval', 0 ) ),
			),
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_disable' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_disable' ), 99 );
		add_filter( 'heartbeat_settings', array( $this, 'maybe_modify' ), 99 );

	}

	/**
	 * Check if the heartbeat is disabled for a specific location.
	 *
	 * @since  5.6.0
	 */
	public function maybe_disable() {
		foreach ( $this->options as $location => $settings ) {
			// Bail if the location doesn't match the specific location.
			if (
				$this->check_location( $location ) &&
				0 === $settings['status']
			) {
				// Deregiter the script.
				wp_deregister_script( 'heartbeat' );
				return;
			}

		}
	}

	/**
	 * Check if the heartbeat should be modified for specific location
	 *
	 * @since  5.6.0
	 *
	 * @param  array $settings Heartbeat settings array.
	 *
	 * @return array           Modified heartbeat settings array.
	 */
	public function maybe_modify( $settings ) {
		foreach ( $this->options as $location => $location_settings ) {
			// Bail if the location doesn't match the specific location.
			if (
				$this->check_location( $location ) &&
				1 === $location_settings['status']
			) {
				// Change the interval.
				$settings['interval'] = $location_settings['interval'];

				// Return the modified settgins.
				return $settings;
			}

		}

		return $settings;
	}

	/**
	 * Check the current location and if the heartbeat should be modified/disabled.
	 *
	 * @since  5.6.0
	 *
	 * @param  string $location The location id.
	 *
	 * @return bool             True if the heartbead should be modified/disabled for the specific location, false otherwise.
	 */
	public function check_location( $location ) {

		switch ( $location ) {
			case 'dashboard':
				return ( is_admin() && false === @strpos( $_SERVER['REQUEST_URI'], '/wp-admin/post.php' ) );
				break;

			case 'frontend':
				return ! is_admin();
				break;

			case 'post':
				return @strpos( $_SERVER['REQUEST_URI'], '/wp-admin/post.php' );
				break;
			default:
				return false;
				break;
		}
	}
}
