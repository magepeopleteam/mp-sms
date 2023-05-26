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
            $order = wc_get_order(229);
            $order_items = $order->get_items();
            foreach ($order_items as $order_item)
            {
                //echo "<pre>"; print_r($order_item);exit;
            }
            //$item = new WC_Order_Item_Product('58');
            // $product_id = $item->get_product_id();
            // $product = wc_get_product( $product_id );
            
            //$date = MP_SMS_Helper::check_plugin('woocommerce','woocommerce.php');
            
            //echo "<pre>"; print_r($date);exit;
            $test = MP_SMS_Helper::format_mobile_number('BD','01770000099');
            //echo "<pre>";print_r($test);exit;
        }
        
    }

    new MP_Test();
}