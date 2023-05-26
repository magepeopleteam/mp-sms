<?php

    if ( ! defined( 'ABSPATH' ) ) 
	{
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MP_SMS_Layout' ) ) 
	{
		class MP_SMS_Layout 
		{
			public function __construct() 
			{
				
			}

			public static function switch_button($name, $checked , $label='')
			{
				?>
					<div>
						<label class="roundSwitchLabel">
							<input type="checkbox" name="<?php echo $name;?>" <?php echo $checked; ?> />
							<span class="roundSwitch"></span>
						</label> <?php echo $label; ?>
					</div>

				<?php
			}
		}

		new MP_SMS_Layout();
	}


?>