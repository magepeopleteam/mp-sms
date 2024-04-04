<?php

if (!defined('ABSPATH'))
{
    die;
} // Cannot access pages directly.

if (!class_exists('MP_SMS_Tour'))
{
    class MP_SMS_Tour 
    {
        private $error;

        public function __construct()
        {
            $this->error = new WP_Error();
            if(MP_SMS_Function::check_plugin('tour-booking-manager','tour-booking-manager.php') == '1')
            {
                add_action('mp_sms_tab', array($this, 'tab_item'));
                add_action('mp_sms_tab_content', array($this, 'tab_content'));
                add_action('admin_init', array($this, 'save_mp_sms_tour_settings'));           
                add_action('admin_notices',array($this, 'mp_admin_notice' ) );
                add_action('mp_sms_tour_settings', array($this,'mp_sms_tour_settings'));
                add_action('mp_sms_trigger', array($this,'send_sms'),10,2);
                add_shortcode('mp_sms_tour_name' , array($this,'tour_name') );
                add_shortcode('mp_sms_tour_date' , array($this,'tour_date') );
                add_filter('mp_sms_get_tour_shortcodes', array($this,'get_shortcodes'));
                add_filter('mp_sms_tour_sms', array($this,'get_sms'),10,2);
                add_filter('mp_sms_tour_shortcodes', array($this,'shortcode_list') );
            }
        }

        public function tab_item()
        {
            ?>
                <li class="tab-item" data-tabs-target="#mp_sms_tour_settings"><?php esc_html_e('Tour SMS Settings','mp-sms');?></li>
            <?php
        }

        public static function post_link_key()
        {
            return 'link_ttbm_id';
        }

        public function shortcode_list($shortcodes)
        {
            $array = array(
                'mp_sms_tour_short_codes' => array(
                    'wp_post_link_key' => self::post_link_key(),
                    'shortcodes' => array(
                        'tour_name' =>'mp_sms_tour_name',
                        'tour_date' =>'mp_sms_tour_date',
                    )
                )
            );

            return array_merge($shortcodes,$array);
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

        public function mp_sms_tour_settings()
        {
            $sms_tour_settings = MP_SMS_Function::get_option('mp_sms_tour_settings','');
            $shortcodes = array_merge(apply_filters('mp_sms_wc_shortcodes',array()),apply_filters('mp_sms_tour_shortcodes',array()));
            $shortcode_string = MP_SMS_Function::format_shortcodes_as_string($shortcodes);
            $sms_woocommerce_settings = MP_SMS_Function::get_array_from_array('woocommerce_settings',$sms_tour_settings);
            ?>

                <div class="accordion">
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <label for="mp_sms_tour_settings[use_feature]"><strong><?php esc_html_e('Use Tour Plugin SMS ?','mp-sms');?></strong></label>
                            <?php MP_SMS_Layout::switch_button('mp_sms_tour_settings[use_feature]' ,'accordion-toggle' , 'mp_sms_tour_settings[use_feature]',  MP_SMS_Function::array_key_checked($sms_tour_settings,'use_feature'),''  ); ?>                                        
                        </div>
                        <div class="accordion-content">
                            <div class="accordion">
                                <div class="accordion-item">
                                    <div class="accordion-header">
                                        <label for="mp_sms_tour_settings[woocommerce_settings][use_feature]"><strong><?php esc_html_e('Use Tour Plugin SMS for Woocommerce Status ?','mp-sms');?></strong></label>
                                        <?php MP_SMS_Layout::switch_button('mp_sms_tour_settings[woocommerce_settings][use_feature]' ,'accordion-toggle' , 'mp_sms_tour_settings[woocommerce_settings][use_feature]',  MP_SMS_Function::array_key_checked($sms_woocommerce_settings,'use_feature'),''  ); ?>
                                    </div>
                                    <div class="accordion-content">
                                        <div class="accordion">
                                            <div class="accordion-item">
                                                <div class="accordion-header">
                                                    <label for="mp_sms_tour_settings[woocommerce_settings][feature_on_hold]"><strong><?php esc_html_e('Enable SMS template for Tour order status ( On-hold ) ?','mp-sms');?></strong></label>
                                                    <?php MP_SMS_Layout::switch_button('mp_sms_tour_settings[woocommerce_settings][feature_on_hold]' ,'accordion-toggle' , 'mp_sms_tour_settings[woocommerce_settings][feature_on_hold]',  MP_SMS_Function::array_key_checked($sms_woocommerce_settings,'feature_on_hold'),''  ); ?>
                                                </div>
                                                <div class="accordion-content">
                                                    <div id="mp_sms_tour_settings[woocommerce_settings][template_for_on_hold]" class="sms-template">
                                                        <?php MP_SMS_Layout::sms_shortcode_info($shortcode_string,'Dear {billing_name}, your order #{order_id} is now On-hold'); ?>
                                                        <textarea class="" name="mp_sms_tour_settings[woocommerce_settings][template_for_on_hold]" placeholder="<?php esc_html_e('Enter SMS template for Tour order status ( On-hold ) here...','mp-sms');?>"><?php echo esc_attr( $sms_tour_settings['woocommerce_settings']['template_for_on_hold']??'' ); ?></textarea>
                                                    </div>  
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion">
                                            <div class="accordion-item">
                                                <div class="accordion-header">
                                                    <label for="mp_sms_tour_settings[woocommerce_settings][feature_on_pending]"><strong><?php esc_html_e('Enable SMS template for Tour order status ( Pending ) ?','mp-sms');?></strong></label>
                                                    <?php MP_SMS_Layout::switch_button('mp_sms_tour_settings[woocommerce_settings][feature_on_pending]' ,'accordion-toggle' , 'mp_sms_tour_settings[woocommerce_settings][feature_on_pending]',  MP_SMS_Function::array_key_checked($sms_woocommerce_settings,'feature_on_pending'),''  ); ?>
                                                </div>
                                                <div class="accordion-content">
                                                    <div id="mp_sms_tour_settings[woocommerce_settings][template_for_on_hold]" class="sms-template">
                                                        <?php MP_SMS_Layout::sms_shortcode_info($shortcode_string,'Dear {billing_name}, your order #{order_id} is now Pending'); ?>
                                                        <textarea class="" name="mp_sms_tour_settings[woocommerce_settings][template_for_pending]" placeholder="<?php esc_html_e('Enter SMS template for Tour order status ( Pending ) here...','mp-sms');?>"><?php echo esc_attr( $sms_tour_settings['woocommerce_settings']['template_for_pending']??'' ); ?></textarea>
                                                    </div>  
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion">
                                            <div class="accordion-item">
                                                <div class="accordion-header">
                                                    <label for="mp_sms_tour_settings[woocommerce_settings][feature_on_processing]"><strong><?php esc_html_e('Enable SMS template for Tour order status ( Processing ) ?','mp-sms');?></strong></label>
                                                    <?php MP_SMS_Layout::switch_button('mp_sms_tour_settings[woocommerce_settings][feature_on_processing]' ,'accordion-toggle' , 'mp_sms_tour_settings[woocommerce_settings][feature_on_processing]',  MP_SMS_Function::array_key_checked($sms_woocommerce_settings,'feature_on_processing'),''  ); ?>
                                                </div>
                                                <div class="accordion-content">
                                                    <div id="mp_sms_tour_settings[woocommerce_settings][template_for_on_hold]" class="sms-template">
                                                        <?php MP_SMS_Layout::sms_shortcode_info($shortcode_string,'Dear {billing_name}, your order #{order_id} is now Processing'); ?>
                                                        <textarea class="" name="mp_sms_tour_settings[woocommerce_settings][template_for_processing]" placeholder="<?php esc_html_e('Enter SMS template for Tour order status ( Processing ) here...','mp-sms');?>"><?php echo esc_attr( $sms_tour_settings['woocommerce_settings']['template_for_processing']??'' ); ?></textarea>
                                                    </div>  
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion">
                                            <div class="accordion-item">
                                                <div class="accordion-header">
                                                    <label for="mp_sms_tour_settings[woocommerce_settings][feature_on_completed]"><strong><?php esc_html_e('Enable SMS template for Tour order status ( Completed ) ?','mp-sms');?></strong></label>
                                                    <?php MP_SMS_Layout::switch_button('mp_sms_tour_settings[woocommerce_settings][feature_on_completed]' ,'accordion-toggle' , 'mp_sms_tour_settings[woocommerce_settings][feature_on_completed]',  MP_SMS_Function::array_key_checked($sms_woocommerce_settings,'feature_on_completed'),''  ); ?>
                                                </div>
                                                <div class="accordion-content">
                                                    <div id="mp_sms_tour_settings[woocommerce_settings][template_for_on_hold]" class="sms-template">
                                                        <?php MP_SMS_Layout::sms_shortcode_info($shortcode_string,'Dear {billing_name}, your order #{order_id} is now Completed'); ?>
                                                        <textarea class="" name="mp_sms_tour_settings[woocommerce_settings][template_for_completed]" placeholder="<?php esc_html_e('Enter SMS template for Tour order status ( Completed ) here...','mp-sms');?>"><?php echo esc_attr( $sms_tour_settings['woocommerce_settings']['template_for_completed']??'' ); ?></textarea>
                                                    </div>  
                                                </div>
                                            </div>
                                        </div>  
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
        }

        public function tab_content()
        {
            ?>
                <div class="tab-content" id="mp_sms_tour_settings">
                    <div class="mp-sms-tour-settings">
                        <h3><?php esc_html_e('Tour Plugin SMS Settings','mp-sms');?></h3>
                        <form method="post" action="options.php">
                            <?php do_action('mp_sms_tour_settings'); ?>
                            <div class="action-button">
                                <?php echo wp_nonce_field('mp_sms_tour_settings', 'mp_sms_tour_settings_nonce'); ?>
                                <input type="hidden" name="action" value="mp_sms_tour_settings_save">
                                <input type="submit" name="submit" class="button" value="Save Settings">
                            </div>
                        </form>
                    </div>                    
                </div>
            <?php
        }

        public function get_shortcodes()
        {
            return array_merge(apply_filters('mp_sms_tour_shortcodes',array()),apply_filters('mp_sms_wc_shortcodes',array()));
        }

        public function get_sms($sms,$order_status)
        {     
            $mp_sms_tour_settings = MP_SMS_Function::get_option('mp_sms_tour_settings','');

            if($mp_sms_tour_settings['use_feature'] == 'on' && $mp_sms_tour_settings['woocommerce_settings']['use_feature'] == 'on')
            {
                if($order_status == 'on-hold')
                {
                    $sms = $mp_sms_tour_settings['woocommerce_settings']['template_for_on_hold'];
                }
                else if($order_status == 'pending')
                {
                    $sms = $mp_sms_tour_settings['woocommerce_settings']['template_for_pending'];
                }
                else if($order_status == 'processing')
                {
                    $sms = $mp_sms_tour_settings['woocommerce_settings']['template_for_processing'];
                }
                else if($order_status == 'completed')
                {
                    $sms = $mp_sms_tour_settings['woocommerce_settings']['template_for_completed'];
                }
            }

            return $sms;
        }

        public function send_sms($order,$item)
        {     
            $number = MP_SMS_Function::format_mobile_number($order->get_billing_country(),$order->get_billing_phone());
            $sms_array = array_filter($this->get_sms_text($order,$item));
            if(count($sms_array) && $number)
            {
                foreach($sms_array as $sms)
                {
                    do_action('mp_send_sms',array('numbers'=>$number,'sms'=>$sms));
                }
            }
        }

        public function get_sms_text($order,$item)
        {
            $sms_array = array();
            $shortcodes ='';
            $order_status = $order->get_status();

            if(MP_SMS_Function::get_item_post_type($item,MP_SMS_Tour::post_link_key()) == 'ttbm_tour')
            {
                $sms = apply_filters('mp_sms_tour_sms','',$order_status);
                $shortcodes = apply_filters('mp_sms_get_tour_shortcodes',array());
                $sms_array[] = MP_SMS_Function::prepare_sms_for_order($order->get_id(),$item->get_id(),$shortcodes,$sms);                    
            }

            return $sms_array;
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
                else
                {
                    $sanitized_options['use_feature'] = '';
                }

                if (isset($_POST['mp_sms_tour_settings']['woocommerce_settings']['use_feature'])) 
                {
                    $sanitized_options['woocommerce_settings']['use_feature'] = sanitize_text_field($_POST['mp_sms_tour_settings']['woocommerce_settings']['use_feature']);
                }

                if (isset($_POST['mp_sms_tour_settings']['woocommerce_settings']['feature_on_hold'])) 
                {
                    $sanitized_options['woocommerce_settings']['feature_on_hold'] = sanitize_text_field($_POST['mp_sms_tour_settings']['woocommerce_settings']['feature_on_hold']);
                }

                if (isset($_POST['mp_sms_tour_settings']['woocommerce_settings']['template_for_on_hold'])) 
                {
                    $sanitized_options['woocommerce_settings']['template_for_on_hold'] = sanitize_text_field($_POST['mp_sms_tour_settings']['woocommerce_settings']['template_for_on_hold']);
                }

                if (isset($_POST['mp_sms_tour_settings']['woocommerce_settings']['feature_on_pending'])) 
                {
                    $sanitized_options['woocommerce_settings']['feature_on_pending'] = sanitize_text_field($_POST['mp_sms_tour_settings']['woocommerce_settings']['feature_on_pending']);
                }

                if (isset($_POST['mp_sms_tour_settings']['woocommerce_settings']['template_for_pending'])) 
                {
                    $sanitized_options['woocommerce_settings']['template_for_pending'] = sanitize_text_field($_POST['mp_sms_tour_settings']['woocommerce_settings']['template_for_pending']);
                }

                if (isset($_POST['mp_sms_tour_settings']['woocommerce_settings']['feature_on_processing']))
                {
                    $sanitized_options['woocommerce_settings']['feature_on_processing'] = sanitize_text_field($_POST['mp_sms_tour_settings']['woocommerce_settings']['feature_on_processing']);
                }

                if (isset($_POST['mp_sms_tour_settings']['woocommerce_settings']['template_for_processing'])) 
                {
                    $sanitized_options['woocommerce_settings']['template_for_processing'] = sanitize_text_field($_POST['mp_sms_tour_settings']['woocommerce_settings']['template_for_processing']);
                }

                if (isset($_POST['mp_sms_tour_settings']['woocommerce_settings']['feature_on_completed'])) 
                {
                    $sanitized_options['woocommerce_settings']['feature_on_completed'] = sanitize_text_field($_POST['mp_sms_tour_settings']['woocommerce_settings']['feature_on_completed']);
                }

                if (isset($_POST['mp_sms_tour_settings']['woocommerce_settings']['template_for_completed'])) 
                {
                    $sanitized_options['woocommerce_settings']['template_for_completed'] = sanitize_text_field($_POST['mp_sms_tour_settings']['woocommerce_settings']['template_for_completed']);
                }

                update_option('mp_sms_tour_settings', $sanitized_options);

                wp_safe_redirect(admin_url('admin.php?page=mp-sms-settings#mp_sms_tour_settings'));
                exit();

            }

        }

        public function mp_admin_notice()
        {				
            MP_SMS_Function::mp_error_notice($this->error);
        }
        
    }

    new MP_SMS_Tour();
}