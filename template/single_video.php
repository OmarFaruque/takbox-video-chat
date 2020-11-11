<?php 
global $post;
$session_id = get_post_meta( $post->ID, 'tokbox_session_id', true );
$token = get_post_meta( $post->ID, 'tokbox_token', true );
?>

<?php get_header(); ?>
<div class="column twelvecol">
	<?php the_post(); ?>
	<article class="full-post">
		<?php if(has_post_thumbnail() && ThemexCore::getOption('post_image')!='true') { ?>
		<div class="bordered-image post-image">
			<?php the_post_thumbnail('extended', array('class' => 'fullwidth')); ?>
		</div>
		<?php } ?>
		<div class="section-title">
			<h1><?php the_title(); ?></h1>
		</div>
		<?php the_content(); ?>

		<?php
			function getTimeZoneFromIpAddress(){
				$clientsIpAddress = get_client_ip();
			
				$clientInformation = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$clientsIpAddress));
			
				$clientsLatitude = $clientInformation['geoplugin_latitude'];
				$clientsLongitude = $clientInformation['geoplugin_longitude'];
				$clientsCountryCode = $clientInformation['geoplugin_countryCode'];
			
				$timeZone = get_nearest_timezone($clientsLatitude, $clientsLongitude, $clientsCountryCode) ;
			
				return $timeZone;
			
			}
			
			function get_client_ip() {
				$ipaddress = '';
				if (getenv('HTTP_CLIENT_IP'))
					$ipaddress = getenv('HTTP_CLIENT_IP');
				else if(getenv('HTTP_X_FORWARDED_FOR'))
					$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
				else if(getenv('HTTP_X_FORWARDED'))
					$ipaddress = getenv('HTTP_X_FORWARDED');
				else if(getenv('HTTP_FORWARDED_FOR'))
					$ipaddress = getenv('HTTP_FORWARDED_FOR');
				else if(getenv('HTTP_FORWARDED'))
					$ipaddress = getenv('HTTP_FORWARDED');
				else if(getenv('REMOTE_ADDR'))
					$ipaddress = getenv('REMOTE_ADDR');
				else
					$ipaddress = 'UNKNOWN';
				return $ipaddress;
			}
			
			function get_nearest_timezone($cur_lat, $cur_long, $country_code = '') {
				$timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code)
					: DateTimeZone::listIdentifiers();
			
				if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {
			
					$time_zone = '';
					$tz_distance = 0;
			
					//only one identifier?
					if (count($timezone_ids) == 1) {
						$time_zone = $timezone_ids[0];
					} else {
			
						foreach($timezone_ids as $timezone_id) {
							$timezone = new DateTimeZone($timezone_id);
							$location = $timezone->getLocation();
							$tz_lat   = $location['latitude'];
							$tz_long  = $location['longitude'];
			
							$theta    = $cur_long - $tz_long;
							$distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
								+ (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
							$distance = acos($distance);
							$distance = abs(rad2deg($distance));
							// echo '<br />'.$timezone_id.' '.$distance;
			
							if (!$time_zone || $tz_distance > $distance) {
								$time_zone   = $timezone_id;
								$tz_distance = $distance;
							}
			
						}
					}
					return  $time_zone;
				}
				return 'unknown';
			}

			
            $current_page_id = $post->ID;
			$date_time = get_post_meta( $current_page_id, 'date_time', true );

			$time_zone = getTimeZoneFromIpAddress();
			$dt = new DateTime( "now", new DateTimeZone( $time_zone ) );
			$current_date = $dt->format('M d, Y H:i:s');

		if( $current_date <= $date_time ){
		?>
		<div class="count-cover"><p>Coming Soon : </p><div id="countDownDate"></div>
		<?php }else{ ?>
        <div 
        data-sessionid="<?php echo $session_id; ?>" 
        data-token="<?php echo $token; ?>" 
        class="single-session">
	
			<?php
				$current_user_id = get_current_user_id();
				$existingCredit_for_user = get_user_meta( $current_user_id, 'v_credit', true );
				if( $existingCredit_for_user > 0 ){
			?>
					<video id="video_test" class="video_test" autoplay></video>
					<button class="vjoinButton" type="submit"><?php _e('Join', 'wp_tokbox'); ?></button>
				<?php }else{
					echo 'Your credit is over, please recharge.';
				}
			}
			?>


        </div>
		<!-- <div id="publisher"></div>
		<div id="screen-preview"></div>
		<div id="people"></div>
		<div id="screens"></div> -->
		
		<div class="video-and-textchat-cover">

			<!-- Video wrap -->
			<div id="videos">
				<div class="publisher-cover">
					<div id="publisher">
						<div data-sessionid="<?php echo $session_id; ?>" data-token="<?php echo $token; ?>" class="single-session"></div>
					</div>
					<div data-sessionid="<?php echo $session_id; ?>" data-token="<?php echo $token; ?>" class="single-session">
						<div class="endButton"></div>
					</div>
				</div>
				<div class="suscriber-cover">
					<div id="subscriber"></div>
				</div>
			</div>
			<div class="video-full-view"></div>
			<?php   
				global $post;
				global $wp;
				$current_page_id = $post->ID;
				$select_contributor = get_post_meta( $current_page_id, 'select_contributor', true );
				if( $select_contributor =='' ){
					$post_author_id = get_post_field( 'post_author', $current_page_id );
				}else{
					$post_author_id = $select_contributor;
				}
				$author_obj = get_user_by( 'id', $post_author_id ); 
				$post_author_name = $author_obj->data->user_nicename;
				
				$current_user_id = get_current_user_id();
				
				$author_obj = get_user_by( 'id', $current_user_id ); 
				$current_user_name = $author_obj->data->user_nicename;

				$is_admin = ( $post_author_id == $current_user_id ) ? 'admin' : 'user';
			?>
			<div class="all-textchat-cover-wpr">
				<div class="all-textchat-cover">
					<div class="all-textchat">
						<!-- textchat wrap -->
						<div class="textchat-and-header <?php echo $is_admin . ' ' . $post_author_name; ?>" id="textchat-and-header"  style="display:none">
							<div id="textchat" data-sessionid="<?php echo $session_id; ?>" data-token="<?php echo $token; ?>">
								<div class="header-name-and-minimise-cover">
									<div class="chat-name-header"></div>
									<div class="chat-minimiser-cover">
										<svg class="chat-minimiser" height="26px" width="26px" viewBox="-4 -4 24 24"><line stroke="#bec2c9" stroke-linecap="round" stroke-width="2" x1="2" x2="14" y1="8" y2="8"></line></svg>
									</div>
								</div>
								<?php if( $post_author_id == $current_user_id ){ ?>
								<div class="admin-set-name">
									<form class="admin-set-name-submit">
										<label for="admin_set_name" class="admin-set-name-label">Set your name for this user :</label>
										<input type="text" placeholder="Set your name for this user" id="admin_set_name" class="admin-set-name" value="<?php echo $post_author_name; ?>">
										<div class="admin-set-user-name"></div>
										<button class="admin-set-name-button">
												<svg class="sqpo3gyd" height="20px" width="20px" viewBox="0 0 24 24"><path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.8429026 L21.714504,14.0454487 C22.6563168,13.5741566 23.1272231,12.6315722 22.9702544,11.6889879 C22.8132856,11.0605983 22.3423792,10.4322088 21.714504,10.118014 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4776575 C0.994623095,2.10604706 0.8376543,3.0486314 1.15159189,3.99121575 L3.03521743,10.4322088 C3.03521743,10.5893061 3.34915502,10.7464035 3.50612381,10.7464035 L16.6915026,11.5318905 C16.6915026,11.5318905 17.1624089,11.5318905 17.1624089,12.0031827 C17.1624089,12.4744748 16.6915026,12.4744748 16.6915026,12.4744748 Z" fill-rule="evenodd" stroke="none"></path></svg>
										</button>
									</form>
								</div>
								<div class="set-admin-name-show" style="display:none"><div class="set-admin-name-show-text"></div></div>
								<?php } ?>
								<div class="textchat-cover">
									<div id="history"></div>
									<form class="text-sender">
										<input type="text" placeholder="Aa" id="msgTxt"></input>
										<div class="massage_author"><?php echo $post_author_name; ?></div>
                                        <div class="author_and_user"><?php echo $post_author_name; ?></div>
                                        <div class="user_and_author"><?php echo $current_user_name; ?></div>
										<button class="text-chat-send">
											<svg class="sqpo3gyd" height="20px" width="20px" viewBox="0 0 24 24"><path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.8429026 L21.714504,14.0454487 C22.6563168,13.5741566 23.1272231,12.6315722 22.9702544,11.6889879 C22.8132856,11.0605983 22.3423792,10.4322088 21.714504,10.118014 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4776575 C0.994623095,2.10604706 0.8376543,3.0486314 1.15159189,3.99121575 L3.03521743,10.4322088 C3.03521743,10.5893061 3.34915502,10.7464035 3.50612381,10.7464035 L16.6915026,11.5318905 C16.6915026,11.5318905 17.1624089,11.5318905 17.1624089,12.0031827 C17.1624089,12.4744748 16.6915026,12.4744748 16.6915026,12.4744748 Z" fill-rule="evenodd" stroke="none"></path></svg>
										</button>
									</form>
								</div>
							</div>
							<div class="text-chat-head"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

        <!-- End Video Wrap -->
		<footer class="post-footer clearfix">
			<div class="column sixcol">
				<?php if(!ThemexCore::checkOption('post_date')) { ?>
				<span class="icon-calendar post-icon"></span><time class="post-date" datetime="<?php the_time('Y-m-d'); ?>"><?php the_time(get_option('date_format')); ?></time>
				<?php } ?>
				<?php if(!ThemexCore::checkOption('post_author')) { ?>
				<span class="icon-pencil post-icon"></span><span class="post-author"><?php the_author_posts_link(); ?></span>
				<?php } ?>
				<?php if(has_category()) { ?>
				<span class="icon-file-alt post-icon"></span><span class="post-category"><?php the_category(', '); ?></span>
				<?php } ?>
			</div>
			<div class="column sixcol last">
				<div class="tagcloud"><?php the_tags('','',''); ?></div>
			</div>
		</footer>
	</article>
	<?php comments_template(); ?>
</div>
<!-- <aside class="sidebar column fourcol last">
<?php //get_sidebar(); ?>
</aside> -->
<?php get_footer(); ?>