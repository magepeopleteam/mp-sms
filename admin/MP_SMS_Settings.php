<?php

	if ( ! defined( 'ABSPATH' ) )
	{
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MP_SMS_Settings' ) ) 
	{
		class MP_SMS_Settings 
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
				add_submenu_page(
					'mp-sms',
					__('WPSmsly Settings', 'mp-sms'),
					__('WPSmsly Settings', 'mp-sms'),
					'manage_options',
					'mp-sms-settings',
					array($this, 'main_sms'),
					9,
				);
			}

			public function main_sms()
			{
				?>
					<div class="mpStyles">

						<div class="mp-sms">

							<div class="loader-container">
								<div class="loader-spinner">
								</div>
							</div>

							<div class="tab-container">
								<ul class="tab-menu">
									<h3><?php esc_html_e('WPSmsly Manage','mp-sms');?></h3>
									<?php do_action('mp_sms_tab'); ?>
								</ul>

								<div class="tab-content-container">
									<?php do_action('mp_sms_tab_content'); ?>
								</div>
							</div>

						</div>

					</div>

				<?php
			}

			public function mp_admin_notice()
			{				
				MP_SMS_Function::mp_error_notice($this->error);
			}

		}

		new MP_SMS_Settings();
	}