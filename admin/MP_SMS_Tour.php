<?php
if (!defined('ABSPATH'))
{
    die;
} // Cannot access pages directly.

/**
 * Class for working on Woocommerce SMS Service
 *
 * @since 1.0
 *  
 * */
if (!class_exists('MP_SMS_Tour'))
{
    class MP_SMS_Tour 
    {
        private $available;
        private $enabled;
        private $feature;
        private $sms_feature;
        private $sms_provider;
        private $error;

        public function __construct()
        {
            add_action('mp_sms_tab', array($this, 'tab_item'));
            add_action('mp_sms_tab_content', array($this, 'tab_content'));
            add_action('admin_init', [ $this, 'save_mp_sms_tour_settings' ]);            
            add_action('wp_loaded', array( $this,'apply' ) );
            add_action('admin_notices',array($this, 'mp_admin_notice' ) );
            add_action('init', array($this,'shortcodes') );
        }

        public function tab_item()
        {
            if($this->available == "yes")
            {
            ?>
                <li class="tab-item" data-tabs-target="#mp_sms_tour_settings">SMS Tour Settings</li>
            <?php
            }
        }

        public function shortcodes()
        {
            if($this->enabled == 'yes')
            {
                add_shortcode( 'mp_sms_tour_name' , array($this,'tour_name') );
                add_shortcode( 'mp_sms_tour_date' , array($this,'tour_date') );
            }            
        }

        public function tour_name( $atts )
        {
            $item = new WC_Order_Item_Product($atts['item_id']);
            return $item->get_name(); 
        }

        public function tour_date( $atts )
        {
            $date = wc_get_order_item_meta( $atts['item_id'], '_ttbm_date', true );
            $date = TTBM_Function::datetime_format($date, $type = 'date-time-text'); 
            return $date;
        }

        public function apply()
        {
            $this->error = new WP_Error();
            $this->available = ( MP_SMS_Helper::check_plugin('tour-booking-manager','tour-booking-manager.php') == '1' ) ? 'yes' : 'no';
            $this->feature = MP_SMS_Helper::senitize(get_option('mp_sms_tour_settings')['use_feature']);
            $this->sms_feature = MP_SMS_Helper::senitize(get_option('mp_sms_general_settings')['use_sms_features']);
            $this->sms_provider = MP_SMS_Helper::senitize(get_option('mp_sms_general_settings')['sms_provider']);
            $this->enabled = ( $this->check_enabled() == '1' ) ? 'yes' : 'no';
        }

        public function check_enabled()
        {

            if($this->available == 'yes' && $this->feature == "on" && $this->sms_feature == "on" && $this->sms_provider != '')
            {
                return 1;
            }
            else
            {
                return 0;
            }

        }

        public function tab_content()
        {
            if($this->available == "yes")
            {
            ?>
                <div class="tab-content" id="mp_sms_tour_settings">
                    <form method="post" action="options.php">
                        <?php
                            wp_nonce_field('mp_sms_tour_settings', 'mp_sms_tour_settings_nonce');
                            $use_feature = MP_SMS_Helper::senitize(get_option('mp_sms_tour_settings')['use_feature']);
                            $feature_checked = $use_feature == 'on' ? 'checked' : '';
                        ?>
                        <table  class="form-table">
                            <tbody>
                                <tr>
                                    <td>Use SMS Tour Feature ?</td>
                                    <td>
                                        <?php MP_SMS_Layout::switch_button( 'mp_sms_tour_settings[use_feature]',  esc_attr($feature_checked) ); ?>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td colspan="2">
                                        <div style="float:right;padding-left: 10px">
                                            <input type="hidden" name="action" value="mp_sms_tour_settings_save">
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
        }

        public function save_mp_sms_tour_settings()
        {
            if (isset($_POST['action']) && $_POST['action'] === 'mp_sms_tour_settings_save' && wp_verify_nonce($_POST['mp_sms_tour_settings_nonce'], 'mp_sms_tour_settings')) 
            {
                $sanitized_options = array();
                if (isset($_POST['mp_sms_tour_settings']['use_feature'])) 
                {
                    $sanitized_options['use_feature'] = sanitize_text_field($_POST['mp_sms_tour_settings']['use_feature']);
                }

                update_option('mp_sms_tour_settings', $sanitized_options);

                wp_safe_redirect(admin_url('admin.php?page=mp-sms#mp_sms_tour_settings'));
                exit();

            }

        }

        public function mp_admin_notice()
        {				
            MP_SMS_Helper::mp_error_notice($this->error);
        }
        
    }

    new MP_SMS_Tour();
}