<?php
if (!defined('ABSPATH'))
{
    die;
} // Cannot access pages directly.

require __DIR__ . '/../thirdparty/sms/twilio/vendor/autoload.php';

use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;

/**
 * Class for working with the Twilio SMS Service
 *
 * @since 1.0
 *  
 * */
if (!class_exists('MP_SMS_TWILIO'))
{
    
    class MP_SMS_TWILIO 
    {
        private $error;

        public function __construct()
        {
            $this->error = new WP_Error();
            if(class_exists(TwilioClient::class))
            {            
                add_action('mp_sms_tab', array($this, 'tab_item'));
                add_action('mp_sms_tab_content', array($this, 'twilio'));
                add_action('admin_init', [ $this, 'save_mp_sms_twilio_settings' ]);
                add_action('admin_notices', array( $this, 'mp_admin_notice' ) );
                add_action('mp_sms_twilio_settings', array($this,'mp_sms_twilio_settings'));
                $this->error_detect();
                if(!$this->error->has_errors())
                {
                    add_action('mp_send_sms', [ $this, 'send_sms' ]);
                }
            }          
        }

        public function tab_item()
        {
            
            ?>
                <!-- <li class="tab-item" data-tabs-target="#mp_sms_twilio_settings"><?php //esc_html_e('Twilio Settings','mp-sms'); ?></li> -->
            <?php
        }

        public function mp_sms_twilio_settings()
        {
            $mp_sms_twilio_settings = MP_SMS_Function::get_option('mp_sms_twilio_settings','');

            ?>
                <div class="accordion">
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <label for="mp_sms_twilio_settings[use_feature]"><strong><?php esc_html_e('Activate Twilio ?','mp-sms');?></strong></label>
                            <?php MP_SMS_Layout::switch_button('mp_sms_twilio_settings[use_feature]' ,'accordion-toggle' , 'mp_sms_twilio_settings[use_feature]',  MP_SMS_Function::array_key_checked($mp_sms_twilio_settings,'use_feature'),''  ); ?>                                        
                        </div>
                        <div class="accordion-content">

                            <h4><?php esc_html_e('Twilio Account Settings','mp-sms'); ?></h4>
                
                            <table class="wc_gateways widefat striped">                                
                                <tr>
                                    <td>
                                        <label><?php esc_html_e('Account ID','mp-sms'); ?></label>
                                    </td>
                                    <td>
                                        <input class="input-text" type="text" name="mp_sms_twilio_settings[account_id]" value="<?php echo esc_attr( $mp_sms_twilio_settings['account_id']??'' ); ?>" placeholder="<?php esc_html_e( 'Account ID', 'mp-sms' ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label><?php esc_html_e('Auth Token','mp-sms'); ?></label>
                                    </td>
                                    <td>
                                        <input class="input-text" type="text" name="mp_sms_twilio_settings[auth_token]" value="<?php echo esc_attr( $mp_sms_twilio_settings['auth_token']??'' ); ?>" placeholder="<?php esc_html_e( 'Auth Token', 'mp-sms' ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label><?php esc_html_e('Twilio Number','mp-sms'); ?></label>
                                    </td>
                                    <td>
                                        <input class="input-text" type="text" name="mp_sms_twilio_settings[twilio_number]" value="<?php echo esc_attr( $mp_sms_twilio_settings['twilio_number']??'' ); ?>" placeholder="<?php esc_html_e( 'Twilio Number', 'mp-sms' ); ?>"/>
                                    </td>
                                </tr>
                            </table>

                        </div>
                    </div>
                </div>
            <?php
        }

        public function twilio() 
        {
            ?>
                <div class="tab-content" id="mp_sms_twilio_settings">

                    <form method="post" action="options.php">
                        <?php do_action('mp_sms_twilio_settings'); ?>
                        <div class="action-button">
                            <?php echo wp_nonce_field('mp_sms_twilio_settings', 'mp_sms_twilio_settings_nonce'); ?>
                            <input type="hidden" name="action" value="mp_sms_twilio_settings_save">
                            <input type="submit" name="submit" class="button" value="Save Settings">
                        </div>
                    </form>
					
                </div>
            <?php
        }

        public function save_mp_sms_twilio_settings()
        {
            if (isset($_POST['action']) && $_POST['action'] === 'mp_sms_twilio_settings_save' && wp_verify_nonce($_POST['mp_sms_twilio_settings_nonce'], 'mp_sms_twilio_settings')) 
            {                
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

                wp_safe_redirect(admin_url('admin.php?page=mp-sms-settings#mp_sms_twilio_settings'));
                exit();
                
            }

        }

        public function send_sms($args)
        {
            $twilio_settings = MP_SMS_Function::get_option('mp_sms_twilio_settings','');

            $numbers = is_array($args['numbers'])?$args['numbers']:array($args['numbers']);

            if(count($numbers))
            {
                $this->error->add('invalid_data', esc_html__( 'Oops!  No mobile number is provided for SMS Sending ', 'mp-sms' ));
            }
        
            if(count($numbers) && !empty($args['sms']))
            {
                try 
                {
                    $client = new TwilioClient($twilio_settings['account_id'], $twilio_settings['auth_token']);
                    foreach ($numbers as $number)
                    {
                        $message = $client->messages->create(
                            $number,
                            array(
                                'from' => $twilio_settings['twilio_number'],
                                'body' => $args['sms']
                            )
                        );
                    }
                } 
                catch (TwilioException $e) 
                {
                    
                }
                
            }

        }

        public function error_detect()
        {
            $general_settings = MP_SMS_Function::get_option('mp_sms_general_settings','');

            if(is_array($general_settings) && (array_key_exists('use_feature',$general_settings) && $general_settings['use_feature'] == "on"))
            {
                $twilio_settings = MP_SMS_Function::get_option('mp_sms_twilio_settings','');
                
                if ( array_key_exists('use_feature',$twilio_settings) &&  $twilio_settings['use_feature'] == "on" )
                {
                    $admin_url = get_admin_url();

                    $sms_setting_url = MP_SMS_Function::get_link($admin_url.'admin.php?page=mp-sms-settings#mp_sms_general_settings');

                    if( !array_key_exists('account_id',$twilio_settings) || (array_key_exists('account_id',$twilio_settings) && empty( $twilio_settings['account_id'] ) ))
                    {
                        $this->error->add('invalid_data', esc_html__( 'Oops!  invalid Twilio Account Id is provided. To fix it please ', 'mp-sms' ). $sms_setting_url);
                    }
                    
                    if( !array_key_exists('auth_token',$twilio_settings) || (array_key_exists('auth_token',$twilio_settings) && empty( $twilio_settings['auth_token']) ))
                    {
                        $this->error->add('invalid_data', esc_html__( 'Oops!  invalid Twilio Auth Token is provided. To fix it please ', 'mp-sms' ). $sms_setting_url);
                    }

                    if( !array_key_exists('twilio_number',$twilio_settings) || (array_key_exists('twilio_number',$twilio_settings) && empty( $twilio_settings['twilio_number']) ))
                    {
                        $this->error->add('invalid_data', esc_html__( 'Oops!  invalid Twilio Number is provided. To fix it please ', 'mp-sms' ). $sms_setting_url);
                    }
                    
                }

            }

        }

        public function mp_admin_notice()
        {				
            MP_SMS_Function::mp_error_notice($this->error);
        }
        
    }

    new MP_SMS_TWILIO();
}