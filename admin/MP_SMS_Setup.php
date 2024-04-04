<?php

	if ( ! defined( 'ABSPATH' ) )
	{
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MP_SMS_Setup' ) ) 
	{
		class MP_SMS_Setup 
		{
			private $error;

			public function __construct()
			{
				$this->error = new WP_Error();
				
				add_action('admin_menu', array($this,'sms_menu'));
				add_action('admin_notices',array($this, 'mp_admin_notice' ) );
				add_action('wp_ajax_mp_sms_install_and_activate_woocommerce', array($this, 'install_and_activate_woocommerce'));				
			}

			public function sms_menu() 
			{
				add_submenu_page(
					'mp-sms',
					__('WPSmsly Setup', 'mp-sms'),
					__('WPSmsly Setup', 'mp-sms'),
					'manage_options',
					'mp-sms-setup',
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
					</div>
				</div>

				<div class="mpStyles">
					<div class="mp-sms">
						<div class="mp-sms-container">
							<div class="install">
								
								<?php 
								if(MP_SMS_Function::check_plugin('woocommerce','woocommerce.php') == 0)
								{
								?>
								<div><h3><?php echo esc_html_e('You must install and activate Woocommerce to work WPSmsly properly.','mp-sms'); ?></h3></div>
								<a href="" class="mp-sms-install-activate" data-install-action="install_activate"><?php echo esc_html_e('Install & Activate Woocommerce','mp-sms'); ?></a>
								<?php
								} 
								else if(MP_SMS_Function::check_plugin('woocommerce','woocommerce.php') == 2)
								{
								?>
								<div><h3><?php echo esc_html_e('You must activate Woocommerce to work WPSmsly properly.','mp-sms'); ?></h3></div>
								<a href="" class="mp-sms-install-activate" data-install-action="activate"><?php echo esc_html_e('Activate Woocommerce','mp-sms'); ?></a>
								<?php
								} 
								?>
								
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

			public function install_and_activate_woocommerce()
			{
				$respnse = '';
				$install_action = isset($_REQUEST['install_action']) ? MP_SMS_Function::sanitize($_REQUEST['install_action']) : '';
				if ($install_action == 'install_activate') 
				{
					$respnse = MP_SMS_Function::install_woocommerce();										
				}
				else if ($install_action == 'activate')
				{
					activate_plugin('woocommerce/woocommerce.php');
					$respnse = array('status' => 'success', 'message' =>'Woocommerce activated successfully !!!');
				}

				echo json_encode($respnse);

				die();
			}

		}

		new MP_SMS_Setup();
	}