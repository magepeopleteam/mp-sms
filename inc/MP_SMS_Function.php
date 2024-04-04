<?php

	if ( ! defined( 'ABSPATH' ) )
	{
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MP_SMS_Function' ) ) 
	{
		class MP_SMS_Function 
		{
			public static function sanitize($data)
			{
				$data = maybe_unserialize($data);

				if (is_string($data)) 
				{
					$data = maybe_unserialize($data);

					if (is_array($data)) 
					{
						$data = self::senitize($data);
					}
					else 
					{
						$data = sanitize_text_field($data);
					}
				}
				else if (is_array($data)) 
				{
					foreach ($data as &$value) 
					{
						if (is_array($value)) 
						{
							$value = self::senitize($value);
						}
						else 
						{
							$value = sanitize_text_field($value);
						}
					}
				}

				return $data;
			}

			public static function get_option($option,$default)
			{
				$return = '';

				$keys = self::extract_array_keys_from_string($option);

				$count_keys = count($keys);

				if($count_keys)
				{
					$value = get_option($keys[0]);

					if(empty($value))
					{
						$return = $default;
					}
					else if(is_array($value))
					{
						$return = self::get_value_from_nested_array($value,array_slice($keys, 1),$default);
					}
					else
					{
						$return = $value;
					}
					
				}
				else
				{
					$return = $default;
				}

				if(is_array($return))
				{
					return array_map(array('MP_SMS_Function','sanitize_array'), $return);
				}
				
				return self::senitize($return);
			}

			public static function get_value_from_nested_array($multidimensionalArray, $keys, $defaultValue = null)
			{
				foreach ($keys as $key) 
				{
					if (!array_key_exists($key, $multidimensionalArray))
					{
						return $defaultValue;
					}
					
					$multidimensionalArray = $multidimensionalArray[$key];
				}
				
				return $multidimensionalArray;
			}

			public static function check_key_exist($multidimensionalArray, $keys)
			{
				foreach ($keys as $key) 
				{

					if (!array_key_exists($key, $multidimensionalArray)) 
					{
						return false;
					}
					
					$multidimensionalArray = $multidimensionalArray[$key];
				}
				
				return true;
			}

			public static function sanitize_array($value) 
			{
				return is_array($value) ? array_map(array('MP_SMS_Function','sanitize_array'), $value) : sanitize_text_field($value);
			}

			public static function senitize($string)
			{
				return sanitize_text_field($string);
			}

			public static function only_woocommerce_installed()
			{
				if(self::check_plugin('mage-eventpress','woocommerce-event-press.php') == 1 || self::check_plugin('tour-booking-manager','tour-booking-manager.php') == 1 )
				{
					return 0;
				}
				else
				{
					return 1;
				}
			}

			public static function check_plugin($plugin_dir_name,$plugin_file): int 
			{
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$plugin_dir = ABSPATH . 'wp-content/plugins/'.$plugin_dir_name;
				if ( is_plugin_active( $plugin_dir_name.'/'.$plugin_file ) ) 
				{
					return 1;
				} 
				elseif ( is_dir( $plugin_dir ) ) 
				{
					return 2;
				} 
				else 
				{
					return 0;
				}
			}

			public static function get_link($url)
			{
				return '<a id="get-link" href="' . $url .'" class="page-title-action">Click here</a>';
			}

			public static function mp_error_notice($error)
			{				
				if($error->has_errors())
				{
					foreach($error->get_error_messages() as $error)
					{
						$class = 'notice notice-error';
						printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $error ) );
					}
					
				}
			}

			public static function format_shortcodes_as_string($shortcodes)
			{
				$string = '';
				$custom_shortcodes = array();				
				$count = count($shortcodes);
				if($count)
				{
					foreach($shortcodes as $key=>$shortcodelist)
					{
						if(is_array($shortcodelist['shortcodes']) && count($shortcodelist['shortcodes']))
						{
							foreach($shortcodelist['shortcodes'] as $key=>$code)
							{
								$custom_shortcodes[$key] = $code;
							}
						}							
					}

				}

				$custom_count = count($custom_shortcodes);

				if($custom_count)
				{
					foreach($custom_shortcodes as $key=>$custom_code)
					{
						if($custom_count > 1)
						{
							$string.=" {".$key."} ,";
						}
						else
						{
							$string.=" {".$key."}";
						}
						$custom_count--;
					}
				}
				
				return $string;
				
			}

			public static function extract_array_keys_from_string($string) : array 
			{
				$parent_key = array(explode('[', $string, 2)[0]);
				$child_keys = self::contents($string,'[',']');
				$keys = array_merge($parent_key,$child_keys);
				return $keys;
			}

			public static function contents($string, $start, $end)
			{
				$result = array();
				foreach (explode($start, $string) as $key => $value) 
				{
					if(strpos($value, $end) !== FALSE)
					{
						$result[] = substr($value, 0, strpos($value, $end));
					}
				}
				return $result;
			}

			public static function shortcode_list( $atts )
			{
				$domain = $atts['domain'];
				global $shortcode_tags;

				$shortcodes = $shortcode_tags;
				
				ksort($shortcodes);
				
				$shortcode_output = array();
				
				foreach ($shortcodes as $shortcode => $value) 
				{
					if(substr($shortcode, 0, strlen($domain)) === $domain)
					{
						$shortcode_output[] = $shortcode;
					}
				}
				
				return $shortcode_output;
			
			}

			public static function prepare_sms_for_order($order_id,$item_id,$shortcodes,$sms)
			{
				$codes = self::contents($sms,'{','}');
				if(count($codes))
				{
					foreach($codes as $code)
					{
						unset($value);                    
						$value = '';
						if(is_array($shortcodes) && count($shortcodes))
						{
							foreach($shortcodes as $shortcodelist)
							{
								//echo "<pre>";print_r($shortcodelist);exit;
								if(is_array($shortcodelist['shortcodes']) && count($shortcodelist['shortcodes']))
								{
									foreach($shortcodelist['shortcodes'] as $key=>$custom_short_code)
									{
										if($key == $code)
										{
											$shortcode_text = '['.$custom_short_code.' order_id="'.$order_id.'" item_id="'.$item_id.'"]';
											$value = do_shortcode($shortcode_text);
											$sms = str_replace('{'.$code.'}',$value,$sms);
										}
									}
								}                            
								
							}

						}
						
					}
				}

				return $sms;
			}

			public static function install_woocommerce()
			{
				try 
				{
					include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
					include_once(ABSPATH . 'wp-admin/includes/file.php');
					include_once(ABSPATH . 'wp-admin/includes/misc.php');
					include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

					$plugin_slug = 'woocommerce';

					$api = plugins_api('plugin_information', array(
						'slug' => $plugin_slug,
						'fields' => array(
							'short_description' => false,
							'sections' => false,
							'requires' => false,
							'rating' => false,
							'ratings' => false,
							'downloaded' => false,
							'last_updated' => false,
							'added' => false,
							'tags' => false,
							'compatibility' => false,
							'homepage' => false,
							'donate_link' => false,
						),
					));

					$woocommerce_plugin_upgrader = new Plugin_Upgrader(new Plugin_Installer_Skin(compact('title', 'url', 'nonce', 'plugin', 'api')));

					$destination = WP_PLUGIN_DIR;

					ob_start();
					$result = $woocommerce_plugin_upgrader->install($api->download_link, array('destination' => $destination));
					ob_end_clean();

					activate_plugin($plugin_slug . '/woocommerce.php');

					if ($result === true) 
					{
						$response = array(
							'status' => 'success',
							'message' => 'WooCommerce installed and activated successfully !!!',
						);
					}
					else 
					{
						$response = array(
							'status' => 'error',
							'message' => 'Error installing WooCommerce: ' . $result->get_error_message(),
						);
					}

					return $response;
				}
				catch (Exception $e) 
				{
					$response = array(
						'status' => 'error',
						'message' => 'Error: ' . $e->getMessage(),
					);

					return $response;
				}
			}

			public static function get_iso_country_mobile_details($country_code)
			{
				$country_codes = array(
					"AF" => array( "icc" => "93" , "nsn" => "9" ) ,
					"AL" => array( "icc" => "355" , "nsn" => "" ) ,
					"DZ" => array( "icc" => "213" , "nsn" => "4" ) ,
					"AS" => array( "icc" => "1-684" , "nsn" => "" ) ,
					"AD" => array( "icc" => "376" , "nsn" => "AD" ) ,
					"AO" => array( "icc" => "244" , "nsn" => "AO" ) ,
					"AI" => array( "icc" => "1-264" , "nsn" => "AI" ) ,
					"AQ" => array( "icc" => "672" , "nsn" => "AQ" ) ,
					"AG" => array( "icc" => "1-268" , "nsn" => "AG" ) ,
					"AR" => array( "icc" => "54" , "nsn" => "AR" ) ,
					"AM" => array( "icc" => "374" , "nsn" => "AM" ) ,
					"AW" => array( "icc" => "297" , "nsn" => "AW" ) ,
					"AU" => array( "icc" => "61" , "nsn" => "AU" ) ,
					"AT" => array( "icc" => "43" , "nsn" => "AT" ) ,
					"AZ" => array( "icc" => "994" , "nsn" => "AZ" ) ,
					"BS" => array( "icc" => "1-242" , "nsn" => "BS" ) ,
					"BH" => array( "icc" => "973" , "nsn" => "BH" ) ,
					"BD" => array( "icc" => "880" , "nsn" => "10" ) ,
					"BB" => array( "icc" => "1-246" , "nsn" => "BB" ) ,
					"BY" => array( "icc" => "375" , "nsn" => "BY" ) ,
					"BE" => array( "icc" => "32" , "nsn" => "BE" ) ,
					"BZ" => array( "icc" => "501" , "nsn" => "BZ" ) ,
					"BJ" => array( "icc" => "229" , "nsn" => "BJ" ) ,
					"BM" => array( "icc" => "1-441" , "nsn" => "BM" ) ,
					"BT" => array( "icc" => "975" , "nsn" => "BT" ) ,
					"BO" => array( "icc" => "591" , "nsn" => "BO" ) ,
					"BA" => array( "icc" => "387" , "nsn" => "BA" ) ,
					"BW" => array( "icc" => "267" , "nsn" => "BW" ) ,
					"BR" => array( "icc" => "55" , "nsn" => "BR" ) ,
					"IO" => array( "icc" => "246" , "nsn" => "IO" ) ,
					"VG" => array( "icc" => "1-284" , "nsn" => "VG" ) ,
					"BN" => array( "icc" => "673" , "nsn" => "BN" ) ,
					"BG" => array( "icc" => "359" , "nsn" => "BG" ) ,
					"BF" => array( "icc" => "226" , "nsn" => "BF" ) ,
					"BI" => array( "icc" => "257" , "nsn" => "BI" ) ,
					"KH" => array( "icc" => "855" , "nsn" => "KH" ) ,
					"CM" => array( "icc" => "237" , "nsn" => "CM" ) ,
					"CA" => array( "icc" => "1" , "nsn" => "CA" ) ,
					"CV" => array( "icc" => "238" , "nsn" => "CV" ) ,
					"KY" => array( "icc" => "1-345" , "nsn" => "KY" ) ,
					"CF" => array( "icc" => "236" , "nsn" => "CF" ) ,
					"TD" => array( "icc" => "235" , "nsn" => "TD" ) ,
					"CL" => array( "icc" => "56" , "nsn" => "CL" ) ,
					"CN" => array( "icc" => "86" , "nsn" => "CN" ) ,
					"CX" => array( "icc" => "61" , "nsn" => "CX" ) ,
					"CC" => array( "icc" => "61" , "nsn" => "CC" ) ,
					"CO" => array( "icc" => "57" , "nsn" => "CO" ) ,
					"KM" => array( "icc" => "269" , "nsn" => "KM" ) ,
					"CK" => array( "icc" => "682" , "nsn" => "CK" ) ,
					"CR" => array( "icc" => "506" , "nsn" => "CR" ) ,
					"HR" => array( "icc" => "385" , "nsn" => "HR" ) ,
					"CU" => array( "icc" => "53" , "nsn" => "CU" ) ,
					"CW" => array( "icc" => "599" , "nsn" => "CW" ) ,
					"CY" => array( "icc" => "357" , "nsn" => "CY" ) ,
					"CZ" => array( "icc" => "420" , "nsn" => "CZ" ) ,
					"CD" => array( "icc" => "243" , "nsn" => "CD" ) ,
					"DK" => array( "icc" => "45" , "nsn" => "DK" ) ,
					"DJ" => array( "icc" => "253" , "nsn" => "DJ" ) ,
					"DM" => array( "icc" => "1-767" , "nsn" => "DM" ) ,
					"DO" => array( "icc" => "1-809" , "nsn" => "DO" ) ,
					"TL" => array( "icc" => "670" , "nsn" => "TL" ) ,
					"EC" => array( "icc" => "593" , "nsn" => "EC" ) ,
					"EG" => array( "icc" => "20" , "nsn" => "EG" ) ,
					"SV" => array( "icc" => "503" , "nsn" => "SV" ) ,
					"GQ" => array( "icc" => "240" , "nsn" => "GQ" ) ,
					"ER" => array( "icc" => "291" , "nsn" => "ER" ) ,
					"EE" => array( "icc" => "372" , "nsn" => "EE" ) ,
					"ET" => array( "icc" => "251" , "nsn" => "ET" ) ,
					"FK" => array( "icc" => "500" , "nsn" => "FK" ) ,
					"FO" => array( "icc" => "298" , "nsn" => "FO" ) ,
					"FJ" => array( "icc" => "679" , "nsn" => "FJ" ) ,
					"FI" => array( "icc" => "358" , "nsn" => "FI" ) ,
					"FR" => array( "icc" => "33" , "nsn" => "FR" ) ,
					"PF" => array( "icc" => "689" , "nsn" => "PF" ) ,
					"GA" => array( "icc" => "241" , "nsn" => "GA" ) ,
					"GM" => array( "icc" => "220" , "nsn" => "GM" ) ,
					"GE" => array( "icc" => "995" , "nsn" => "GE" ) ,
					"DE" => array( "icc" => "49" , "nsn" => "DE" ) ,
					"GH" => array( "icc" => "233" , "nsn" => "GH" ) ,
					"GI" => array( "icc" => "350" , "nsn" => "GI" ) ,
					"GR" => array( "icc" => "30" , "nsn" => "GR" ) ,
					"GL" => array( "icc" => "299" , "nsn" => "GL" ) ,
					"GD" => array( "icc" => "1-473" , "nsn" => "GD" ) ,
					"GU" => array( "icc" => "1-671" , "nsn" => "GU" ) ,
					"GT" => array( "icc" => "502" , "nsn" => "GT" ) ,
					"GG" => array( "icc" => "44-1481" , "nsn" => "GG" ) ,
					"GN" => array( "icc" => "224" , "nsn" => "GN" ) ,
					"GW" => array( "icc" => "245" , "nsn" => "GW" ) ,
					"GY" => array( "icc" => "592" , "nsn" => "GY" ) ,
					"HT" => array( "icc" => "509" , "nsn" => "HT" ) ,
					"HN" => array( "icc" => "504" , "nsn" => "HN" ) ,
					"HK" => array( "icc" => "852" , "nsn" => "HK" ) ,
					"HU" => array( "icc" => "36" , "nsn" => "HU" ) ,
					"IS" => array( "icc" => "354" , "nsn" => "IS" ) ,
					"IN" => array( "icc" => "91" , "nsn" => "IN" ) ,
					"ID" => array( "icc" => "62" , "nsn" => "ID" ) ,
					"IR" => array( "icc" => "98" , "nsn" => "IR" ) ,
					"IQ" => array( "icc" => "964" , "nsn" => "IQ" ) ,
					"IE" => array( "icc" => "353" , "nsn" => "IE" ) ,
					"IM" => array( "icc" => "44-1624" , "nsn" => "IM" ) ,
					"IL" => array( "icc" => "972" , "nsn" => "IL" ) ,
					"IT" => array( "icc" => "39" , "nsn" => "IT" ) ,
					"CI" => array( "icc" => "225" , "nsn" => "CI" ) ,
					"JM" => array( "icc" => "1-876" , "nsn" => "JM" ) ,
					"JP" => array( "icc" => "81" , "nsn" => "JP" ) ,
					"JE" => array( "icc" => "44-1534" , "nsn" => "JE" ) ,
					"JO" => array( "icc" => "962" , "nsn" => "JO" ) ,
					"KZ" => array( "icc" => "7" , "nsn" => "KZ" ) ,
					"KE" => array( "icc" => "254" , "nsn" => "KE" ) ,
					"KI" => array( "icc" => "686" , "nsn" => "KI" ) ,
					"XK" => array( "icc" => "383" , "nsn" => "XK" ) ,
					"KW" => array( "icc" => "965" , "nsn" => "KW" ) ,
					"KG" => array( "icc" => "996" , "nsn" => "KG" ) ,
					"LA" => array( "icc" => "856" , "nsn" => "LA" ) ,
					"LV" => array( "icc" => "371" , "nsn" => "LV" ) ,
					"LB" => array( "icc" => "961" , "nsn" => "LB" ) ,
					"LS" => array( "icc" => "266" , "nsn" => "LS" ) ,
					"LR" => array( "icc" => "231" , "nsn" => "LR" ) ,
					"LY" => array( "icc" => "218" , "nsn" => "LY" ) ,
					"LI" => array( "icc" => "423" , "nsn" => "LI" ) ,
					"LT" => array( "icc" => "370" , "nsn" => "LT" ) ,
					"LU" => array( "icc" => "352" , "nsn" => "LU" ) ,
					"MO" => array( "icc" => "853" , "nsn" => "MO" ) ,
					"MK" => array( "icc" => "389" , "nsn" => "MK" ) ,
					"MG" => array( "icc" => "261" , "nsn" => "MG" ) ,
					"MW" => array( "icc" => "265" , "nsn" => "MW" ) ,
					"MY" => array( "icc" => "60" , "nsn" => "MY" ) ,
					"MV" => array( "icc" => "960" , "nsn" => "MV" ) ,
					"ML" => array( "icc" => "223" , "nsn" => "ML" ) ,
					"MT" => array( "icc" => "356" , "nsn" => "MT" ) ,
					"MH" => array( "icc" => "692" , "nsn" => "MH" ) ,
					"MR" => array( "icc" => "222" , "nsn" => "MR" ) ,
					"MU" => array( "icc" => "230" , "nsn" => "MU" ) ,
					"YT" => array( "icc" => "262" , "nsn" => "YT" ) ,
					"MX" => array( "icc" => "52" , "nsn" => "MX" ) ,
					"FM" => array( "icc" => "691" , "nsn" => "FM" ) ,
					"MD" => array( "icc" => "373" , "nsn" => "MD" ) ,
					"MC" => array( "icc" => "377" , "nsn" => "MC" ) ,
					"MN" => array( "icc" => "976" , "nsn" => "MN" ) ,
					"ME" => array( "icc" => "382" , "nsn" => "ME" ) ,
					"MS" => array( "icc" => "1-664" , "nsn" => "MS" ) ,
					"MA" => array( "icc" => "212" , "nsn" => "MA" ) ,
					"MZ" => array( "icc" => "258" , "nsn" => "MZ" ) ,
					"MM" => array( "icc" => "95" , "nsn" => "MM" ) ,
					"NA" => array( "icc" => "264" , "nsn" => "NA" ) ,
					"NR" => array( "icc" => "674" , "nsn" => "NR" ) ,
					"NP" => array( "icc" => "977" , "nsn" => "NP" ) ,
					"NL" => array( "icc" => "31" , "nsn" => "NL" ) ,
					"AN" => array( "icc" => "599" , "nsn" => "AN" ) ,
					"NC" => array( "icc" => "687" , "nsn" => "NC" ) ,
					"NZ" => array( "icc" => "64" , "nsn" => "NZ" ) ,
					"NI" => array( "icc" => "505" , "nsn" => "NI" ) ,
					"NE" => array( "icc" => "227" , "nsn" => "NE" ) ,
					"NG" => array( "icc" => "234" , "nsn" => "NG" ) ,
					"NU" => array( "icc" => "683" , "nsn" => "NU" ) ,
					"KP" => array( "icc" => "850" , "nsn" => "KP" ) ,
					"MP" => array( "icc" => "1-670" , "nsn" => "MP" ) ,
					"NO" => array( "icc" => "47" , "nsn" => "NO" ) ,
					"OM" => array( "icc" => "968" , "nsn" => "OM" ) ,
					"PK" => array( "icc" => "92" , "nsn" => "PK" ) ,
					"PW" => array( "icc" => "680" , "nsn" => "PW" ) ,
					"PS" => array( "icc" => "970" , "nsn" => "PS" ) ,
					"PA" => array( "icc" => "507" , "nsn" => "PA" ) ,
					"PG" => array( "icc" => "675" , "nsn" => "PG" ) ,
					"PY" => array( "icc" => "595" , "nsn" => "PY" ) ,
					"PE" => array( "icc" => "51" , "nsn" => "PE" ) ,
					"PH" => array( "icc" => "63" , "nsn" => "PH" ) ,
					"PN" => array( "icc" => "64" , "nsn" => "PN" ) ,
					"PL" => array( "icc" => "48" , "nsn" => "PL" ) ,
					"PT" => array( "icc" => "351" , "nsn" => "PT" ) ,
					"PR" => array( "icc" => "1-787" , "nsn" => "PR" ) ,
					"QA" => array( "icc" => "974" , "nsn" => "QA" ) ,
					"CG" => array( "icc" => "242" , "nsn" => "CG" ) ,
					"RE" => array( "icc" => "262" , "nsn" => "RE" ) ,
					"RO" => array( "icc" => "40" , "nsn" => "RO" ) ,
					"RU" => array( "icc" => "7" , "nsn" => "RU" ) ,
					"RW" => array( "icc" => "250" , "nsn" => "RW" ) ,
					"BL" => array( "icc" => "590" , "nsn" => "BL" ) ,
					"SH" => array( "icc" => "290" , "nsn" => "SH" ) ,
					"KN" => array( "icc" => "1-869" , "nsn" => "KN" ) ,
					"LC" => array( "icc" => "1-758" , "nsn" => "LC" ) ,
					"MF" => array( "icc" => "590" , "nsn" => "MF" ) ,
					"PM" => array( "icc" => "508" , "nsn" => "PM" ) ,
					"VC" => array( "icc" => "1-784" , "nsn" => "VC" ) ,
					"WS" => array( "icc" => "685" , "nsn" => "WS" ) ,
					"SM" => array( "icc" => "378" , "nsn" => "SM" ) ,
					"ST" => array( "icc" => "239" , "nsn" => "ST" ) ,
					"SA" => array( "icc" => "966" , "nsn" => "SA" ) ,
					"SN" => array( "icc" => "221" , "nsn" => "SN" ) ,
					"RS" => array( "icc" => "381" , "nsn" => "RS" ) ,
					"SC" => array( "icc" => "248" , "nsn" => "SC" ) ,
					"SL" => array( "icc" => "232" , "nsn" => "SL" ) ,
					"SG" => array( "icc" => "65" , "nsn" => "SG" ) ,
					"SX" => array( "icc" => "1-721" , "nsn" => "SX" ) ,
					"SK" => array( "icc" => "421" , "nsn" => "SK" ) ,
					"SI" => array( "icc" => "386" , "nsn" => "SI" ) ,
					"SB" => array( "icc" => "677" , "nsn" => "SB" ) ,
					"SO" => array( "icc" => "252" , "nsn" => "SO" ) ,
					"ZA" => array( "icc" => "27" , "nsn" => "ZA" ) ,
					"KR" => array( "icc" => "82" , "nsn" => "KR" ) ,
					"SS" => array( "icc" => "211" , "nsn" => "SS" ) ,
					"ES" => array( "icc" => "34" , "nsn" => "ES" ) ,
					"LK" => array( "icc" => "94" , "nsn" => "LK" ) ,
					"SD" => array( "icc" => "249" , "nsn" => "SD" ) ,
					"SR" => array( "icc" => "597" , "nsn" => "SR" ) ,
					"SJ" => array( "icc" => "47" , "nsn" => "SJ" ) ,
					"SZ" => array( "icc" => "268" , "nsn" => "SZ" ) ,
					"SE" => array( "icc" => "46" , "nsn" => "SE" ) ,
					"CH" => array( "icc" => "41" , "nsn" => "CH" ) ,
					"SY" => array( "icc" => "963" , "nsn" => "SY" ) ,
					"TW" => array( "icc" => "886" , "nsn" => "TW" ) ,
					"TJ" => array( "icc" => "992" , "nsn" => "TJ" ) ,
					"TZ" => array( "icc" => "255" , "nsn" => "TZ" ) ,
					"TH" => array( "icc" => "66" , "nsn" => "TH" ) ,
					"TG" => array( "icc" => "228" , "nsn" => "TG" ) ,
					"TK" => array( "icc" => "690" , "nsn" => "TK" ) ,
					"TO" => array( "icc" => "676" , "nsn" => "TO" ) ,
					"TT" => array( "icc" => "1-868" , "nsn" => "TT" ) ,
					"TN" => array( "icc" => "216" , "nsn" => "TN" ) ,
					"TR" => array( "icc" => "90" , "nsn" => "TR" ) ,
					"TM" => array( "icc" => "993" , "nsn" => "TM" ) ,
					"TC" => array( "icc" => "1-649" , "nsn" => "TC" ) ,
					"TV" => array( "icc" => "688" , "nsn" => "TV" ) ,
					"VI" => array( "icc" => "1-340" , "nsn" => "VI" ) ,
					"UG" => array( "icc" => "256" , "nsn" => "UG" ) ,
					"UA" => array( "icc" => "380" , "nsn" => "UA" ) ,
					"AE" => array( "icc" => "971" , "nsn" => "AE" ) ,
					"GB" => array( "icc" => "44" , "nsn" => "GB" ) ,
					"US" => array( "icc" => "1" , "nsn" => "US" ) ,
					"UY" => array( "icc" => "598" , "nsn" => "UY" ) ,
					"UZ" => array( "icc" => "998" , "nsn" => "UZ" ) ,
					"VU" => array( "icc" => "678" , "nsn" => "VU" ) ,
					"VA" => array( "icc" => "379" , "nsn" => "VA" ) ,
					"VE" => array( "icc" => "58" , "nsn" => "VE" ) ,
					"VN" => array( "icc" => "84" , "nsn" => "VN" ) ,
					"WF" => array( "icc" => "681" , "nsn" => "WF" ) ,
					"EH" => array( "icc" => "212" , "nsn" => "EH" ) ,
					"YE" => array( "icc" => "967" , "nsn" => "YE" ) ,
					"ZM" => array( "icc" => "260" , "nsn" => "ZM" ) ,
					"ZW" => array( "icc" => "263" , "nsn" => "ZW" ) ,
				);

				return $country_codes[$country_code]??'';
			}

			public static function country_phone_prefix_list()
			{
				$phoneCodes = [
					'AF' => '93',
					'AL' => '355',
					'DZ' => '213',
					'AS' => '1-684',
					'AD' => '376',
					'AO' => '244',
					'AI' => '1-264',
					'AQ' => '672',
					'AG' => '1-268',
					'AR' => '54',
					'AM' => '374',
					'AW' => '297',
					'AU' => '61',
					'AT' => '43',
					'AZ' => '994',
					'BS' => '1-242',
					'BH' => '973',
					'BD' => '880',
					'BB' => '1-246',
					'BY' => '375',
					'BE' => '32',
					'BZ' => '501',
					'BJ' => '229',
					'BM' => '1-441',
					'BT' => '975',
					'BO' => '591',
					'BA' => '387',
					'BW' => '267',
					'BR' => '55',
					'IO' => '246',
					'VG' => '1-284',
					'BN' => '673',
					'BG' => '359',
					'BF' => '226',
					'BI' => '257',
					'KH' => '855',
					'CM' => '237',
					'CA' => '1',
					'CV' => '238',
					'KY' => '1-345',
					'CF' => '236',
					'TD' => '235',
					'CL' => '56',
					'CN' => '86',
					'CX' => '61',
					'CC' => '61',
					'CO' => '57',
					'KM' => '269',
					'CK' => '682',
					'CR' => '506',
					'HR' => '385',
					'CU' => '53',
					'CW' => '599',
					'CY' => '357',
					'CZ' => '420',
					'CD' => '243',
					'DK' => '45',
					'DJ' => '253',
					'DM' => '1-767',
					'DO' => '1-809',
					'TL' => '670',
					'EC' => '593',
					'EG' => '20',
					'SV' => '503',
					'GQ' => '240',
					'ER' => '291',
					'EE' => '372',
					'ET' => '251',
					'FK' => '500',
					'FO' => '298',
					'FJ' => '679',
					'FI' => '358',
					'FR' => '33',
					'PF' => '689',
					'GA' => '241',
					'GM' => '220',
					'GE' => '995',
					'DE' => '49',
					'GH' => '233',
					'GI' => '350',
					'GR' => '30',
					'GL' => '299',
					'GD' => '1-473',
					'GU' => '1-671',
					'GT' => '502',
					'GG' => '44-1481',
					'GN' => '224',
					'GW' => '245',
					'GY' => '592',
					'HT' => '509',
					'HN' => '504',
					'HK' => '852',
					'HU' => '36',
					'IS' => '354',
					'IN' => '91',
					'ID' => '62',
					'IR' => '98',
					'IQ' => '964',
					'IE' => '353',
					'IM' => '44-1624',
					'IL' => '972',
					'IT' => '39',
					'CI' => '225',
					'JM' => '1-876',
					'JP' => '81',
					'JE' => '44-1534',
					'JO' => '962',
					'KZ' => '7',
					'KE' => '254',
					'KI' => '686',
					'XK' => '383',
					'KW' => '965',
					'KG' => '996',
					'LA' => '856',
					'LV' => '371',
					'LB' => '961',
					'LS' => '266',
					'LR' => '231',
					'LY' => '218',
					'LI' => '423',
					'LT' => '370',
					'LU' => '352',
					'MO' => '853',
					'MK' => '389',
					'MG' => '261',
					'MW' => '265',
					'MY' => '60',
					'MV' => '960',
					'ML' => '223',
					'MT' => '356',
					'MH' => '692',
					'MR' => '222',
					'MU' => '230',
					'YT' => '262',
					'MX' => '52',
					'FM' => '691',
					'MD' => '373',
					'MC' => '377',
					'MN' => '976',
					'ME' => '382',
					'MS' => '1-664',
					'MA' => '212',
					'MZ' => '258',
					'MM' => '95',
					'NA' => '264',
					'NR' => '674',
					'NP' => '977',
					'NL' => '31',
					'AN' => '599',
					'NC' => '687',
					'NZ' => '64',
					'NI' => '505',
					'NE' => '227',
					'NG' => '234',
					'NU' => '683',
					'KP' => '850',
					'MP' => '1-670',
					'NO' => '47',
					'OM' => '968',
					'PK' => '92',
					'PW' => '680',
					'PS' => '970',
					'PA' => '507',
					'PG' => '675',
					'PY' => '595',
					'PE' => '51',
					'PH' => '63',
					'PN' => '64',
					'PL' => '48',
					'PT' => '351',
					'PR' => '1-787',
					'QA' => '974',
					'CG' => '242',
					'RE' => '262',
					'RO' => '40',
					'RU' => '7',
					'RW' => '250',
					'BL' => '590',
					'SH' => '290',
					'KN' => '1-869',
					'LC' => '1-758',
					'MF' => '590',
					'PM' => '508',
					'VC' => '1-784',
					'WS' => '685',
					'SM' => '378',
					'ST' => '239',
					'SA' => '966',
					'SN' => '221',
					'RS' => '381',
					'SC' => '248',
					'SL' => '232',
					'SG' => '65',
					'SX' => '1-721',
					'SK' => '421',
					'SI' => '386',
					'SB' => '677',
					'SO' => '252',
					'ZA' => '27',
					'KR' => '82',
					'SS' => '211',
					'ES' => '34',
					'LK' => '94',
					'SD' => '249',
					'SR' => '597',
					'SJ' => '47',
					'SZ' => '268',
					'SE' => '46',
					'CH' => '41',
					'SY' => '963',
					'TW' => '886',
					'TJ' => '992',
					'TZ' => '255',
					'TH' => '66',
					'TG' => '228',
					'TK' => '690',
					'TO' => '676',
					'TT' => '1-868',
					'TN' => '216',
					'TR' => '90',
					'TM' => '993',
					'TC' => '1-649',
					'TV' => '688',
					'VI' => '1-340',
					'UG' => '256',
					'UA' => '380',
					'AE' => '971',
					'GB' => '44',
					'US' => '1',
					'UY' => '598',
					'UZ' => '998',
					'VU' => '678',
					'VA' => '379',
					'VE' => '58',
					'VN' => '84',
					'WF' => '681',
					'EH' => '212',
					'YE' => '967',
					'ZM' => '260',
					'ZW' => '263'
				];

				return $phoneCodes;
			}

			/**
			 * Get countries by code
			 *
			 * @return string[]
			 */
			function wp_sms_get_countries()
			{
				$countries = [
					'+93'  => 'Afghanistan (افغانستان) (+93)',
					'+355' => 'Albania (Shqipëri) (+355)',
					'+213' => 'Algeria (الجزائر) (+213)',
					'+1'   => 'American Samoa (+1)',
					'+376' => 'Andorra (+376)',
					'+244' => 'Angola (+244)',
					'+1'   => 'Anguilla (+1)',
					'+1'   => 'Antigua and Barbuda (+1)',
					'+54'  => 'Argentina (+54)',
					'+374' => 'Armenia (Հայաստան) (+374)',
					'+297' => 'Aruba (+297)',
					'+247' => 'Ascension Island (+247)',
					'+61'  => 'Australia (+61)',
					'+43'  => 'Austria (Österreich) (+43)',
					'+994' => 'Azerbaijan (Azərbaycan) (+994)',
					'+1'   => 'Bahamas (+1)',
					'+973' => 'Bahrain (البحرين) (+973)',
					'+880' => 'Bangladesh (বাংলাদেশ) (+880)',
					'+1'   => 'Barbados (+1)',
					'+375' => 'Belarus (Беларусь) (+375)',
					'+32'  => 'Belgium (België) (+32)',
					'+501' => 'Belize (+501)',
					'+229' => 'Benin (Bénin) (+229)',
					'+1'   => 'Bermuda (+1)',
					'+975' => 'Bhutan (འབྲུག) (+975)',
					'+591' => 'Bolivia (+591)',
					'+387' => 'Bosnia and Herzegovina (Босна и Херцеговина) (+387)',
					'+267' => 'Botswana (+267)',
					'+55'  => 'Brazil (Brasil) (+55)',
					'+246' => 'British Indian Ocean Territory (+246)',
					'+1'   => 'British Virgin Islands (+1)',
					'+673' => 'Brunei (+673)',
					'+359' => 'Bulgaria (България) (+359)',
					'+226' => 'Burkina Faso (+226)',
					'+257' => 'Burundi (Uburundi) (+257)',
					'+855' => 'Cambodia (កម្ពុជា) (+855)',
					'+237' => 'Cameroon (Cameroun) (+237)',
					'+1'   => 'Canada (+1)',
					'+238' => 'Cape Verde (Kabu Verdi) (+238)',
					'+599' => 'Caribbean Netherlands (+599)',
					'+1'   => 'Cayman Islands (+1)',
					'+236' => 'Central African Republic (République centrafricaine) (+236)',
					'+235' => 'Chad (Tchad) (+235)',
					'+56'  => 'Chile (+56)',
					'+86'  => 'China (中国) (+86)',
					'+61'  => 'Christmas Island (+61)',
					'+61'  => 'Cocos (Keeling) Islands (+61)',
					'+57'  => 'Colombia (+57)',
					'+269' => 'Comoros (جزر القمر) (+269)',
					'+243' => 'Congo (DRC) (Jamhuri ya Kidemokrasia ya Kongo) (+243)',
					'+242' => 'Congo (Republic) (Congo-Brazzaville) (+242)',
					'+682' => 'Cook Islands (+682)',
					'+506' => 'Costa Rica (+506)',
					'+225' => 'Côte d’Ivoire (+225)',
					'+385' => 'Croatia (Hrvatska) (+385)',
					'+53'  => 'Cuba (+53)',
					'+599' => 'Curaçao (+599)',
					'+357' => 'Cyprus (Κύπρος) (+357)',
					'+420' => 'Czech Republic (Česká republika) (+420)',
					'+45'  => 'Denmark (Danmark) (+45)',
					'+253' => 'Djibouti (+253)',
					'+1'   => 'Dominica (+1)',
					'+1'   => 'Dominican Republic (República Dominicana) (+1)',
					'+593' => 'Ecuador (+593)',
					'+20'  => 'Egypt (مصر) (+20)',
					'+503' => 'El Salvador (+503)',
					'+240' => 'Equatorial Guinea (Guinea Ecuatorial) (+240)',
					'+291' => 'Eritrea (+291)',
					'+372' => 'Estonia (Eesti) (+372)',
					'+268' => 'Eswatini (+268)',
					'+251' => 'Ethiopia (+251)',
					'+500' => 'Falkland Islands (Islas Malvinas) (+500)',
					'+298' => 'Faroe Islands (Føroyar) (+298)',
					'+679' => 'Fiji (+679)',
					'+358' => 'Finland (Suomi) (+358)',
					'+33'  => 'France (+33)',
					'+594' => 'French Guiana (Guyane française) (+594)',
					'+689' => 'French Polynesia (Polynésie française) (+689)',
					'+241' => 'Gabon (+241)',
					'+220' => 'Gambia (+220)',
					'+995' => 'Georgia (საქართველო) (+995)',
					'+49'  => 'Germany (Deutschland) (+49)',
					'+233' => 'Ghana (Gaana) (+233)',
					'+350' => 'Gibraltar (+350)',
					'+30'  => 'Greece (Ελλάδα) (+30)',
					'+299' => 'Greenland (Kalaallit Nunaat) (+299)',
					'+1'   => 'Grenada (+1)',
					'+590' => 'Guadeloupe (+590)',
					'+1'   => 'Guam (+1)',
					'+502' => 'Guatemala (+502)',
					'+44'  => 'Guernsey (+44)',
					'+224' => 'Guinea (Guinée) (+224)',
					'+245' => 'Guinea-Bissau (Guiné Bissau) (+245)',
					'+592' => 'Guyana (+592)',
					'+509' => 'Haiti (+509)',
					'+504' => 'Honduras (+504)',
					'+852' => 'Hong Kong (香港) (+852)',
					'+36'  => 'Hungary (Magyarország) (+36)',
					'+354' => 'Iceland (Ísland) (+354)',
					'+91'  => 'India (भारत) (+91)',
					'+62'  => 'Indonesia (+62)',
					'+98'  => 'Iran (ایران) (+98)',
					'+964' => 'Iraq (العراق) (+964)',
					'+353' => 'Ireland (+353)',
					'+44'  => 'Isle of Man (+44)',
					'+972' => 'Israel (ישראל) (+972)',
					'+39'  => 'Italy (Italia) (+39)',
					'+1'   => 'Jamaica (+1)',
					'+81'  => 'Japan (日本) (+81)',
					'+44'  => 'Jersey (+44)',
					'+962' => 'Jordan (الأردن) (+962)',
					'+7'   => 'Kazakhstan (Казахстан) (+7)',
					'+254' => 'Kenya (+254)',
					'+686' => 'Kiribati (+686)',
					'+383' => 'Kosovo (+383)',
					'+965' => 'Kuwait (الكويت) (+965)',
					'+996' => 'Kyrgyzstan (Кыргызстан) (+996)',
					'+856' => 'Laos (ລາວ) (+856)',
					'+371' => 'Latvia (Latvija) (+371)',
					'+961' => 'Lebanon (لبنان) (+961)',
					'+266' => 'Lesotho (+266)',
					'+231' => 'Liberia (+231)',
					'+218' => 'Libya (ليبيا) (+218)',
					'+423' => 'Liechtenstein (+423)',
					'+370' => 'Lithuania (Lietuva) (+370)',
					'+352' => 'Luxembourg (+352)',
					'+853' => 'Macau (澳門) (+853)',
					'+389' => 'North Macedonia (Македонија) (+389)',
					'+261' => 'Madagascar (Madagasikara) (+261)',
					'+265' => 'Malawi (+265)',
					'+60'  => 'Malaysia (+60)',
					'+960' => 'Maldives (+960)',
					'+223' => 'Mali (+223)',
					'+356' => 'Malta (+356)',
					'+692' => 'Marshall Islands (+692)',
					'+596' => 'Martinique (+596)',
					'+222' => 'Mauritania (موريتانيا) (+222)',
					'+230' => 'Mauritius (Moris) (+230)',
					'+262' => 'Mayotte (+262)',
					'+52'  => 'Mexico (México) (+52)',
					'+691' => 'Micronesia (+691)',
					'+373' => 'Moldova (Republica Moldova) (+373)',
					'+377' => 'Monaco (+377)',
					'+976' => 'Mongolia (Монгол) (+976)',
					'+382' => 'Montenegro (Crna Gora) (+382)',
					'+1'   => 'Montserrat (+1)',
					'+212' => 'Morocco (المغرب) (+212)',
					'+258' => 'Mozambique (Moçambique) (+258)',
					'+95'  => 'Myanmar (Burma) (မြန်မာ) (+95)',
					'+264' => 'Namibia (Namibië) (+264)',
					'+674' => 'Nauru (+674)',
					'+977' => 'Nepal (नेपाल) (+977)',
					'+31'  => 'Netherlands (Nederland) (+31)',
					'+687' => 'New Caledonia (Nouvelle-Calédonie) (+687)',
					'+64'  => 'New Zealand (+64)',
					'+505' => 'Nicaragua (+505)',
					'+227' => 'Niger (Nijar) (+227)',
					'+234' => 'Nigeria (+234)',
					'+683' => 'Niue (+683)',
					'+672' => 'Norfolk Island (+672)',
					'+850' => 'North Korea (조선 민주주의 인민 공화국) (+850)',
					'+1'   => 'Northern Mariana Islands (+1)',
					'+47'  => 'Norway (Norge) (+47)',
					'+968' => 'Oman (عُمان) (+968)',
					'+92'  => 'Pakistan (پاکستان) (+92)',
					'+680' => 'Palau (+680)',
					'+970' => 'Palestine (فلسطين) (+970)',
					'+507' => 'Panama (Panamá) (+507)',
					'+675' => 'Papua New Guinea (+675)',
					'+595' => 'Paraguay (+595)',
					'+51'  => 'Peru (Perú) (+51)',
					'+63'  => 'Philippines (+63)',
					'+48'  => 'Poland (Polska) (+48)',
					'+351' => 'Portugal (+351)',
					'+1'   => 'Puerto Rico (+1)',
					'+974' => 'Qatar (قطر) (+974)',
					'+262' => 'Réunion (La Réunion) (+262)',
					'+40'  => 'Romania (România) (+40)',
					'+7'   => 'Russia (Россия) (+7)',
					'+250' => 'Rwanda (+250)',
					'+590' => 'Saint Barthélemy (+590)',
					'+290' => 'Saint Helena (+290)',
					'+1'   => 'Saint Kitts and Nevis (+1)',
					'+1'   => 'Saint Lucia (+1)',
					'+590' => 'Saint Martin (Saint-Martin (partie française)) (+590)',
					'+508' => 'Saint Pierre and Miquelon (Saint-Pierre-et-Miquelon) (+508)',
					'+1'   => 'Saint Vincent and the Grenadines (+1)',
					'+685' => 'Samoa (+685)',
					'+378' => 'San Marino (+378)',
					'+239' => 'São Tomé and Príncipe (São Tomé e Príncipe) (+239)',
					'+966' => 'Saudi Arabia (المملكة العربية السعودية) (+966)',
					'+221' => 'Senegal (Sénégal) (+221)',
					'+381' => 'Serbia (Србија) (+381)',
					'+248' => 'Seychelles (+248)',
					'+232' => 'Sierra Leone (+232)',
					'+65'  => 'Singapore (+65)',
					'+1'   => 'Sint Maarten (+1)',
					'+421' => 'Slovakia (Slovensko) (+421)',
					'+386' => 'Slovenia (Slovenija) (+386)',
					'+677' => 'Solomon Islands (+677)',
					'+252' => 'Somalia (Soomaaliya) (+252)',
					'+27'  => 'South Africa (+27)',
					'+82'  => 'South Korea (대한민국) (+82)',
					'+211' => 'South Sudan (جنوب السودان) (+211)',
					'+34'  => 'Spain (España) (+34)',
					'+94'  => 'Sri Lanka (ශ්‍රී ලංකාව) (+94)',
					'+249' => 'Sudan (السودان) (+249)',
					'+597' => 'Suriname (+597)',
					'+47'  => 'Svalbard and Jan Mayen (+47)',
					'+46'  => 'Sweden (Sverige) (+46)',
					'+41'  => 'Switzerland (Schweiz) (+41)',
					'+963' => 'Syria (سوريا) (+963)',
					'+886' => 'Taiwan (台灣) (+886)',
					'+992' => 'Tajikistan (+992)',
					'+255' => 'Tanzania (+255)',
					'+66'  => 'Thailand (ไทย) (+66)',
					'+670' => 'Timor-Leste (+670)',
					'+228' => 'Togo (+228)',
					'+690' => 'Tokelau (+690)',
					'+676' => 'Tonga (+676)',
					'+1'   => 'Trinidad and Tobago (+1)',
					'+216' => 'Tunisia (تونس) (+216)',
					'+90'  => 'Turkey (Türkiye) (+90)',
					'+993' => 'Turkmenistan (+993)',
					'+1'   => 'Turks and Caicos Islands (+1)',
					'+688' => 'Tuvalu (+688)',
					'+1'   => 'U.S. Virgin Islands (+1)',
					'+256' => 'Uganda (+256)',
					'+380' => 'Ukraine (Україна) (+380)',
					'+971' => 'United Arab Emirates (الإمارات العربية المتحدة) (+971)',
					'+44'  => 'United Kingdom (+44)',
					'+1'   => 'United States (+1)',
					'+598' => 'Uruguay (+598)',
					'+998' => 'Uzbekistan (Oʻzbekiston) (+998)',
					'+678' => 'Vanuatu (+678)',
					'+39'  => 'Vatican City (Città del Vaticano) (+39)',
					'+58'  => 'Venezuela (+58)',
					'+84'  => 'Vietnam (Việt Nam) (+84)',
					'+681' => 'Wallis and Futuna (Wallis-et-Futuna) (+681)',
					'+212' => 'Western Sahara (الصحراء الغربية) (+212)',
					'+967' => 'Yemen (اليمن) (+967)',
					'+260' => 'Zambia (+260)',
					'+263' => 'Zimbabwe (+263)',
					'+358' => 'Åland Islands (+358)',
				];

				return $countries;
			}

			public static function format_mobile_number($country_code,$mobile_number)
			{
				$nsn = '';
				$iso_country_mobile_details = self::get_iso_country_mobile_details($country_code);
				if(is_array($iso_country_mobile_details))
				{
					$country_code = substr($mobile_number, 0, 1) == '+' ? true : false;
					if($country_code)
					{
						// $nsn = str_replace("+".$iso_country_mobile_details['icc'],"",$mobile_number);
						// if(strlen($nsn) == $iso_country_mobile_details['nsn'])
						// {
						// 	  return $mobile_number;
						// }

						return $mobile_number;
					}
					else
					{
						
						$icc_pos = strpos($mobile_number, $iso_country_mobile_details['icc']);
					
						if($icc_pos !== false && $icc_pos == 0)
						{
							return '+'.$mobile_number;
						}
						else
						{
							return '+'.$iso_country_mobile_details['icc'].$mobile_number;
						}
					}
				}
				else
				{
					return $mobile_number;
				}
								
			}

			public static function get_item_post_type($item,$post_link_key)
			{
				$product_id = $item->get_product_id();
				$post = get_post( $product_id );
				$post_metas = get_post_meta($post->ID);
				if(is_array($post_metas) && array_key_exists($post_link_key,$post_metas))
				{
					$post_id = is_array($post_metas[$post_link_key]) ? $post_metas[$post_link_key][0]:'';
					if($post_id)
					{
						return get_post_type($post_id);
					}
					else
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}

			public static function check_wc_order_item_post_link_key($item_id,$post_link_key)
			{
				$item = new WC_Order_Item_Product($item_id);
				$product_id = $item->get_product_id();
				$post = get_post( $product_id );
				$post_metas = get_post_meta($post->ID);
				if(array_key_exists($post_link_key, $post_metas))
				{
					return true;
				}

				return false;
			}

			public static function get_array_from_array($search_key,$array)
			{
				if(is_array($array))
				{
					if(array_key_exists($search_key, $array))
					{
						return is_array($array[$search_key])?$array[$search_key]:array();
					}
				}
				
				return array();
			}

			public static function array_key_exist_like($array, $search_key)
			{
				if(is_array($array))
				{
					foreach($array as $key => $v)
					{
						if (strpos($key, $search_key) !== false)
						{
							return $key;
						}
					}
				}				

				return false;
			}

			public static function get_wp_post_by_meta_key_from_wc_order_itemm($item_id,$meta_key)
			{
				$item = new WC_Order_Item_Product($item_id);
				$product_id = $item->get_product_id();
				$post = get_post( $product_id );
				$post_metas = get_post_meta($post->ID);
				$parent_post_id = get_post_meta($post->ID,$meta_key,true);
				$parent_post = get_post( $parent_post_id );
				$parent_post_metas = get_post_meta($parent_post->ID);

				return $post_metas;
			}

			public static function array_key_checked($array,$key,$default = '')
			{
				if(is_array($array) && array_key_exists($key,$array))
				{
					if($array[$key] == 'on')
					{
						return 'checked';
					}
					else
					{
						return '';
					}
				}
				else
				{
					return $default;
				}
			}

		}

		new MP_SMS_Function();
	}