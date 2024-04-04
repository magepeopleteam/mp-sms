<?php
	/**
	 * Plugin Name: WPSmsly - A SMS Manager for Wordpress by MagePeople.
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
			private $error;

			public function __construct() 
			{
				$this->error = new WP_Error();
				$this->load_plugin();
			}

			private function load_plugin(): void 
			{
				add_action('admin_notices',array($this, 'mp_admin_notice' ) );     
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

				if ( ! defined( 'MP_SMS_PLUGIN_DIR' ) ) 
				{
					define( 'MP_SMS_PLUGIN_DIR', dirname( __FILE__ ) );
				}

				if ( ! defined( 'MP_SMS_PLUGIN_URL' ) ) 
				{
					define( 'MP_SMS_PLUGIN_URL', plugins_url() . '/' . plugin_basename( dirname( __FILE__ ) ) );
				}

				require_once MP_SMS_PLUGIN_DIR . '/inc/MP_SMS_Dependencies.php';

				$woocommerce = MP_SMS_Function::check_plugin('woocommerce','woocommerce.php');
				if ( $woocommerce == 1 ) 
				{
					add_action('activated_plugin', array($this, 'activation_redirect'), 90, 1);
				} 
				else if ( $woocommerce == 2 )
				{
					add_action('activated_plugin', array($this, 'activation_redirect_setup'), 90, 1);
					$this->error->add('invalid_data', esc_html__( 'Oops! WPSmsly is enabled but not effective. It requires WooCommerce activated properly. !!! ', 'mp-sms' ));
				}
				else
				{
					add_action('activated_plugin', array($this, 'activation_redirect_setup'), 90, 1);
					$this->error->add('invalid_data', esc_html__( 'Oops! WPSmsly is enabled but not effective. It requires WooCommerce installed and activated properly !!! ', 'mp-sms' ));	
				}
			}

			public function activation_redirect($plugin)
			{
				if ($plugin == plugin_basename(__FILE__)) 
				{
					exit(wp_redirect(admin_url('admin.php?page=mp-sms')));
				}
			}

			public function activation_redirect_setup($plugin) 
			{
				if ($plugin == plugin_basename(__FILE__)) 
				{
					exit(wp_redirect(admin_url('admin.php?page=mp-sms-setup')));
				}
			}

			public function mp_admin_notice()
			{				
				MP_SMS_Function::mp_error_notice($this->error);
			}

		}

		new MP_SMS_Plugin();
	}