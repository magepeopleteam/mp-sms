<?php
if (!defined('ABSPATH'))
{
    die;
} // Cannot access pages directly.

/**
 * Class for working with SMS General Settings
 *
 * @since 1.0
 *  
 * */
if (!class_exists('MP_SMS_General_Settings'))
{
    class MP_SMS_General_Settings 
    {
        private $sms_feature;
        private $sms_provider;
        private $error;

        public function __construct()
        {
            $this->error = new WP_Error();
            add_action('mp_sms_tab', array($this, 'tab_item'));
            add_action('mp_sms_tab_content', array($this, 'tab_content'));
            add_action('admin_init', [ $this, 'save_mp_sms_general_settings' ]);
            add_action('admin_notices',array($this, 'mp_admin_notice' ) );
            add_action('mp_sms_general_settings', array($this,'mp_sms_general_settings'));
            $this->error_detect();

        }

        public function mp_sms_general_settings()
        {
            $mp_sms_general_settings = MP_SMS_Function::get_option('mp_sms_general_settings','');

            ?>
                <div class="accordion">
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <label for="mp_sms_general_settings[use_feature]"><strong><?php esc_html_e('Activate SMS Feature ?','mp-sms');?></strong></label>
                            <?php MP_SMS_Layout::switch_button('mp_sms_general_settings[use_feature]' ,'accordion-toggle' , 'mp_sms_general_settings[use_feature]',  MP_SMS_Function::array_key_checked($mp_sms_general_settings,'use_feature','checked'),''  ); ?>                                        
                        </div>
                        <div class="accordion-content">
                            <h4><?php esc_html_e('SMS Provider Settings','mp-sms');?></h4>
                            <?php do_action('mp_sms_twilio_settings'); ?>                            
                            <h4><?php esc_html_e('SMS Woocommerce Settings','mp-sms');?></h4>
                            <?php do_action('mp_sms_wc_settings'); ?>                            
                        </div>
                    </div>
                </div>
            <?php
        }



        public function tab_item()
        {
            ?>
                <li class="tab-item active" data-tabs-target="#mp_sms_general_settings"><?php esc_html_e('SMS General Settings','mp-sms');?></li>
            <?php
        }

        public function tab_content()
        {
            ?>
                <div class="tab-content active" id="mp_sms_general_settings">
                    <div class="form-container">
                        <form method="post" action="options.php">
                            <?php do_action('mp_sms_general_settings'); ?>                          
                            <div class="action-button">
                                <?php echo wp_nonce_field('mp_sms_general_settings', 'mp_sms_general_settings_nonce'); ?>
                                <input type="hidden" name="action" value="mp_sms_general_settings_save">
                                <input type="submit" name="submit" class="button" value="Save Settings">
                            </div>
                        </form>
                    </div>
                </div>
            <?php
        }

        public function save_mp_sms_general_settings()
        {
            if (isset($_POST['action']) && $_POST['action'] === 'mp_sms_general_settings_save' && wp_verify_nonce($_POST['mp_sms_general_settings_nonce'], 'mp_sms_general_settings')) 
            {
                $sanitized_options = array();

                if (isset($_POST['mp_sms_general_settings']['use_feature'])) 
                {
                    $sanitized_options['use_feature'] = sanitize_text_field($_POST['mp_sms_general_settings']['use_feature']);
                }
                else
                {
                    $sanitized_options['use_feature'] = '';
                }
                                                
                update_option('mp_sms_general_settings', $sanitized_options);

                $sanitized_options = array();

                if (isset($_POST['mp_sms_twilio_settings']['use_feature'])) 
                {
                    $sanitized_options['use_feature'] = sanitize_text_field($_POST['mp_sms_twilio_settings']['use_feature']);
                }
                else
                {
                    $sanitized_options['use_feature'] = '';
                }

                if (isset($_POST['mp_sms_twilio_settings']['account_id'])) 
                {
                    $sanitized_options['account_id'] = sanitize_text_field($_POST['mp_sms_twilio_settings']['account_id']);
                }
                if (isset($_POST['mp_sms_twilio_settings']['auth_token'])) 
                {
                    $sanitized_options['auth_token'] = sanitize_text_field($_POST['mp_sms_twilio_settings']['auth_token']);
                }
                if (isset($_POST['mp_sms_twilio_settings']['twilio_number'])) 
                {
                    $sanitized_options['twilio_number'] = sanitize_text_field($_POST['mp_sms_twilio_settings']['twilio_number']);
                }

                update_option('mp_sms_twilio_settings', $sanitized_options);

                $sanitized_options = array();
                
                if (isset($_POST['mp_sms_woocommerce_settings']['use_feature'])) 
                {
                    $sanitized_options['use_feature'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['use_feature']);
                }
                else
                {
                    $sanitized_options['use_feature'] = '';
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['use_feature'])) 
                {
                    $sanitized_options['woocommerce_settings']['use_feature'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['use_feature']);
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['feature_on_hold'])) 
                {
                    $sanitized_options['woocommerce_settings']['feature_on_hold'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['feature_on_hold']);
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['template_for_on_hold'])) 
                {
                    $sanitized_options['woocommerce_settings']['template_for_on_hold'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['template_for_on_hold']);
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['feature_on_pending'])) 
                {
                    $sanitized_options['woocommerce_settings']['feature_on_pending'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['feature_on_pending']);
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['template_for_pending'])) 
                {
                    $sanitized_options['woocommerce_settings']['template_for_pending'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['template_for_pending']);
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['feature_on_processing']))
                {
                    $sanitized_options['woocommerce_settings']['feature_on_processing'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['feature_on_processing']);
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['template_for_processing'])) 
                {
                    $sanitized_options['woocommerce_settings']['template_for_processing'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['template_for_processing']);
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['feature_on_completed'])) 
                {
                    $sanitized_options['woocommerce_settings']['feature_on_completed'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['feature_on_completed']);
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['template_for_completed'])) 
                {
                    $sanitized_options['woocommerce_settings']['template_for_completed'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['woocommerce_settings']['template_for_completed']);
                }
                
                update_option('mp_sms_woocommerce_settings', $sanitized_options);

                wp_safe_redirect(admin_url('admin.php?page=mp-sms-settings#mp_sms_general_settings'));
                exit();

            }

        }

        public function error_detect()
        {
            $general_settings = MP_SMS_Function::get_option('mp_sms_general_settings','');
        }

        public function mp_admin_notice()
        {				
            MP_SMS_Function::mp_error_notice($this->error);
        }
        
    }

    new MP_SMS_General_Settings();
}