<?php

	if ( ! defined( 'ABSPATH' ) )
	{
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MP_SMS' ) ) 
	{
		class MP_SMS 
		{
			private $error;

			public function __construct()
			{
				$this->error = new WP_Error();
				
				add_action('admin_menu', array($this,'sms_menu'));
				add_action('admin_notices',array($this, 'mp_admin_notice' ) );				
			}

			public function sms_menu() 
			{
				add_menu_page(
					__('WPSmsly', 'mp-sms'),
					__('WPSmsly', 'mp-sms'),
					'manage_options',
					'mp-sms',
					array($this, 'main_sms'),
					'',
					9
				);

			}

			public function main_sms()
			{
				?>
					
				<?php
			}

			public function mp_admin_notice()
			{				
				MP_SMS_Function::mp_error_notice($this->error);
			}

		}

		new MP_SMS();
	}