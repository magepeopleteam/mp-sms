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
            add_action('mp_sms_tab', array($this, 'tab_item'));
            add_action('mp_sms_tab_content', array($this, 'tab_content'));
            add_action('admin_init', [ $this, 'save_mp_sms_general_settings' ]);            
            add_action('wp_loaded', array( $this,'apply' ) );
            add_action('admin_notices',array($this, 'mp_admin_notice' ) );
        }

        public function apply()
        {
            $this->error = new WP_Error();
            $this->sms_feature = get_option('mp_sms_general_settings')['use_sms_features'];
            $this->sms_provider = get_option('mp_sms_general_settings')['sms_provider'];

            $admin_url = get_admin_url();

            $sms_setting_url = MP_SMS_Helper::get_link($admin_url.'/admin.php?page=mp-sms#mp_sms_general_settings');
            
            if($this->sms_feature == "on" && empty($this->sms_provider))
            {
                $this->error->add('invalid_data', esc_html__( 'Oops!  invalid SMS Provider is provided. To fix it please ', 'mp-sms' ). $sms_setting_url);
            }			
        }

        public function tab_item()
        {
            ?>
                <li class="tab-item active" data-tabs-target="#mp_sms_general_settings">SMS General Settings</li>
            <?php
        }

        public function tab_content()
        {
            ?>
                <div class="tab-content active" id="mp_sms_general_settings">
                    <form method="post" action="options.php">
                        <?php
                            wp_nonce_field('mp_sms_general_settings', 'mp_sms_general_settings_nonce');
                            $use_sms_features = get_option('mp_sms_general_settings')['use_sms_features'];
                            $feature_checked = $use_sms_features == 'on' ? 'checked' : '';
                            $sms_provider = get_option('mp_sms_general_settings')['sms_provider'];
                        ?>
                        <table  class="form-table">
                            <tbody>
                                <tr>
                                    <td>Use SMS Feature ?</td>
                                    <td>
                                        <?php MP_SMS_Layout::switch_button( 'mp_sms_general_settings[use_sms_features]', $feature_checked ); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Select SMS Provider</td>
                                    <td>
                                        <select name="mp_sms_general_settings[sms_provider]">
                                            <option value="" <?php echo $sms_provider == '' ? 'selected' : ''; ?> >Select SMS Provider</option>
                                            <option value="twilio"  <?php echo $sms_provider == 'twilio' ? 'selected' : ''; ?>>Twilio</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div style="float:right;padding-left: 10px">
                                            <input type="hidden" name="action" value="mp_sms_general_settings_save">
                                            <input type="submit" name="submit" class="button-primary" value="Save Settings">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>

                <script>

				</script>
            <?php
        }

        public function save_mp_sms_general_settings()
        {
            if (isset($_POST['action']) && $_POST['action'] === 'mp_sms_general_settings_save' && wp_verify_nonce($_POST['mp_sms_general_settings_nonce'], 'mp_sms_general_settings')) 
            {
                $sanitized_options = array();
                if (isset($_POST['mp_sms_general_settings']['use_sms_features'])) 
                {
                    $sanitized_options['use_sms_features'] = sanitize_text_field($_POST['mp_sms_general_settings']['use_sms_features']);
                }
                if (isset($_POST['mp_sms_general_settings']['sms_provider'])) 
                {
                    $sanitized_options['sms_provider'] = sanitize_text_field($_POST['mp_sms_general_settings']['sms_provider']);
                }
                                
                update_option('mp_sms_general_settings', $sanitized_options);

                wp_safe_redirect(admin_url('admin.php?page=mp-sms#mp_sms_general_settings'));
                exit();

            }

        }

        public function mp_admin_notice()
        {				
            MP_SMS_Helper::mp_error_notice($this->error);
        }
        
    }

    new MP_SMS_General_Settings();
}