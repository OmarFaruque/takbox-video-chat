<?php
/*
* tokbox_video_chat Class 
*/


if (!class_exists('tokboxProfileUsers')) {

    class tokboxProfileUsers{

        public static $data;
        
        public function __construct() {
         
            $this->init();
        }
        public function init() {

            //refresh module data
            add_action('wp', array(__CLASS__, 'refresh'), 1);
        }

        /**
         * Refreshes module data
         *
         * @access public
         * @return void
         */
        public static function refresh() {
            $ID=get_current_user_id();
            self::$data['user']=self::getUser($ID, true);

            $user=0;
            if(($var=get_query_var('author')) || ($var=get_query_var('message')) || ($var=get_query_var('chat'))) {
                $user=intval($var);
            }

            if($user!=0) {
                self::$data['active_user']=self::getUser($user, true);
            } else {
                self::$data['active_user']=self::$data['user'];
            }
        }

        /**
         * Gets user data
         *
         * @access public
         * @param int $ID
         * @return array
         */
        public static function getUser($ID, $extended=false) {
            $data=get_userdata($ID);
            if($data!=false) {
                $user['login']=$data->user_login;
                $user['email']=$data->user_email;
                $user['role']=reset($data->roles);
            }

            $user['ID']=$ID;
            $user['status']=self::getStatus($ID);

            $user['profile_url']=get_author_posts_url($ID);
            $user['message_url']=ThemexCore::getUrl('message', $ID);
            $user['chat_url']=ThemexCore::getUrl('chat', $ID);
            $user['profile']=self::getProfile($ID);

            if($extended) {
                $user['memberships_url']=ThemexCore::getUrl('memberships', $ID);
                $user['settings_url']=ThemexCore::getUrl('settings', $ID);
                $user['messages_url']=ThemexCore::getUrl('messages', $ID);

                $user['favorites']=themex_keys(ThemexCore::getUserMeta($ID, 'favorites', array()));
                $user['ignores']=themex_keys(ThemexCore::getUserMeta($ID, 'ignores', array()));
                $user['photos']=themex_keys(ThemexCore::getUserMeta($ID, 'photos', array()));
                $user['gifts']=ThemexCore::getUserMeta($ID, 'gifts', array());
                $user['membership']=self::getMembership($ID, $user['profile']['gender']);
                $user['settings']=self::getSettings($ID);
            }

            return $user;
        }

        /**
        * Gets user status
        *
        * @access public
        * @param int $ID
        * @return array
        */
        public static function getStatus($ID) {
            $status['name']=__('Offline', 'lovestory');
            $status['value']='offline';

            if(isset($_SESSION['users'][$ID])) {
                $status['name']=__('Online', 'lovestory');
                $status['value']='online';
            }

            return $status;
        }

        /**
        * Gets user profile
        *
        * @access public
        * @param int $ID
        * @return array
        */
        public static function getProfile($ID) {
            global $wp_embed;

            $profile=array();
            $meta=get_user_meta($ID);

            $profile['name']=themex_array_value('first_name', $meta);
            $profile['nickname']=themex_array_value('nickname', $meta);

            if(ThemexCore::checkOption('user_name') || empty($profile['name'])) {
                $profile['first_name']=$profile['nickname'];
                $profile['last_name']='';
                $profile['full_name']=$profile['first_name'];
            } else {
                $profile['first_name']=$profile['name'];
                $profile['last_name']=themex_array_value('last_name', $meta);
                $profile['full_name']=trim($profile['first_name'].' '.$profile['last_name']);
            }

            if(!ThemexCore::checkOption('user_location')) {
                $profile['country']=themex_array_value('_'.THEMEX_PREFIX.'country', $meta);
                $profile['city']=themex_array_value('_'.THEMEX_PREFIX.'city', $meta);
            }

            $profile['description']=themex_array_value('description', $meta);
            $profile['gender']=themex_array_value('_'.THEMEX_PREFIX.'gender', $meta);
            $profile['seeking']=themex_array_value('_'.THEMEX_PREFIX.'seeking', $meta);
            $profile['age']=themex_array_value('_'.THEMEX_PREFIX.'age', $meta);

            if(isset(ThemexForm::$data['profile']) && is_array(ThemexForm::$data['profile'])) {
                foreach(ThemexForm::$data['profile'] as $field) {
                    $name=themex_sanitize_key($field['name']);
                    if(!isset($profile[$name])) {
                        $profile[$name]=themex_array_value('_'.THEMEX_PREFIX.$name, $meta);
                    }
                }
            }

            return $profile;
        }

        /**
        * Gets user membership
        *
        * @access public
        * @param int $ID
        * @param string $gender
        * @return array
        */
        public static function getMembership($ID, $gender) {
            global $wpdb;
            $filter=ThemexCore::getOption('user_membership');

            if(!is_user_logged_in() || $filter=='none' || ($filter=='woman' && $gender=='woman')) {
                $membership=array(
                    'ID' => -1,
                    'name' => __('Free', 'lovestory'),
                    'date' => current_time('timestamp')+60,
                    'messages' => 1,
                    'photos' => 1,
                    'gifts' => 1,
                    'chat' => 1,
                );
            } else {
                $membership=ThemexCore::getUserMeta($ID, 'membership');

                if(function_exists('wcs_get_users_subscriptions')) {
                    $subscriptions=wcs_get_users_subscriptions($ID);
                } else {
                    $subscriptions=get_user_meta($ID, $wpdb->prefix.'woocommerce_subscriptions', true);
                }

                if(!empty($membership)) {
                    if(is_array($subscriptions) && !empty($subscriptions)) {
                        $product=intval(ThemexCore::getPostMeta($membership['ID'], 'product'));

                        if($product!=0) {
                            foreach($subscriptions as $key=>$subscription) {
                                if(function_exists('wcs_get_users_subscriptions')) {
                                    $subscription_items=$subscription->get_items();
                                    $first=reset($subscription_items);

                                    if(is_array($first) && isset($first['product_id']) && $first['product_id']==$product) {
                                        $time=strtotime($subscription->end_date);

                                        if(empty($time)) {
                                            $time=strtotime($subscription->next_payment_date)+84600;
                                        }

                                        $membership['date']=$time;

                                        break;
                                    }
                                } else if($subscription['product_id']==$product) {
                                    $time=strtotime($subscription['expiry_date']);

                                    if($time!==false) {
                                        $membership['date']=$time;
                                    } else {
                                        $time=wp_next_scheduled('scheduled_subscription_payment', array(
                                            'user_id' => $ID,
                                            'subscription_key' => $key,
                                        ));

                                        if($time!=false) {
                                            $membership['date']=$time+84600;
                                        }
                                    }

                                    break;
                                }
                            }
                        }
                    }

                    if($membership['ID']!=0) {
                        $period=intval(ThemexCore::getPostMeta($membership['ID'], 'period'));

                        if(isset($membership['date']) && empty($period)) {
                            unset($membership['date']);
                        }
                    }
                }

                if(is_array($membership) && !isset($membership['chat'])) {
                    $membership['chat']=1;
                }
            }

            if(empty($membership) || (isset($membership['date']) && $membership['date']<current_time('timestamp'))) {
                $date=intval(ThemexCore::getOption('user_membership_date', 31))*86400+current_time('timestamp');
                $messages=intval(ThemexCore::getOption('user_membership_messages', 100));
                $photos=intval(ThemexCore::getOption('user_membership_photos', 10));
                $gifts=intval(ThemexCore::getOption('user_membership_gifts', 5));
                $chat=intval(ThemexCore::getOption('user_membership_chat', 1));

                if(isset($membership['date'])) {
                    $messages=0;
                    $photos=0;
                    $gifts=0;
                    $chat=0;
                }

                $membership=array(
                    'ID' => 0,
                    'name' => __('Free', 'lovestory'),
                    'date' => $date,
                    'messages' => $messages,
                    'photos' => $photos,
                    'gifts' => $gifts,
                    'chat' => $chat,
                );

                ThemexCore::updateUserMeta($ID, 'membership', $membership);
            }

            return $membership;
        }
   

        /**
         * Gets user settings
         *
         * @access public
         * @param int $ID
         * @return array
         */
        public static function getSettings($ID) {
            $settings=ThemexCore::getUserMeta($ID, 'settings', array(
                'favorites' => ThemexCore::getOption('user_settings_favorites', 1),
                'photos' => ThemexCore::getOption('user_settings_photos', 1),
                'gifts' => ThemexCore::getOption('user_settings_gifts', 1),
                'notices' => 1,
            ));

            return $settings;
        }

        public static function getUsers($args=array()){
            
        $role = ( !get_option( 'show_profile_role' ) ) ? 'contributor' : get_option( 'show_profile_role' ); 

		global $wpdb;
		$wpdb->query('SET SQL_BIG_SELECTS=1');

		$args['exclude']=self::$data['user']['ID'];
		$args['orderby']='registered';
		$args['order']='DESC';
        $args['role']= $role;

		$order=ThemexCore::getOption('user_order', 'date');
		if($order=='name') {
			$args['orderby']='display_name';
			$args['order']='ASC';
		}

		if(ThemexCore::checkOption('user_name')) {
			$args['meta_query']=array(
				array(
					'key' => '_'.THEMEX_PREFIX.'updated',
					'value' => '',
					'compare' => '!=',
				),
			);
		} else {
			$args['meta_query']=array(
				array(
					'key' => 'first_name',
					'value' => '',
					'compare' => '!=',
				),
			);
		}

		if(ThemexCore::checkOption('user_avatar')) {
			$args['meta_query']=array(
				array(
					'key' => '_'.THEMEX_PREFIX.'avatar',
					'value' => '',
					'compare' => '!=',
				),
			);
		}

		if(self::isUserFilter()) {
			if(isset($_GET['gender'])) {
				$args['meta_query'][]=array(
					'key' => '_'.THEMEX_PREFIX.'seeking',
					'value' => sanitize_title($_GET['gender']),
				);
			}

			if(isset($_GET['seeking'])) {
				$args['meta_query'][]=array(
					'key' => '_'.THEMEX_PREFIX.'gender',
					'value' => sanitize_title($_GET['seeking']),
				);
			}

			if(isset($_GET['country']) && !empty($_GET['country'])) {
				$args['meta_query'][]=array(
					'key' => '_'.THEMEX_PREFIX.'country',
					'value' => sanitize_title($_GET['country']),
				);
			}

			if(isset($_GET['city']) && !empty($_GET['city'])) {
				$args['meta_query'][]=array(
					'key' => '_'.THEMEX_PREFIX.'city',
					'value' => sanitize_text_field($_GET['city']),
				);
			}

			if(isset($_GET['age_from'])) {
				$args['meta_query'][]=array(
					'key' => '_'.THEMEX_PREFIX.'age',
					'type' => 'NUMERIC',
					'value' => intval($_GET['age_from']),
					'compare' => '>=',
				);
			}

			if(isset($_GET['age_to'])) {
				$args['meta_query'][]=array(
					'key' => '_'.THEMEX_PREFIX.'age',
					'type' => 'NUMERIC',
					'value' => intval($_GET['age_to']),
					'compare' => '<=',
				);
			}

			if(isset(ThemexForm::$data['profile']) && is_array(ThemexForm::$data['profile'])) {
				foreach(ThemexForm::$data['profile'] as $field) {
					if(isset($field['search'])) {
						$name=themex_sanitize_key($field['name']);
						if(isset($_GET[$name]) && !empty($_GET[$name])) {
							if(in_array($field['type'], array('text', 'textarea'))) {
								$args['meta_query'][]=array(
									'key' => '_'.THEMEX_PREFIX.$name,
									'value' => sanitize_text_field($_GET[$name]),
									'compare' => 'LIKE',
								);
							} else {
								$args['meta_query'][]=array(
									'key' => '_'.THEMEX_PREFIX.$name,
									'value' => sanitize_text_field($_GET[$name]),
								);
							}
						}
					}
				}
			}
		}

		if($order=='status' && isset($_SESSION['users']) && !empty($_SESSION['users'])) {
			$online=$_SESSION['users'];
			if(isset($online[self::$data['user']['ID']])) {
				unset($online[self::$data['user']['ID']]);
			}

			$online=array_keys($online);
			if(!empty($online) && isset($args['number']) && isset($args['offset'])) {
				$number=$args['number'];
				$args['number']=null;

				$offset=$args['offset'];
				$args['offset']=null;

				$args['exclude']=array_merge(array(self::$data['user']['ID']), $online);
				$users=get_users($args);

				$args['include']=$online;
				$users=array_slice(array_merge(get_users($args), $users), $offset, $number);
			} else {
				$users=get_users($args);
			}
		} else {
			$users=get_users($args);
		}

		return $users;
        }


        /**
         * Checks user filter
         *
         * @access public
         * @return bool
         */
        public static function isUserFilter() {
            if(isset($_GET['s']) && empty($_GET['s'])) {
                return true;
            }

            return false;
        }



    }

}