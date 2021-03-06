<?php
namespace SiteGround_Optimizer\Rest;

use SiteGround_Optimizer\Options\Options;
use SiteGround_Optimizer\Multisite\Multisite;
use SiteGround_Optimizer\Front_End_Optimization\Front_End_Optimization;
use SiteGround_Optimizer\Helper\Helper;

/**
 * Rest Helper class that manages all of the front end optimisation.
 */
class Rest_Helper_Options extends Rest_Helper {
	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->options   = new Options();
		$this->multisite = new Multisite();
	}
	/**
	 * Checks if the option key exists.
	 *
	 * @since  5.0.0
	 *
	 * @param  object $request Request data.
	 */
	public function enable_option_from_rest( $request ) {
		// Get the option key.
		$key        = $this->validate_and_get_option_value( $request, 'option_key' );
		$is_network = $this->validate_and_get_option_value( $request, 'is_multisite', false );
		$result     = $this->options->enable_option( $key, $is_network );

		// Enable the option.
		wp_send_json(
			array(
				'success' => $result,
				'data' => array(
					'message' => $this->options->get_response_message( $result, $key, true ),
				),
			)
		);
	}

	/**
	 * Checks if the option key exists.
	 *
	 * @since  5.0.0
	 *
	 * @param  object $request Request data.
	 *
	 * @return string The option key.
	 */
	public function disable_option_from_rest( $request ) {
		// Get the option key.
		$key        = $this->validate_and_get_option_value( $request, 'option_key' );
		$is_network = $this->validate_and_get_option_value( $request, 'is_multisite', false );
		$result     = $this->options->disable_option( $key, $is_network );

		// Disable the option.
		return wp_send_json(
			array(
				'success' => $result,
				'data' => array(
					'message' => $this->options->get_response_message( $result, $key, false ),
				),
			)
		);
	}

	/**
	 * Checks if the option key exists.
	 *
	 * @since  5.5.0
	 *
	 * @param  object $request Request data.
	 *
	 * @return string The option key.
	 */
	public function change_option_from_rest( $request ) {
		$allowed_options = array(
			'siteground_optimizer_quality_webp',
			'siteground_optimizer_quality_type',
			'siteground_optimizer_quality_images',
			'siteground_optimizer_heartbeat_dashboard_interval',
			'siteground_optimizer_heartbeat_post_interval',
			'siteground_optimizer_heartbeat_frontend_interval',
		);

		// Get the option key.
		$key = $this->validate_and_get_option_value( $request, 'option_key' );

		// Bail if the option is now allowed.
		if ( ! in_array( $key, $allowed_options ) ) {
			wp_send_json_error();
		}

		$value      = $this->validate_and_get_option_value( $request, 'value' );
		$is_network = $this->validate_and_get_option_value( $request, 'is_multisite', false );
		$result     = $this->options->change_option( $key, $value, $is_network );

		// Chnage the option.
		return wp_send_json(
			array(
				'success' => $result,
			)
		);
	}

	/**
	 * Provide all plugin options.
	 *
	 * @since  5.0.0
	 */
	public function fetch_options() {
		// Fetch the options.
		$options = $this->options->fetch_options();

		if ( is_multisite() ) {
			$options['sites_data'] = $this->multisite->get_sites_info();
		}

		$options['has_images']                  = $this->options->check_for_images();
		$options['has_images_for_optimization'] = $this->options->check_for_unoptimized_images();
		$options['assets']                      = Front_End_Optimization::get_instance()->get_assets();
		$options['quality_type']                = get_option( 'siteground_optimizer_quality_type', '' );

		// Check for non converted images when we are on avalon server.
		if ( Helper::is_avalon() ) {
			$options['has_images_for_conversion']   = $this->options->check_for_non_converted_images();
		}

		// Send the options to react app.
		wp_send_json_success( $options );
	}
}
