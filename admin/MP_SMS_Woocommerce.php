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
if (!class_exists('MP_SMS_Woocommerce'))
{
    class MP_SMS_Woocommerce 
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
            add_action('admin_init', [ $this, 'save_mp_sms_woocommerce_settings' ]);            
            add_action('wp_loaded', array( $this,'apply' ) );
            add_action('woocommerce_checkout_order_processed', array($this, 'checkout_order_processed' ), 10 );
            add_action('woocommerce_order_status_changed', array($this,'order_status_changed'), 10, 3);
            add_action('mp_sms',array($this,'sms'), 10,1);
            add_action('admin_notices',array($this, 'mp_admin_notice' ) );
            add_action('init', array($this,'shortcodes') );
        }

        public function tab_item()
        {
            if($this->available == "yes")
            {
            ?>
                <li class="tab-item" data-tabs-target="#mp_sms_woocommerce_settings">SMS Woocomemrce Settings</li>
            <?php
            }
        }

        public function shortcodes()
        {
            add_shortcode( 'mp_sms_wc_order_id' , array($this,'order_id') );
            add_shortcode( 'mp_sms_wc_billing_first_name' , array($this,'billing_first_name') );
        }

        public function order_id( $atts )
        {   
            $order = wc_get_order(  $atts['order_id'] );
            return $order->get_id();           
        }

        public function billing_first_name( $atts )
        {
            $order = wc_get_order(  $atts['order_id'] );
            return $order->get_billing_first_name();
        }

        public function apply()
        {
            $this->error = new WP_Error();
            $this->available = ( MP_SMS_Helper::check_plugin('woocommerce','woocommerce.php') ) ? 'yes' : 'no';
            $this->feature = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['use_feature']);
            $this->sms_feature = MP_SMS_Helper::senitize(get_option('mp_sms_general_settings')['use_sms_features']);
            $this->sms_provider = MP_SMS_Helper::senitize(get_option('mp_sms_general_settings')['sms_provider']);
            $this->enabled = ( $this->check_enabled() == '1' ) ? 'yes' : 'no';
            
            if($this->available == 'no') 
            {
                $this->error->add('invalid_data', esc_html__( 'Oops! Woocommerce is not installed or activeted properly !!! ', 'mp-sms' ));
            }
        }

        public function checkout_order_processed($order_id)
        {
            if($this->enabled == 'yes')
            {
                do_action('mp_sms',array('order_id'=>$order_id));
            }
            
        }

        public function order_status_changed($order_id,$old_status,$new_status)
        {
            if($this->enabled == 'yes')
            {
                $order = wc_get_order( $order_id );
                
                if ($old_status != $new_status) 
                {                    
                    if ( $order->has_status( 'on-hold' ) || $order->has_status( 'pending' ) || $order->has_status( 'processing' ) || $order->has_status( 'completed' ))
                    {
                        do_action('mp_sms',array('order_id'=>$order_id));
                    }	                    
                    
                }
            }
            
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

        public function sms($args)
        {

            if($this->error->has_errors())
            {
                wp_die( print_r( $this->error->get_error_messages() , true ) );
            }

            if(is_array($args) && isset($args['order_id']))
            {
                $order = wc_get_order( $args['order_id'] );

                if ( empty( $args['order_id'] ) || ! $order )
                {
                    $error =  new WP_Error( 'invalid_data', esc_html__( 'Invalid order id provided', 'mp-sms' ));
                    wp_die( print_r( $error->get_error_messages(), true ) );
                }
            }

            $order_items = $order->get_items();

            foreach($order_items as $item)
            {
                $this->send_sms($order,$item);
            }				
            
        }

        public function send_sms($order,$item)
        {
            $number = $this->get_mobile_number($order);
            $sms = $this->get_sms_text($order,$item);
            do_action('mp_send_sms',array('numbers'=>$number,'sms'=>$sms));
        }

        public function get_mobile_number($order)
        {
            $mobile_number = MP_SMS_Helper::format_mobile_number($order->get_billing_country(),$order->get_billing_phone());
            return $mobile_number;
        }

        public function get_sms_text($order,$item)
        {
            $sms = '';
            $order_status = $order->get_status();
            
            if($order_status == 'on-hold' && MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['sms_for_on_hold']) == 'on')
            {
                $sms = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['template_for_on_hold']);
            }
            else if($order_status == 'pending' && MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['sms_for_pending']) == 'on')
            {
                $sms = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['template_for_pending']);
            }
            else if($order_status == 'processing' && MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['sms_for_processing']) == 'on')
            {
                $sms = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['template_for_processing']);
            }
            else if($order_status == 'completed' && MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['sms_for_completed']) == 'on')
            {
                $sms = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['template_for_completed']);
            }

            $sms = $this->prepare_sms_for_order($sms,$order,$item);

            return $sms;
        }

        public function prepare_sms_for_order($sms,$order,$item)
        {
            $codes = MP_SMS_Helper::contents($sms,'{','}');
            if(count($codes))
            {
                foreach($codes as $code)
                {
                    unset($shortcode);
                    unset($value);
                    $shortcode = '['.$code.' order_id="'.$order->get_id().'" item_id="'.$item->get_id().'"]';
                    $value = do_shortcode($shortcode);
                    $sms = str_replace('{'.$code.'}',$value,$sms);
                }
            }

            return $sms;
        }

        public function tab_content()
        {
            if($this->available == "yes")
            {
            ?>
                <div class="tab-content" id="mp_sms_woocommerce_settings">
                    <form method="post" action="options.php">
                        <?php
                            wp_nonce_field('mp_sms_woocommerce_settings', 'mp_sms_woocommerce_settings_nonce');
                            $use_feature = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['use_feature']);
                            $feature_checked = $use_feature == 'on' ? 'checked' : '';
                            $sms_for_on_hold = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['sms_for_on_hold']);
                            $on_hold_checked = $sms_for_on_hold == 'on' ? 'checked' : '';
                            $sms_for_pending = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['sms_for_pending']);
                            $pending_checked = $sms_for_pending == 'on' ? 'checked' : '';
                            $sms_for_processing = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['sms_for_processing']);
                            $processing_checked = $sms_for_processing == 'on' ? 'checked' : '';
                            $sms_for_completed = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['sms_for_completed']);
                            $completed_checked = $sms_for_completed == 'on' ? 'checked' : '';
                            $on_hold = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['template_for_on_hold']);
                            $pending = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['template_for_pending']);
                            $processing = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['template_for_processing']);
                            $completed = MP_SMS_Helper::senitize(get_option('mp_sms_woocommerce_settings')['template_for_completed']);
                        ?>
                        <table  class="form-table">
                            <tbody>
                                <tr>
                                    <td>Use Woocommerce SMS Feature ?</td>
                                    <td>
                                        <?php MP_SMS_Layout::switch_button( 'mp_sms_woocommerce_settings[use_feature]',  esc_attr($feature_checked) ); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Woocommerce Status</td>
                                    <td>
                                        <table>
                                            <tr>
                                                <td>
                                                    <label>                                                        
                                                        <?php MP_SMS_Layout::switch_button( 'mp_sms_woocommerce_settings[sms_for_on_hold]', $on_hold_checked ); ?>                                                       
                                                        On-hold
                                                    </label>                                                    
                                                </td>
                                                <td>
                                                    <div id="mp_sms_woocommerce_settings[sms_for_on_hold]" >
                                                        <textarea class="formControl" name="mp_sms_woocommerce_settings[template_for_on_hold]" placeholder="Enter On-hold SMS Template here..."><?php echo esc_attr( $on_hold??'' ); ?></textarea>
                                                    </div>                                                        
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label>                                                        
                                                        <?php MP_SMS_Layout::switch_button( 'mp_sms_woocommerce_settings[sms_for_pending]', $pending_checked ); ?>                                                       
                                                        Pending
                                                    </label>                                                    
                                                </td>
                                                <td>
                                                    <div id="mp_sms_woocommerce_settings[sms_for_pending]" >
                                                        <textarea class="formControl" name="mp_sms_woocommerce_settings[template_for_pending]" placeholder="Enter On-hold SMS Template here..."><?php echo esc_attr( $pending??'' ); ?></textarea>
                                                    </div>                                                        
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label>                                                        
                                                        <?php MP_SMS_Layout::switch_button( 'mp_sms_woocommerce_settings[sms_for_processing]', $processing_checked ); ?>                                                       
                                                        Processing
                                                    </label>                                                    
                                                </td>
                                                <td>
                                                    <div id="mp_sms_woocommerce_settings[sms_for_processing]" >
                                                        <textarea class="formControl" name="mp_sms_woocommerce_settings[template_for_processing]" placeholder="Enter On-hold SMS Template here..."><?php echo esc_attr( $processing??'' ); ?></textarea>
                                                    </div>                                                        
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label>                                                        
                                                        <?php MP_SMS_Layout::switch_button( 'mp_sms_woocommerce_settings[sms_for_completed]', $completed_checked ); ?>                                                       
                                                        Completed
                                                    </label>                                                    
                                                </td>
                                                <td>
                                                    <div id="mp_sms_woocommerce_settings[sms_for_completed]" >
                                                        <textarea class="formControl" name="mp_sms_woocommerce_settings[template_for_completed]" placeholder="Enter On-hold SMS Template here..."><?php echo esc_attr( $completed??'' ); ?></textarea>
                                                    </div>                                                        
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div style="float:right;padding-left: 10px">
                                            <input type="hidden" name="action" value="mp_sms_woocommerce_settings_save">
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

        public function save_mp_sms_woocommerce_settings()
        {
            if (isset($_POST['action']) && $_POST['action'] === 'mp_sms_woocommerce_settings_save' && wp_verify_nonce($_POST['mp_sms_woocommerce_settings_nonce'], 'mp_sms_woocommerce_settings')) 
            {
                $sanitized_options = array();
                if (isset($_POST['mp_sms_woocommerce_settings']['use_feature'])) 
                {
                    $sanitized_options['use_feature'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['use_feature']);
                }
                if (isset($_POST['mp_sms_woocommerce_settings']['sms_for_on_hold'])) 
                {
                    $sanitized_options['sms_for_on_hold'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['sms_for_on_hold']);
                }
                if (isset($_POST['mp_sms_woocommerce_settings']['sms_for_pending'])) 
                {
                    $sanitized_options['sms_for_pending'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['sms_for_pending']);
                }
                if (isset($_POST['mp_sms_woocommerce_settings']['sms_for_processing'])) 
                {
                    $sanitized_options['sms_for_processing'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['sms_for_processing']);
                }
                if (isset($_POST['mp_sms_woocommerce_settings']['sms_for_completed'])) 
                {
                    $sanitized_options['sms_for_completed'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['sms_for_completed']);
                }

                if (isset($_POST['mp_sms_woocommerce_settings']['template_for_on_hold'])) 
                {
                    $sanitized_options['template_for_on_hold'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['template_for_on_hold']);
                }
                if (isset($_POST['mp_sms_woocommerce_settings']['template_for_pending'])) 
                {
                    $sanitized_options['template_for_pending'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['template_for_pending']);
                }
                if (isset($_POST['mp_sms_woocommerce_settings']['template_for_processing'])) 
                {
                    $sanitized_options['template_for_processing'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['template_for_processing']);
                }
                if (isset($_POST['mp_sms_woocommerce_settings']['template_for_completed'])) 
                {
                    $sanitized_options['template_for_completed'] = sanitize_text_field($_POST['mp_sms_woocommerce_settings']['template_for_completed']);
                }
                
                update_option('mp_sms_woocommerce_settings', $sanitized_options);

                wp_safe_redirect(admin_url('admin.php?page=mp-sms#mp_sms_woocommerce_settings'));
                exit();

            }

        }

        public function mp_admin_notice()
        {				
            MP_SMS_Helper::mp_error_notice($this->error);
        }
        
    }

    new MP_SMS_Woocommerce();
}