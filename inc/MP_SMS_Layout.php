<?php

    if ( ! defined( 'ABSPATH' ) ) 
	{
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MP_SMS_Layout' ) ) 
	{
		class MP_SMS_Layout 
		{
			public static function switch_button($id='',$class='',$name='',$status='',$data='')
            {
                $str_data = '';
                if(is_array($data) && count($data))
                {
                    foreach($data as $key=>$name)
                    {
                        $str_data .= 'data-'.$key.'="'.$name.'"';
                    }
                }

                ?>
                    <label class="switch">
                        <input type="checkbox" id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($class); ?>" name="<?php echo esc_attr($name); ?>" <?php echo esc_attr($status); ?>  <?php echo $str_data;?> >
                        <span class="slider"></span>
                    </label>
                <?php 
            }

            public static function sms_shortcode_info($shortcodes,$message)
            {
                ?>
                <div class="sms-template-info">
                    <p><b><?php esc_html_e('Available Shortcodes','mp-sms');?></b></p>
                    <p><?php echo esc_html($shortcodes); ?></p>
                    <p><b><?php esc_html_e('Example: ','mp-sms');?></b></p>
                    <p><?php esc_html_e($message,'mp-sms'); ?></p>
                </div>
                <?php
            }
		}

		new MP_SMS_Layout();
	}


?>