<?php
if (!defined('ABSPATH'))
{
    die;
} // Cannot access pages directly.

require __DIR__ . '/../thirdparty/sms/twilio/vendor/autoload.php';

use Twilio\Rest\Client as TwilioClient;

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
        private $available;
        private $enabled;
        private $sms_feature;
        private $sms_provider;
        private $client;
        private $account_id;
        private $auth_token;
        private $twilio_number;
        private $error;

        public function __construct()
        {            
            add_action('mp_sms_tab', array($this, 'tab_item'));
            add_action('mp_sms_tab_content', array($this, 'twilio'));
            add_action('admin_init', [ $this, 'save_mp_sms_twilio_settings' ]);
            add_action('wp_loaded', array( $this, 'apply' ) );
            add_action('mp_send_sms', [ $this, 'send_sms' ]);
            add_action('admin_notices', array( $this, 'mp_admin_notice' ) );          
        }

        public function tab_item()
        {
            if($this->available == 'yes')
            {
            ?>
                <li class="tab-item" data-tabs-target="#mp_sms_twilio_settings">Twilio Settings</li>
            <?php
            }
        }

        public function twilio() 
        {

            ?>
                <div class="tab-content" id="mp_sms_twilio_settings">
                    <div class="mpStyle">
                        <form method="post" action="options.php">
                            <?php
                                wp_nonce_field('mp_sms_twilio_settings', 'mp_sms_twilio_settings_nonce');
                                $account_id = MP_SMS_Helper::senitize(get_option('mp_sms_twilio_settings')['account_id']);
                                $auth_token = MP_SMS_Helper::senitize(get_option('mp_sms_twilio_settings')['auth_token']);
                                $twilio_number = MP_SMS_Helper::senitize(get_option('mp_sms_twilio_settings')['twilio_number']);
                            ?>
                            <table  class="form-table">
                                <tbody>
                                    <tr>
                                        <td>
                                            <label>Account ID</label>
                                        </td>
                                        <td>
                                            <input class="formControl" type="text" name="mp_sms_twilio_settings[account_id]" value="<?php echo esc_attr( $account_id??'' ); ?>" placeholder="<?php esc_html_e( 'Account ID', 'mp-sms' ); ?>"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label>Auth Token</label>
                                        </td>
                                        <td>
                                            <input class="formControl" type="text" name="mp_sms_twilio_settings[auth_token]" value="<?php echo esc_attr( $auth_token??'' ); ?>" placeholder="<?php esc_html_e( 'Auth Token', 'mp-sms' ); ?>"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label>Twilio Number</label>
                                        </td>
                                        <td>
                                            <input class="formControl" type="text" name="mp_sms_twilio_settings[twilio_number]" value="<?php echo esc_attr( $twilio_number??'' ); ?>" placeholder="<?php esc_html_e( 'Twilio Number', 'mp-sms' ); ?>"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <div style="float:right;padding-left: 10px">
                                                <input type="hidden" name="action" value="mp_sms_twilio_settings_save">
                                                <input type="submit" name="submit" class="button-primary" value="Save Settings">
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>
					</div>
                </div>

            <?php
        }

        public function save_mp_sms_twilio_settings()
        {
            if (isset($_POST['action']) && $_POST['action'] === 'mp_sms_twilio_settings_save' && wp_verify_nonce($_POST['mp_sms_twilio_settings_nonce'], 'mp_sms_twilio_settings')) 
            {                
                $sanitized_options = array();
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

                wp_safe_redirect(admin_url('admin.php?page=mp-sms#mp_sms_twilio_settings'));
                exit();
                
            }

        }

        public function apply()
        {
            $this->error = new WP_Error();
            $this->available = ( $this->available() == '1' ) ? 'yes' : 'no';
            $this->sms_feature = MP_SMS_Helper::senitize(get_option('mp_sms_general_settings')['use_sms_features']);
            $this->sms_provider = MP_SMS_Helper::senitize(get_option('mp_sms_general_settings')['sms_provider']);
            $this->enabled = ( $this->check_enabled() == '1' ) ? 'yes' : 'no';

            if($this->available == 'no') 
            {
                $this->error->add('invalid_data', esc_html__( 'Oops! Twilio is not installed properly !!! ', 'mp-sms' ));
            }

            if($this->enabled == 'yes')
            {                
                $this->account_id = MP_SMS_Helper::senitize(get_option('mp_sms_twilio_settings')['account_id']);

                $this->auth_token = MP_SMS_Helper::senitize(get_option('mp_sms_twilio_settings')['auth_token']);

                $this->twilio_number = MP_SMS_Helper::senitize(get_option('mp_sms_twilio_settings')['twilio_number']);

                $admin_url = get_admin_url();

                $sms_setting_url = MP_SMS_Helper::get_link($admin_url.'/admin.php?page=mp-sms#mp_sms_twilio_settings');

                if ( empty( $this->account_id ) )
                {
                    $this->error->add('invalid_data', esc_html__( 'Oops!  invalid Twilio Account Id is provided. To fix it please ', 'mp-sms' ). $sms_setting_url);
                }
                
                if( empty($this->auth_token) )
                {
                    $this->error->add('invalid_data', esc_html__( 'Oops!  invalid Twilio Auth Token is provided. To fix it please ', 'mp-sms' ). $sms_setting_url);
                }

                if( empty($this->twilio_number) )
                {
                    $this->error->add('invalid_data', esc_html__( 'Oops!  invalid Twilio Number is provided. To fix it please ', 'mp-sms' ). $sms_setting_url);
                }

                $this->client = new TwilioClient($this->account_id, $this->auth_token);
                
            }
            
        }

        public static function available()
        {
            if(class_exists(TwilioClient::class))
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }

        public function check_enabled()
        {
            if($this->available == 'yes' && $this->sms_feature == "on" && $this->sms_provider == 'twilio')
            {
                return 1;
            }
            else
            {
                return 0;
            }

        }

        public function send_sms($args)
        {
            if( $this->enabled == 'yes' && !$this->error->has_errors())
            {
                $numbers = is_array($args['numbers'])?$args['numbers']:array($args['numbers']);
                
                if(count($numbers) && !empty($args['sms']))
                {
                    foreach ($numbers as $number)
                    {
                        $this->client->messages->create(
                            // Where to send a text message (your cell phone?)
                            $number,
                            array(
                                'from' => $this->twilio_number,
                                'body' => $args['sms']
                            )
                        );

                    }

                }
                
            }

        }

        public function mp_admin_notice()
        {				
            MP_SMS_Helper::mp_error_notice($this->error);
        }
        
    }

    new MP_SMS_TWILIO();
}