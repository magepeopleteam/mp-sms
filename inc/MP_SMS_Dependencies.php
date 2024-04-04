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
				add_action('admin_head', array($this, 'js_constants'), 5);
				add_action('wp_head', array($this, 'js_constants'), 5);
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'), 90);
				$this->load_files();
			}

			public function admin_enqueue()
			{
				wp_register_style('mp_sms_common', MP_SMS_PLUGIN_URL . '/assets/common/css/mp-styles.css' );
				wp_enqueue_style('mp_sms_common');
				wp_enqueue_script('mp_sms_common', MP_SMS_PLUGIN_URL . '/assets/common/js/mp-styles.js', array('jquery'), time(), true);

				wp_register_style('mp_sms', MP_SMS_PLUGIN_URL . '/assets/common/css/mp-sms.css' );
				wp_enqueue_style('mp_sms');
				wp_enqueue_script('mp_sms', MP_SMS_PLUGIN_URL . '/assets/common/js/mp-sms.js', array('jquery'), time(), true);
	
			}

			private function load_files(): void 
			{
				require_once MP_SMS_PLUGIN_DIR . '/inc/MP_SMS_Function.php';
				require_once MP_SMS_PLUGIN_DIR . '/inc/MP_SMS_Layout.php';
				require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS.php';

				if(MP_SMS_Function::check_plugin('woocommerce','woocommerce.php') == 1)
				{
					require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Settings.php';				
					require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_General_Settings.php';
					require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Twilio.php';
					require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Woocommerce.php';
					require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Tour.php';
					require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Event.php';
					require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Taxi.php';
					require_once MP_SMS_PLUGIN_DIR . '/admin/MP_Test.php';
					//require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Setup.php';
				}
				else
				{
					require_once MP_SMS_PLUGIN_DIR . '/admin/MP_SMS_Setup.php';
				}
				
			}

			public function js_constants() 
			{
				?>
				<script type="text/javascript">
					let mp_sms_site_url = "<?php echo admin_url('admin.php?page=mp-sms-settings'); ?>";
					let mp_sms_ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>";
					
				</script>
				<?php
			}
		}

		new MP_SMS_Dependencies();
	}


?>