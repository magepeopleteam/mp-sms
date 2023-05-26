<?php

    if ( ! defined( 'ABSPATH' ) ) 
	{
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MP_SMS_Dependencies' ) ) 
	{
		class MP_SMS_Dependencies 
		{
			public function __construct() 
			{
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'), 90);
				$this->load_files();
			}

			public function admin_enqueue()
			{
				wp_register_style('mp_sms', MP_SMS_PLUGIN_URL . '/assets/common/css/mp-sms.css' );
				wp_enqueue_style('mp_sms');
				wp_enqueue_script('mp_sms', MP_SMS_PLUGIN_URL . '/assets/common/js/mp-sms.js', array('jquery'), time(), true);

				wp_register_style('mp_sms_common', MP_SMS_PLUGIN_URL . '/assets/common/css/mp-styles.css' );
				wp_enqueue_style('mp_sms_common');
				wp_enqueue_script('mp_sms_common', MP_SMS_PLUGIN_URL . '/assets/common/js/mp-styles.js', array('jquery'), time(), true);
			}

			private function load_files(): void 
			{
				require_once MP_SMS_PLUGIN_DIR . '/inc/MP_SMS_Layout.php';

				require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Helper.php';

				require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_General_Settings.php';
				require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS.php';
				require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Twilio.php';
				require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Woocommerce.php';
				require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Tour.php';
				require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Event.php';
				require_once MP_SMS_PLUGIN_DIR . '/admin/MP_Test.php';
			}
		}

		new MP_SMS_Dependencies();
	}


?>