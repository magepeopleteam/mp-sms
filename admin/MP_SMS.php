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
					__('MP SMS', 'mp-sms'),
					__('MP SMS', 'mp-sms'),
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
					<div class="mpStyles">

						<div class="tab-container">
							<ul class="tab-menu">
								<?php do_action('mp_sms_tab'); ?>
							</ul>

							<div class="tab-content-container">
								<?php do_action('mp_sms_tab_content'); ?>
							</div>
						</div>

					</div>

				<?php
			}

			public function mp_admin_notice()
			{				
				MP_SMS_Helper::mp_error_notice($this->error);
			}

		}

		new MP_SMS();
	}