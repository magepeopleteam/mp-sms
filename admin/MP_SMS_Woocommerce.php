<?php
if (!defined('ABSPATH'))
{
    die;
} // Cannot access pages directly.

if (!class_exists('MP_SMS_Woocommerce'))
{
    class MP_SMS_Woocommerce 
    {
        private $error;
        
        public function __construct()
        {
            $this->error = new WP_Error();

            add_action('admin_notices',array($this, 'mp_admin_notice' ) );            
            if(MP_SMS_Function::check_plugin('woocommerce','woocommerce.php') == '1')
            {
                add_action('mp_sms_tab', array($this, 'tab_item'));
                add_action('mp_sms_tab_content', array($this, 'tab_content'));
                add_action('admin_init', [ $this, 'save_mp_sms_woocommerce_settings' ]);
                add_action('mp_sms_wc_settings_feature', array($this,'mp_sms_wc_settings_feature'));
                add_action('mp_sms_wc_settings', array($this,'mp_sms_wc_settings'));
                add_filter('mp_sms_post_link_list', array($this,'post_link_list'));
                add_action('woocommerce_checkout_order_processed', array($this, 'checkout_order_processed' ), 99 );
                add_action('woocommerce_order_status_changed', array($this,'order_status_changed'), 99, 3);
                add_action('mp_sms',array($this,'sms'), 10,1);
                add_action('mp_sms_trigger', array($this,'send_sms'),10,2);
                add_shortcode( 'mp_sms_wc_order_id' , array($this,'order_id') );
                add_shortcode( 'mp_sms_wc_billing_first_name' , array($this,'billing_first_name') );
                add_filter('mp_sms_get_wc_shortcodes', array($this,'get_shortcodes'));
                add_filter('mp_sms_wc_sms', array($this,'get_sms'),10,2);
                add_filter( 'mp_sms_wc_shortcodes', array($this,'shortcode_list') );
            }
            
        }

        public function tab_item()
        {
            if(MP_SMS_Function::only_woocommerce_installed())
            {
            ?>
                <!-- <li class="tab-item" data-tabs-target="#mp_sms_woocommerce_settings"><?php esc_html_e('Woocommerce SMS Settings','mp-sms');?></li> -->
            <?php
            }
        }

        public function post_link_list($post_links)
        {
            $array = array(
                'mp_sms_wc_link' => array(
                    'post_type' => 'ttbm_tour',
                    'post_link_key' => 'wc',
                )
            );

            return array_merge($post_links,$array);
        }

        public function shortcode_list($shortcodes)
        {
            $array = array(
                'mp_sms_wc_short_codes' => array(
                    'wp_post_link_key' => 'wc',
                    'shortcodes' => array(
                        'order_id' =>'mp_sms_wc_order_id',
                        'billing_name' =>'mp_sms_wc_billing_first_name',
                    )
                )
            );

            return array_merge($shortcodes,$array);
        }

        public function get_shortcodes()
        {
            return apply_filters('mp_sms_wc_shortcodes',array());
        }

        public function get_sms($sms,$order_status)
        {     
            $mp_sms_woocommerce_settings = MP_SMS_Function::get_option('mp_sms_woocommerce_settings','');

            if($mp_sms_woocommerce_settings['use_feature'] == 'on' && $mp_sms_woocommerce_settings['woocommerce_settings']['use_feature'] == 'on')
            {
                if($order_status == 'on-hold')
                {
                    $sms = $mp_sms_woocommerce_settings['woocommerce_settings']['template_for_on_hold'];
                }
                else if($order_status == 'pending')
                {
                    $sms = $mp_sms_woocommerce_settings['woocommerce_settings']['template_for_pending'];
                }
                else if($order_status == 'processing')
                {
                    $sms = $mp_sms_woocommerce_settings['woocommerce_settings']['template_for_processing'];
                }
                else if($order_status == 'completed')
                {
                    $sms = $mp_sms_woocommerce_settings['woocommerce_settings']['template_for_completed'];
                }
            }

            return $sms;
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

        public function checkout_order_processed($order_id)
        {
            if($this->check_enabled())
            {
                do_action('mp_sms',array('order_id'=>$order_id));
            }
            
        }

        public function order_status_changed($order_id,$old_status,$new_status)
        {
            if($this->check_enabled())
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
            $general_settings = MP_SMS_Function::get_option('mp_sms_general_settings','');
            
            if(is_array($general_settings) && (array_key_exists('use_feature',$general_settings) && $general_settings['use_feature'] == "on"))
            {
                $mp_sms_woocommerce_settings = MP_SMS_Function::get_option('mp_sms_woocommerce_settings','');
                if(is_array($mp_sms_woocommerce_settings) && (array_key_exists('use_feature',$mp_sms_woocommerce_settings) && $mp_sms_woocommerce_settings['use_feature'] == "on"))
                {
                    return 1;
                }
                else
                {
                    return 0;
                }
            }
            else
            {
                return 0;
            }
            
        }

        public function sms($args)
        {
            if(!$this->error->has_errors())
            {
                if(is_array($args) && isset($args['order_id']))
                {
                    $order = wc_get_order( $args['order_id'] );

                    if ( $order )
                    {
                        $order_items = $order->get_items();

                        if(count($order_items))
                        {
                            foreach($order_items as $item)
                            {
                                do_action('mp_sms_trigger', $order, $item);
                            }
                        }                    
                    }
                }
            }
        }

        public function send_sms($order,$item)
        {
            if(MP_SMS_Function::only_woocommerce_installed())
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
        }

        public function get_sms_text($order,$item)
        {
            $sms_array = array();
            $shortcodes ='';
            $order_status = $order->get_status();

            $mp_sms_woocommerce_settings = MP_SMS_Function::get_option('mp_sms_woocommerce_settings','');
            if(is_array($mp_sms_woocommerce_settings) && (array_key_exists('woocommerce_settings',$mp_sms_woocommerce_settings) && array_key_exists('use_feature',$mp_sms_woocommerce_settings) && $mp_sms_woocommerce_settings['use_feature'] == "on"))
            {
                $sms = apply_filters('mp_sms_wc_sms','',$order_status);
                $shortcodes = apply_filters('mp_sms_get_wc_shortcodes',array());
                $sms_array[] = MP_SMS_Function::prepare_sms_for_order($order->get_id(),$item->get_id(),$shortcodes,$sms);
            }
            
            return $sms_array;
        }

        public function mp_sms_wc_settings_feature()
        {
            $sms_woocommerce_settings = MP_SMS_Function::get_option('mp_sms_woocommerce_settings',array());
            ?>
                <div class="accordion">
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <label for="mp_sms_woocommerce_settings[use_feature]"><strong><?php esc_html_e('Use Woocommerce SMS ?','mp-sms');?></strong></label>
                            <?php MP_SMS_Layout::switch_button('mp_sms_woocommerce_settings[use_feature]' ,'accordion-toggle' , 'mp_sms_woocommerce_settings[use_feature]',  MP_SMS_Function::array_key_checked($sms_woocommerce_settings,'use_feature','checked'),''  ); ?>                                        
                        </div>
                    </div>
                </div>
            <?php
        }

        public function mp_sms_wc_settings()
        {
            $sms_woocommerce_settings = MP_SMS_Function::get_option('mp_sms_woocommerce_settings',array());
            $shortcodes = apply_filters('mp_sms_wc_shortcodes',array());
            $shortcode_string = MP_SMS_Function::format_shortcodes_as_string($shortcodes);
            $woocommerce_settings = MP_SMS_Function::get_array_from_array('woocommerce_settings',$sms_woocommerce_settings);
            ?>
                <div class="accordion">
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <label for="mp_sms_woocommerce_settings[use_feature]"><strong><?php esc_html_e('Use Woocommerce SMS ?','mp-sms');?></strong></label>
                            <?php MP_SMS_Layout::switch_button('mp_sms_woocommerce_settings[use_feature]' ,'accordion-toggle' , 'mp_sms_woocommerce_settings[use_feature]',  MP_SMS_Function::array_key_checked($sms_woocommerce_settings,'use_feature','checked'),''  ); ?>                                        
                        </div>
                        <?php 
                        if(MP_SMS_Function::only_woocommerce_installed() == 2)
                        {
                        ?>
                        <div class="accordion-content">
                            <div class="accordion">
                                <div class="accordion-item">
                                    <div class="accordion-header">
                                        <label for="mp_sms_woocommerce_settings[woocommerce_settings][use_feature]"><strong><?php esc_html_e('Use SMS for Woocommerce Status ?','mp-sms');?></strong></label>
                                        <?php MP_SMS_Layout::switch_button('mp_sms_woocommerce_settings[woocommerce_settings][use_feature]' ,'accordion-toggle' , 'mp_sms_woocommerce_settings[woocommerce_settings][use_feature]',  MP_SMS_Function::array_key_checked($woocommerce_settings,'use_feature'),''  ); ?>
                                    </div>
                                    <div class="accordion-content">
                                        <div class="accordion">
                                            <div class="accordion-item">
                                                <div class="accordion-header">
                                                    <label for="mp_sms_woocommerce_settings[woocommerce_settings][feature_on_hold]"><strong><?php esc_html_e('Enable SMS template for order status ( On-hold ) ?','mp-sms');?></strong></label>
                                                    <?php MP_SMS_Layout::switch_button('mp_sms_woocommerce_settings[woocommerce_settings][feature_on_hold]' ,'accordion-toggle' , 'mp_sms_woocommerce_settings[woocommerce_settings][feature_on_hold]',  MP_SMS_Function::array_key_checked($woocommerce_settings,'feature_on_hold'),''  ); ?>
                                                </div>
                                                <div class="accordion-content">
                                                    <div id="mp_sms_woocommerce_settings[woocommerce_settings][template_for_on_hold]" class="sms-template">
                                                        <?php MP_SMS_Layout::sms_shortcode_info($shortcode_string,'Dear {billing_name}, your order #{order_id} is now On-hold'); ?>
                                                        <textarea class="" name="mp_sms_woocommerce_settings[woocommerce_settings][template_for_on_hold]" placeholder="<?php esc_html_e('Enter SMS template for order status ( On-hold ) here...','mp-sms');?>"><?php echo esc_attr( $sms_woocommerce_settings['woocommerce_settings']['template_for_on_hold']??'' ); ?></textarea>
                                                    </div>  
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion">
                                            <div class="accordion-item">
                                                <div class="accordion-header">
                                                    <label for="mp_sms_woocommerce_settings[woocommerce_settings][feature_on_pending]"><strong><?php esc_html_e('Enable SMS template for order status ( Pending ) ?','mp-sms');?></strong></label>
                                                    <?php MP_SMS_Layout::switch_button('mp_sms_woocommerce_settings[woocommerce_settings][feature_on_pending]' ,'accordion-toggle' , 'mp_sms_woocommerce_settings[woocommerce_settings][feature_on_pending]',  MP_SMS_Function::array_key_checked($woocommerce_settings,'feature_on_pending'),''  ); ?>
                                                </div>
                                                <div class="accordion-content">
                                                    <div id="mp_sms_woocommerce_settings[woocommerce_settings][template_for_on_hold]" class="sms-template">
                                                        <?php MP_SMS_Layout::sms_shortcode_info($shortcode_string,'Dear {billing_name}, your order #{order_id} is now Pending'); ?>
                                                        <textarea class="" name="mp_sms_woocommerce_settings[woocommerce_settings][template_for_pending]" placeholder="<?php esc_html_e('Enter SMS template for order status ( Pending ) here...','mp-sms');?>"><?php echo esc_attr( $sms_woocommerce_settings['woocommerce_settings']['template_for_pending']??'' ); ?></textarea>
                                                    </div>  
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion">
                                            <div class="accordion-item">
                                                <div class="accordion-header">
                                                    <label for="mp_sms_woocommerce_settings[woocommerce_settings][feature_on_processing]"><strong><?php esc_html_e('Enable SMS template for order status ( Processing ) ?','mp-sms');?></strong></label>
                                                    <?php MP_SMS_Layout::switch_button('mp_sms_woocommerce_settings[woocommerce_settings][feature_on_processing]' ,'accordion-toggle' , 'mp_sms_woocommerce_settings[woocommerce_settings][feature_on_processing]',  MP_SMS_Function::array_key_checked($woocommerce_settings,'feature_on_processing'),''  ); ?>
                                                </div>
                                                <div class="accordion-content">
                                                    <div id="mp_sms_woocommerce_settings[woocommerce_settings][template_for_on_hold]" class="sms-template">
                                                        <?php MP_SMS_Layout::sms_shortcode_info($shortcode_string,'Dear {billing_name}, your order #{order_id} is now Processing'); ?>
                                                        <textarea class="" name="mp_sms_woocommerce_settings[woocommerce_settings][template_for_processing]" placeholder="<?php esc_html_e('Enter SMS template for order status ( Processing ) here...','mp-sms');?>"><?php echo esc_attr( $sms_woocommerce_settings['woocommerce_settings']['template_for_processing']??'' ); ?></textarea>
                                                    </div>  
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion">
                                            <div class="accordion-item">
                                                <div class="accordion-header">
                                                    <label for="mp_sms_woocommerce_settings[woocommerce_settings][feature_on_completed]"><strong><?php esc_html_e('Enable SMS template for order status ( Completed ) ?','mp-sms');?></strong></label>
                                                    <?php MP_SMS_Layout::switch_button('mp_sms_woocommerce_settings[woocommerce_settings][feature_on_completed]' ,'accordion-toggle' , 'mp_sms_woocommerce_settings[woocommerce_settings][feature_on_completed]',  MP_SMS_Function::array_key_checked($woocommerce_settings,'feature_on_completed'),''  ); ?>
                                                </div>
                                                <div class="accordion-content">
                                                    <div id="mp_sms_woocommerce_settings[woocommerce_settings][template_for_on_hold]" class="sms-template">
                                                        <?php MP_SMS_Layout::sms_shortcode_info($shortcode_string,'Dear {billing_name}, your order #{order_id} is now Completed'); ?>
                                                        <textarea class="" name="mp_sms_woocommerce_settings[woocommerce_settings][template_for_completed]" placeholder="<?php esc_html_e('Enter SMS template for order status ( Completed ) here...','mp-sms');?>"><?php echo esc_attr( $sms_woocommerce_settings['woocommerce_settings']['template_for_completed']??'' ); ?></textarea>
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
                        ?>
                    </div>
                </div>
            <?php
        }

        public function error_detect()
        {
            if(MP_SMS_Function::check_plugin('woocommerce','woocommerce.php') != '1') 
            {
                $this->error->add('invalid_data', esc_html__( 'Oops! Woocommerce is not installed or activeted properly !!! ', 'mp-sms' ));
            }

            $general_settings = MP_SMS_Function::get_option('mp_sms_general_settings','');

            if(is_array($general_settings) && (array_key_exists('use_feature',$general_settings) && $general_settings['use_feature'] == "on"))
            {
                $mp_sms_woocommerce_settings = MP_SMS_Function::get_option('mp_sms_woocommerce_settings','');
                if ( array_key_exists('use_feature',$mp_sms_woocommerce_settings) &&  $mp_sms_woocommerce_settings['use_feature'] == "on" )
                {

                }
            }

            $feature = MP_SMS_Function::get_option('mp_sms_woocommerce_settings[use_feature]','');
            $sms_feature = MP_SMS_Function::get_option('mp_sms_general_settings[use_feature]','');
            $sms_provider = MP_SMS_Function::get_option('mp_sms_general_settings[sms_provider]','');				

            if(MP_SMS_Function::check_plugin('woocommerce','woocommerce.php') == '1' && $feature == "on" && $sms_feature == "on" && $sms_provider != '')
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
            ?>
                <div class="tab-content" id="mp_sms_woocommerce_settings">
                    <div class="mp-sms-tour-settings">
                        <h3><?php esc_html_e('Woocommerce SMS Settings','mp-sms');?></h3>
                        <form method="post" action="options.php">
                            <?php do_action('mp_sms_wc_settings'); ?>                            
                            <div class="action-button">
                                <?php echo wp_nonce_field('mp_sms_woocommerce_settings', 'mp_sms_woocommerce_settings_nonce'); ?>
                                <input type="hidden" name="action" value="mp_sms_woocommerce_settings_save">
                                <input type="submit" name="submit" class="button" value="Save Settings">
                            </div>
                        </form>
                    </div>                    
                </div>
            <?php
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

                wp_safe_redirect(admin_url('admin.php?page=mp-sms-settings#mp_sms_woocommerce_settings'));
                exit();

            }

        }

        public function mp_admin_notice()
        {				
            MP_SMS_Function::mp_error_notice($this->error);
        }
        
    }

    new MP_SMS_Woocommerce();
}