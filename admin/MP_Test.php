<?php
if (!defined('ABSPATH'))
{
    die;
} // Cannot access pages directly.

/**
 * Class for any testing purposes
 *
 * @since 1.0
 *  
 * */
if (!class_exists('MP_Test'))
{
    class MP_Test 
    {
        public function __construct()
        {
            add_action('admin_init', [ $this, 'test' ]);
        }

        public function test()
        {
            // $order_id = 854;
            // $upload_dir                     = wp_upload_dir();
            // $pdf_url                    = $upload_dir['basedir'] . '/' . esc_html__( 'Ticket', 'ttbm-pro' ) . $order_id . '.pdf';
            // do_action('ttbm_generate_pdf',$order_id,$pdf_url,'mail');
            // echo "<pre>"; print_r($pdf_url);echo "</pre>";exit;        
            // $array = MP_SMS_Function::get_option('mp_sms_woocommerce_settings[template_for_on_hold]','');
            // echo "<pre>";print_r($array);exit;
            // $shortcodes = array_merge(apply_filters('mp_sms_tour_shortcodes',array()),apply_filters('mp_sms_wc_shortcodes',array()));
            // echo "<pre>";print_r($shortcodes);echo "<pre>";exit;
            // echo "<pre>"; print_r($date);exit;
            // do_action('mp_sms',array('order_id'=>644));
            // $test = MP_SMS_Function::format_mobile_number('BD','01770000099');
            // echo "<pre>";print_r($test);exit;
        }      

        
    }

    new MP_Test();
}