<?php
	/**
	 * Plugin Name: SMS Manager for Wordpress by MagePeople.
	 * Plugin URI: http://mage-people.com
	 * Description: A compact SMS solution for WordPress by MagePeople.
	 * Version: 1.0.0
	 * Author: MagePeople Team
	 * Author URI: http://www.mage-people.com/
	 * Text Domain: mp-sms
	 * Domain Path: /languages/
	 * WC requires at least: 3.0.9
	 * WC tested up to: 5.0
	 */
	if ( ! defined( 'ABSPATH' ) ) 
	{
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MP_SMS_Plugin' ) ) 
	{
		class MP_SMS_Plugin 
		{
			public function __construct() 
			{
				$this->load_plugin();
			}

			private function load_plugin(): void 
			{
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

				if ( ! defined( 'MP_SMS_PLUGIN_DIR' ) ) 
				{
					define( 'MP_SMS_PLUGIN_DIR', dirname( __FILE__ ) );
				}

				if ( ! defined( 'MP_SMS_PLUGIN_URL' ) ) 
				{
					define( 'MP_SMS_PLUGIN_URL', plugins_url() . '/' . plugin_basename( dirname( __FILE__ ) ) );
				}

				if ( self::check_woocommerce() == 1 ) 
				{
					require_once MP_SMS_PLUGIN_DIR . '/inc/MP_SMS_Dependencies.php';
				} 
				else
				{

				}
			}

			public static function check_woocommerce(): int 
			{
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$plugin_dir = ABSPATH . 'wp-content/plugins/woocommerce';
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) 
				{
					return 1;
				} 
				elseif ( is_dir( $plugin_dir ) ) 
				{
					return 2;
				} 
				else 
				{
					return 0;
				}
			}
		}

		new MP_SMS_Plugin();
	}