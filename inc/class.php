<?php
/*
* tokbox_video_chat Class 
*/
use OpenTok\OpenTok;
use OpenTok\MediaMode;
use OpenTok\ArchiveMode;
use OpenTok\Session;
use OpenTok\Role;
// use OpenTok\RoleConstants;

if (!class_exists('tokbox_video_chatClass')) {
    class tokbox_video_chatClass{
        public $plugin_url;
        public $plugin_dir;
        public $wpdb;
        public $option_tbl; 
        
        /**Plugin init action**/ 
        public function __construct() {
            global $wpdb;
            $this->plugin_url 				= tokbox_video_chatURL;
            $this->plugin_dir 				= tokbox_video_chatDIR;
            $this->wpdb 					= $wpdb;	
            $this->option_tbl               = $this->wpdb->prefix . 'options';
         
            $this->init();
        }

        private function init(){

            //Backend Script
            add_action( 'admin_enqueue_scripts', array($this, 'larasoftNote_backend_script') );
            
            add_action( 'init', array($this, 'cr_session_post_type') );

            //Add Menu Options
            add_action('admin_menu', array($this, 'tokbox_video_chat_admin_menu_function'));
            
            // add_action( 'wp_head', array($this, 'test') );
            add_action( 'save_post', array($this, 'sessionSavePostCallback'));
            add_shortcode( 'v-sessions', array($this, 'sessionFrontEndCallback') );
            add_action( 'wp_enqueue_scripts', array($this, 'sessionVEnqueueScriptCallback') );

            // Add Credit field to backend profile page
            add_action( 'show_user_profile', array($this, 'extra_profile_fields'), 10 );
            add_action( 'edit_user_profile', array($this, 'extra_profile_fields'), 10 );
            
            // Save Credit
            add_action( 'personal_options_update', array($this, 'save_extra_profile_fields') );
            add_action( 'edit_user_profile_update', array($this, 'save_extra_profile_fields') );

            // REdirect single-video templte from plugin
            add_filter('single_template', array($this, 'redirectTEmplateForVideo'));

            // Add metaboxs 
            add_action( 'add_meta_boxes', array($this, 'opentok_register_meta_boxes') );

            // Reduce credit from user meta
            add_action('wp_ajax_reduseUserCreditWhileSessionOn', array($this, 'reduseUserCreditWhileSessionOn'));
            add_action('wp_ajax_nopriv_reduseUserCreditWhileSessionOn',	array($this, 'reduseUserCreditWhileSessionOn'));

            // Save archive video link
            add_action('wp_ajax_saveArchiveVideoLink', array($this, 'saveArchiveVideoLink'));
            add_action('wp_ajax_nopriv_saveArchiveVideoLink', array($this, 'saveArchiveVideoLink'));

            // Save text chat
            add_action('wp_ajax_saveTextChat', array($this, 'saveTextChat'));
            add_action('wp_ajax_nopriv_saveTextChat', array($this, 'saveTextChat'));

            // user details and session id
            add_action('wp_ajax_all_user_details', array($this, 'all_user_details'));
            add_action('wp_ajax_nopriv_all_user_details', array($this, 'all_user_details'));

            add_action('wp_ajax_nopriv_tokbox_video_chatsettingssaveajax', array($this, 'tokbox_video_chatsettingssaveajax') );
            add_action( 'wp_ajax_tokbox_video_chatsettingssaveajax', array($this, 'tokbox_video_chatsettingssaveajax') );
            
            // add_action('wp_head', array($this, 'testFunction'));
            add_action('wp_footer', array($this, 'text_chat_for_user') );

            add_action('admin_init', array($this, 'create_session_for_chat'));

            
            add_filter ('page_template',  array($this,'wpse255804_redirect_page_template') );
            add_filter ('theme_page_templates',  array($this,'wpse255804_add_page_template') );


        }


        function wpse255804_add_page_template ($templates) {
            $templates['template-profiles.php'] = 'template-profiles.php';
            return $templates;
        }
        function wpse255804_redirect_page_template ($template) {
            if ('template-profiles.php' == basename ($template))
                $template =  $this->plugin_dir . '/template/template-profiles.php';
            return $template;
        }

        function testFunction(){

            echo 'login : ' . is_user_logged_in();
        //    echo 'session id : ' . get_option( 'tokbox_session_id' );
        //     echo '</br> token : ' . get_option( 'tokbox_token' );

        }

        function create_session_for_chat(){

            if(date('Y-m-d') !== get_option('tokbox_token_date')){
                delete_option( 'tokbox_session_id' );
            }
            
            if( get_option( 'tokbox_session_id' ) == '' ){
                $sessionID = $this->sessionId();
                $token = $this->openTalkToken($sessionID);
                update_option( 'tokbox_token_date', date('Y-m-d') );
                update_option( 'tokbox_session_id', $sessionID );
                update_option( 'tokbox_token', $token );
            }
        }

        function text_chat_for_user(){
            if(!is_user_logged_in()){
                return false;
            }

            
            $current_user_id = get_current_user_id();
            $author_role = ( !get_option('show_profile_role') ) ? 'contributor' : get_option('show_profile_role');
            $user_meta = get_userdata( $current_user_id );
            $user_roles = $user_meta->roles;
            $current_user_role = ( in_array( $author_role, $user_roles ) ) ? 'yes' : 'no';

            if( $current_user_role == 'no' ){
                if (basename( get_page_template() ) != 'template-profiles.php'){
                    return false;
                }
            }

            global $post;
            global $wp;

            $session_id = get_option( 'tokbox_session_id' );
            $token = get_option( 'tokbox_token' );

            $current_user_id = get_current_user_id();
            $author_obj = get_user_by( 'id', $current_user_id ); 
            $current_user_name = $author_obj->data->user_nicename;

            $author_role = ( !get_option('show_profile_role') ) ? 'contributor' : get_option('show_profile_role');
            
            $user_meta = get_userdata( $current_user_id );
            $user_roles = $user_meta->roles;

            // echo '<pre>';
            // print_r($user_roles);
            // echo '</pre>';

            // if(in_array( $author_role, $user_roles )){
            //     echo "Match found";
            // }
            // else{
            //     echo "Match not found";
            // }
            $is_admin = ( in_array( $author_role, $user_roles ) ) ? 'admin' : 'user';
        ?>

        <div class="video-and-textchat-cover-for-user">
            <div class="all-textchat-cover-wpr">
				<div class="all-textchat-cover">
					<div class="all-textchat">
						<!-- textchat wrap -->
						<div class="textchat-and-header <?php echo $is_admin . ' ' . $current_user_name;?> demo-chat" id="textchat-and-header">
							<div id="textchat" data-sessionid="<?php echo $session_id; ?>" data-token="<?php echo $token; ?>">
								<div class="header-name-and-minimise-cover">
									<div class="chat-name-header"></div>
									<div class="chat-minimiser-cover">
										<svg class="chat-minimiser" height="26px" width="26px" viewBox="-4 -4 24 24"><line stroke="#bec2c9" stroke-linecap="round" stroke-width="2" x1="2" x2="14" y1="8" y2="8"></line></svg>
									</div>
								</div>

								<div class="textchat-cover">
									<div id="history"></div>
									<form class="text-sender">
										<input type="text" placeholder="Aa" id="msgTxt"></input>
										<div class="massage_author"></div>
                                        <div class="author_and_user"></div>
                                        <div class="user_and_author"></div>
                                        <div class="massage_unique_id"></div>
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
        <?php
        }

        // public function testFunction(){
        //     $opentok = $this->opentok();
        //     $archiveList = $opentok->listArchives(0, 100);

        //     $sesson_id = '1_MX40NjkzMjEyNH5-MTYwMjI0NTM4Njc1NX5Vc0pqT2dNRlp5N0VXaXFNNndOVEVORXl-QX4';

        //     $all_url = array();
        //     foreach($archiveList->getItems() as $s){

        //         // if($sesson_id == $s->sessionId){
        //             // echo $s->sessionId;
        //             // echo '</br>';
        //             // echo $single_url = $s->url;
        //             // echo '</br>';
        //             // array_push( $all_url, $single_url );
        //         // }

        //         // echo '<pre>';
        //         // print_r($s);
        //         // echo '</pre>';


        //     }
        //     // $current_page_id = '53337';
        //     // $session_all_archive_videos_url = get_post_meta( $current_page_id, 'session_all_archive_videos_url', true );
            

        //     // echo '<pre>';
        //     // print_r($session_all_archive_videos_url);
        //     // echo '</pre>';

        // }

        public function saveArchiveVideoLink(){
            $sessionId = $_POST['sessionId'];
            $current_page_id = $_POST['current_page_id'];

            delete_post_meta( $current_page_id, 'session_all_archive_videos_url' );

            echo json_encode(
                array(
                    'message' => 'success',
                    'carrent_page_id' => $current_page_id
                )
            );
            die();
        }

        public function saveTextChat(){
            $msgText = $_POST['msgText'];
            $user_name = $_POST['user_name'];
            $massage_author = $_POST['massage_author'];
            $current_page_id = $_POST['current_page_id'];
            $chat_page = $_POST['chat_page'];
            $author_and_user = $_POST['author_and_user'];
            $user_and_author = $_POST['user_and_author'];
            $seen = $_POST['seen'];
            

            if( $chat_page == 'no' ){

                //video and text chat
                $post_id = $_POST['post_id'];
                

                $tokbox_text_chat_nw = array(
                    "msgText" => $msgText,
                    "seen" => $seen,
                    "user_name" => $user_name,
                    "massage_author" => $massage_author,
                    'author_and_user' => $author_and_user,
                    'user_and_author' => $user_and_author

                );

                if( get_post_meta( $post_id, 'tokbox_video_and_text_chat_' . $author_and_user . '_' . $user_and_author, true ) == '' )
                {
                    $tokbox_text_chat = array();
                }else{
                    $tokbox_text_chat = get_post_meta( $post_id, 'tokbox_video_and_text_chat_' . $author_and_user . '_' . $user_and_author, true );
                }
                
                array_push( $tokbox_text_chat, $tokbox_text_chat_nw );
                update_post_meta( $post_id, 'tokbox_video_and_text_chat_' . $author_and_user . '_' . $user_and_author, $tokbox_text_chat );
                
            }else{

                
                $name_set = $_POST['name_set'];

                if( $name_set == 'yes' ){
                    //text chat name set

                    $admin_set_name = $_POST['admin_set_name'];

                    $admin_details = get_user_by('login',$author_and_user);
                    if($admin_details){
                        $author_and_user_id = $admin_details->ID;
                    }

                    $user_details = get_user_by('login',$user_and_author);
                    if($user_details){
                        $user_and_author_id = $user_details->ID;
                    }

                    $user_max_id = max( $author_and_user_id, $user_and_author_id);
                    $user_min_id = min( $author_and_user_id, $user_and_author_id);

                    update_user_meta( $user_max_id, 'tokbox_text_chat_set_name_' . $user_max_id . '_' . $user_min_id, $admin_set_name );

                }else{

                    //text chat save.
                    $tokbox_text_chat_nw = array(
                        "msgText" => $msgText,
                        "seen" => $seen,
                        "user_name" => $user_name,
                        "massage_author" => $massage_author,
                        'author_and_user' => $author_and_user,
                        'user_and_author' => $user_and_author
                    );

                    $admin_details = get_user_by('login',$author_and_user);
                    if($admin_details){
                        $author_and_user_id = $admin_details->ID;
                    }

                    $user_details = get_user_by('login',$user_and_author);
                    if($user_details){
                        $user_and_author_id = $user_details->ID;
                    }

                    $user_max_id = max( $author_and_user_id, $user_and_author_id);
                    $user_min_id = min( $author_and_user_id, $user_and_author_id);


                    if( get_user_meta( $user_max_id, 'tokbox_text_chat_' . $user_max_id . '_' . $user_min_id, true ) == '' )
                    {
                        $tokbox_text_chat = array();
                    }else{
                        $tokbox_text_chat = get_user_meta( $user_max_id, 'tokbox_text_chat_' . $user_max_id . '_' . $user_min_id, true );
                    }
                    
                    array_push( $tokbox_text_chat, $tokbox_text_chat_nw );
                    update_user_meta( $user_max_id, 'tokbox_text_chat_' . $user_max_id . '_' . $user_min_id, $tokbox_text_chat );
                }
            }

            echo json_encode(
                array(
                    'message' => 'success',
                    'tokbox_text_chat' => $tokbox_text_chat,
                    'jony' => $tokbox_text_chat_nw,
                    'current_page_id' => $current_page_id,
                    '53378' => $post_id
                )
            );
            die();
        }

        public function all_user_details(){

            
            $sendTOappend = $_POST['sendTOappend'];
            $userId = '';
            $user_name = '';
            $admin_set_name = '';
            $append_sms = $_POST['append_sms'];
            $tokbox_text_chat = '';

            if( $append_sms == 'yes' ){
                // admin set name chaek

                

                $author_and_user = $_POST['author_and_user'];
                $user_and_author = $_POST['user_and_author'];
                $massage_unique_id = $_POST['massage_unique_id'];

                $admin_details = get_user_by('login',$author_and_user);
                if($admin_details){
                    $author_and_user_id = $admin_details->ID;
                }

                $user_details = get_user_by('login',$user_and_author);
                if($user_details){
                    $user_and_author_id = $user_details->ID;
                }

                $user_max_id = max( $author_and_user_id, $user_and_author_id);
                $user_min_id = min( $author_and_user_id, $user_and_author_id);


                if( get_user_meta( $user_max_id, 'tokbox_text_chat_set_name_' . $user_max_id . '_' . $user_min_id , true ) == '' )
                {
                    $admin_set_name = '';
                }else{
                    $admin_set_name = get_user_meta( $user_max_id, 'tokbox_text_chat_set_name_' . $user_max_id . '_' . $user_min_id , true );
                }

            }else{

                if( $sendTOappend == 'yes' ){

                    //text chat click to open chat

                    $userId = $_POST['userId'];
                    
                    $author_obj = get_user_by( 'id', $userId ); 
                    $user_name = $author_obj->data->user_nicename;

                    $current_user_id = get_current_user_id();

                   
                    $author_and_user_id = $userId;
                    $user_and_author_id = $current_user_id;
                    

                    $user_max_id = max( $author_and_user_id, $user_and_author_id);
                    $user_min_id = min( $author_and_user_id, $user_and_author_id);

                    
                    if( get_user_meta( $user_max_id, 'tokbox_text_chat_' . $user_max_id . '_' . $user_min_id, true ) == '' )
                    {
                        $tokbox_text_chat = array();
                    }else{
                        $tokbox_text_chat = get_user_meta( $user_max_id, 'tokbox_text_chat_' . $user_max_id . '_' . $user_min_id, true );
                    }


                    if( get_user_meta( $user_max_id, 'tokbox_text_chat_set_name_' . $user_max_id . '_' . $user_min_id, true ) == '' )
                    {
                        $admin_set_name = '';
                    }else{
                        $admin_set_name = get_user_meta( $user_max_id, 'tokbox_text_chat_set_name_' . $user_max_id . '_' . $user_min_id, true );
                    }

                }else{

                    //text chat admin to open chat

                    $author_and_user = $_POST['author_and_user'];
                    $user_and_author = $_POST['user_and_author'];

                    $admin_details = get_user_by('login',$author_and_user);
                    if($admin_details){
                        $author_and_user_id = $admin_details->ID;
                    }

                    $user_details = get_user_by('login',$user_and_author);
                    if($user_details){
                        $user_and_author_id = $user_details->ID;
                    }

                    $user_max_id = max( $author_and_user_id, $user_and_author_id);
                    $user_min_id = min( $author_and_user_id, $user_and_author_id);


                    if( get_user_meta( $user_max_id, 'tokbox_text_chat_' . $user_max_id . '_' . $user_min_id, true ) == '' )
                    {
                        $tokbox_text_chat = array();
                    }else{
                        $tokbox_text_chat = get_user_meta( $user_max_id, 'tokbox_text_chat_' . $user_max_id . '_' . $user_min_id, true );
                        $newchat = array();
                        if(isset($_POST['seen'])){
                            foreach($tokbox_text_chat as $single){
                                $single['seen'] = true;
                                array_push($newchat, $single);
                            }
                            update_user_meta( $user_max_id, 'tokbox_text_chat_' . $user_max_id . '_' . $user_min_id, $newchat );
                        }

                    }

                }
            }

            echo json_encode(
                array(
                    'message' => 'success',
                    'userId' => $userId,
                    'newChat' => $newchat,
                    'user_name' => $user_name,
                    'tokbox_text_chat' => $tokbox_text_chat,
                    'admin_set_name' => $admin_set_name,
                    'author_and_user' => $author_and_user,
                    'massage_unique_id' => $massage_unique_id,
                )
            );
            die();
        }

        public function reduseUserCreditWhileSessionOn(){
            // Reduce user Credits
            $userid = esc_attr( $_POST['userid'] );
            $existingCredit = get_user_meta( $userid, 'v_credit', true );
            $newCredit = (double)$existingCredit - 0.5;
            $forcedisconnect = '';
            if( $newCredit > 0){
                update_user_meta( $userid, 'v_credit', $newCredit );
            }else{
                $forcedisconnect = 'yes';
                update_user_meta( $userid, 'v_credit', $newCredit );
            }

        

            // Add Author Credits
            $current_page_id = esc_attr( $_POST['current_page_id'] );

            
            $select_contributor = get_post_meta( $current_page_id, 'select_contributor', true );
            if( $select_contributor =='' ){
                $post_author_id = get_post_field( 'post_author', $current_page_id );
            }else{
                $post_author_id = $select_contributor;
            }

            $existingCredit_for_author = get_user_meta( $post_author_id, 'v_credit', true );
            $newCredit_for_author = (double)$existingCredit_for_author + 0.5; 
            update_user_meta( $post_author_id, 'v_credit', $newCredit_for_author );

            // Add All Credits By This Session
            if( $post_author_id != $userid ){
                $existingCredit_par_video = get_post_meta( $current_page_id, 'session_all_v_credit', true );
                $newCredit_par_video = (double)$existingCredit_par_video + 0.5; 
                update_post_meta( $current_page_id, 'session_all_v_credit', $newCredit_par_video );


                if( get_post_meta( $current_page_id, 'tokbox_all_user', true ) == '' )
                {
                    $user_name = array();
                }else{
                    $user_name = get_post_meta( $current_page_id, 'tokbox_all_user', true );
                }
				$author_obj = get_user_by( 'id', $userid ); 
                $single_user_name = $author_obj->data->user_nicename;
                if ( !in_array( $single_user_name, $user_name ) ){  
                    array_push( $user_name, $single_user_name );
                    update_post_meta( $current_page_id, 'tokbox_all_user', $user_name );
                }

            }

            wp_send_json( array(
                'msg' => 'success',
                'forcedisconnect' => $forcedisconnect,
                'userid' => $userid,
                'existingCredit' => $existingCredit,
                'newCredit' => $newCredit,
                'post_author_id' => $post_author_id,
                'existingCredit_for_author' => $existingCredit_for_author,
                'newCredit_for_author' => $newCredit_for_author,

            ) );

        }


        public function opentok_register_meta_boxes(){
            /*
            * Register metabox for session post type
            */
            add_meta_box( 'opentalk_metabox', __( 'Setting\'s', 'opentalk' ), array($this, 'opentalk_metaboxCallback'), 'v_session' );

            add_meta_box( 'opentalk_text_history_metabox', __( 'Text Chat History', 'opentalk' ), array($this, 'opentalk_text_history_metaboxCallback'), 'v_session' );

            add_meta_box( 'opentalk_video_history_metabox', __( 'Video Chat History', 'opentalk' ), array($this, 'opentalk_video_history_metaboxCallback'), 'v_session' );

            add_meta_box( 'opentalk_total_earning_metabox', __( 'Earning History', 'opentalk' ), array($this, 'opentalk_total_earning_metaboxCallback'), 'v_session', 'side' );
        }


        public function opentalk_metaboxCallback($post){
            ob_start();
            $date_time = get_post_meta( $post->ID, 'date_time', true );
            $duration = get_post_meta( $post->ID, 'duration', true );
            $select_contributor = get_post_meta( $post->ID, 'select_contributor', true );

            // echo 'tokbox_token : ' . get_post_meta( $post->ID, 'tokbox_token', true );
            ?>
                <div id="settingsSection">
                    <div class="settings-inner">
                        <div class="form-group">
                            <label for="date_time"><?php _e('Date & time', 'opentalk'); ?></label>
                            <input class="datepicker form-control w-100 mt-3px" value="<?php echo $date_time; ?>" type="text" name="date_time" id="date_time">
                        </div>

                        <div class="form-group mt-2">
                            <label for="duration"><?php _e('Duration', 'opentalk'); ?></label>
                            <input class="form-control w-100 mt-3px" value="<?php echo $duration; ?>" type="number" name="duration" id="duration">
                            <small class="description"><?php _e('Set Duration as Minute\'s', 'opentalk'); ?></small>
                        </div>
                        <?php 
                        $user = wp_get_current_user();
                        $roles = ( array ) $user->roles;
                        if( in_array("administrator", $roles) ){
                        ?>
                        <div class="form-group mt-2">
                            <label for="select_contributor"><?php _e('Select Contributor', 'opentalk'); ?></label>
                            <select name="select_contributor" class="select_contributor  w-100 mt-3px" id="select_contributor">
                            <option value="" >Select Contributor</option>';
                            <?php
                                $users = get_users( array( 'fields' => array( 'ID' ) ) );
                                foreach($users as $user){
                                    $user_id = $user->ID;
                                    $user_meta = get_userdata( $user_id );
                                    $user_roles = $user_meta->roles;
                                    if( in_array("contributor", $user_roles) ){
                                        $selected = ( $select_contributor  == $user_id ) ? 'selected' : '';
                                        echo '<option ' . $selected . ' value="' . $user_id . '">' . $user_meta->user_login . '</option>';
                                    }
                                }
                            ?>
                            </select>
                       </div>

                       <?php 
                       }
                       ?>

                    </div>
                </div>
            <?php 
            $output = ob_get_clean();
            echo $output;
        }

        public function opentalk_text_history_metaboxCallback($post){
            ob_start();

            $current_page_id = $post->ID;
            $select_contributor = get_post_meta( $current_page_id, 'select_contributor', true );
            if( $select_contributor =='' ){
                $post_author_id = get_post_field( 'post_author', $current_page_id );
            }else{
                $post_author_id = $select_contributor;
            }
            $author_obj = get_user_by( 'id', $post_author_id ); 
            $author_and_user = $author_obj->data->user_nicename;

// 
            $tokbox_all_user = get_post_meta( $post->ID, 'tokbox_all_user', true );

            foreach( $tokbox_all_user as $user_and_author ){

                $tokbox_text_chat = get_post_meta( $post->ID, 'tokbox_video_and_text_chat_' . $author_and_user . '_' . $user_and_author, true );
            }
            ?>
                <div id="settingsSection">
                    <div class="settings-inner">
                        <div class="form-group">
                            <?php foreach( $tokbox_all_user as $user_and_author ){ 
                                $tokbox_text_chat = get_post_meta( $post->ID, 'tokbox_video_and_text_chat_' . $author_and_user . '_' . $user_and_author, true );
                            ?>
                            <div class="text-chat-history">
                                <div class="tokbox_video_chat-accordion tokbox_video_chat-accordion-true"><?php echo $user_and_author; ?></div>
                                <div class="tokbox_video_chat-panel">
                                    <div id="history">
                                        <?php foreach( $tokbox_text_chat as $single_mas ){
                                            $className = ($author_and_user == $single_mas['user_name']) ? 'mine' : 'theirs';
                                        ?>
                                        <div class="<?php echo $className; ?>">
                                            <p class="owner-msg"><?php echo $single_mas['msgText']; ?></p>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php 
            $output = ob_get_clean();
            echo $output;
        }

        public function opentalk_video_history_metaboxCallback($post){
            ob_start();

            $all_urls = get_post_meta( $post->ID, 'session_all_archive_videos_url', true );

            if( $all_urls == '' ){
                $current_page_id = $post->ID;
                $session_id = get_post_meta( $post->ID, 'tokbox_session_id', true );
                $opentok = $this->opentok();
                $archiveList = $opentok->listArchives();

                $all_url = array();
                foreach($archiveList->getItems() as $s){

                    if($session_id == $s->sessionId){
                        $single_url = $s->url;
                        array_push( $all_url, $single_url );
                    }
                }
                $all_urls = array_filter( $all_url );
                update_post_meta( $current_page_id, 'session_all_archive_videos_url', $all_urls );
            }

            ?>
                <div id="settingsSection">
                    <div class="settings-inner">
                    <?php foreach( $all_urls as $single_url ){ ?>
                        <div class="form-group">
                            <video width="640" height="480" controls src="<?php echo $single_url; ?>">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    <?php } ?>
                    </div>
                </div>
            <?php 
            $output = ob_get_clean();
            echo $output;
        }

        public function opentalk_total_earning_metaboxCallback($post){
            ob_start();
            $existingCredit_par_video = get_post_meta( $post->ID, 'session_all_v_credit', true );
            ?>
                <div id="settingsSection">
                    <div class="settings-inner">
                        <div class="form-group">
                            <label for="existingCredit_par_video"><?php _e('Total earning this session', 'opentalk'); ?></label>
                            <input class="datepicker form-control w-100 mt-3px" value="<?php echo $existingCredit_par_video; ?>" type="text" name="existingCredit_par_video" id="existingCredit_par_video" disabled>
                        </div>
                    </div>
                </div>
            <?php
            $output = ob_get_clean();
            echo $output;
        }

        public function redirectTEmplateForVideo($single){
            global $post;

            /* Checks for single template by post type */
            if ( $post->post_type == 'v_session' ) {
                if ( file_exists( $this->plugin_dir . '/template/single_video.php' ) ) {
                    return $this->plugin_dir . '/template/single_video.php';
                }
            }
        
            return $single;
        
        }

        public function save_extra_profile_fields( $user_id ) {

            if ( !current_user_can( 'edit_user', $user_id ) )
                return false;

            /* Edit the following lines according to your set fields */
            update_usermeta( $user_id, 'v_credit', $_POST['v_credit'] );
        }


        public function extra_profile_fields( $user ) { 
            
            $current_user_id = get_current_user_id();
            $user_meta = get_userdata( $current_user_id );
            $user_roles = $user_meta->roles;
            if( in_array("administrator", $user_roles) ){
                $disabled = '';
            }else{
                $disabled = 'disabled';
            }
            ?>
        
            <h3><?php _e('User Credit'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="v_credit"><?php _e('Credit', 'tock_box'); ?></label></th>
                    <td>
                    <input type="number" step="0.01" min="0.00" name="v_credit" id="v_credit" value="<?php echo esc_attr( get_the_author_meta( 'v_credit', $user->ID ) ); ?>" class="regular-text" <?php echo $disabled; ?>/><br />
                    <span class="description"><?php _e('Set Credit as Minute\'s', 'tock_box'); ?></span>
                    </td>
                </tr>
            </table>
        <?php
        }

        public function sessionVEnqueueScriptCallback(){

            global $post;
            global $wp;



            // $apiKey = '46932124';
            // $apiSecret = 'e739bbfbcad1d07c724bfa0f06c2d617844e2d40';

            $apiKey = '47005864'; 
            $apiSecret = 'ba8a8c7ee7cb72f3f00f8178fee865c75d17279e';

            $current_page_id = $post->ID;
            $date_times = get_post_meta( $current_page_id, 'date_time', true );

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
            $user_name = $author_obj->data->user_nicename;

            $session_id = get_post_meta( $current_page_id, 'tokbox_session_id', true );
            $token = get_post_meta( $current_page_id, 'tokbox_token', true );
            
            $joining_massage_for_subscriber = ( !get_option( 'joining_massage_for_subscriber' ) ) ? 'Hi, chief I am joined, my name is : {user-name}' : get_option( 'joining_massage_for_subscriber' );

            $chat_session_id = get_option( 'tokbox_session_id' );
            $chat_token = get_option( 'tokbox_token' );

            $user_login = is_user_logged_in();
            if( $user_login == 1 ){
                $author_role = ( !get_option('show_profile_role') ) ? 'contributor' : get_option('show_profile_role');
                $user_meta = get_userdata( $current_user_id );
                $user_roles = $user_meta->roles;
                $current_user_role = ( in_array( $author_role, $user_roles ) ) ? 'yes' : 'no';
            }

            // Add opentalk js from api server 
            wp_enqueue_script( 'openTalkJS', 'https://static.opentok.com/v2/js/opentok.min.js', array('jquery'), time(), true );
            if(is_singular('v_session')){
                wp_enqueue_script('f_tv_chatJS', $this->plugin_url . 'asset/js/tokbox_video_chat_frontend.js', array('jquery'), time(), true);
            }else{
                wp_enqueue_script('f_tv_chatJS', $this->plugin_url . 'asset/js/tokbox_chat_frontend.js', array('jquery'), time(), true); 
            }

            // Add chat js
            wp_enqueue_script('f_tv_cdnJS', 'https://cdn.jsdelivr.net/npm/promise-polyfill@7/dist/polyfill.min.js', array('jquery'), time(), true);
            wp_enqueue_script('f_tv_cdnJsJS', 'https://cdnjs.cloudflare.com/ajax/libs/fetch/2.0.3/fetch.min.js', array('jquery'), time(), true);

            // Css Files 
            wp_enqueue_style( 'f_tv_chatCSS', $this->plugin_url . 'asset/css/tokbox_video_chat_frontend.css', array(), true, 'all' );

            // localize script 
            wp_localize_script( 'f_tv_chatJS', 'tockbox', array(
                'tockbox_api' => $apiKey,
                'wp_user' => $current_user_id, 
                'current_page_id' => $current_page_id,
                'date_time' => $date_times,
                'post_author_id' => $post_author_id,
                'user_name' => $user_name,
                'post_author_name' => $post_author_name,
                'joining_massage_for_subscriber' => $joining_massage_for_subscriber,
                'session_id' => $session_id,
                'token' => $token,
                'chat_session_id' => $chat_session_id,
                'chat_token' => $chat_token,
                'user_login' => $user_login,
                'current_user_role' => $current_user_role,
                'template_name' => basename( get_page_template() ),
                'ajax_url' => admin_url( 'admin-ajax.php' )
            ) );
        }


        public function openTalkToken($sessionID){
            $opentok = $this->opentok();
            // Replace with meaningful metadata for the connection:
            $connectionMetaData = "";
            // Replace with the correct session ID:
            
            return $opentok->generateToken( $sessionID, array('role' => Role::PUBLISHER, 'expireTime' => time()+(7 * 24 * 60 * 60), 'data' =>  $connectionMetaData )); 
        }

        public function sessionFrontEndCallback(){
            ob_start();
            require_once(tokbox_video_chatDIR . 'template/v-lists.php');
            $output = ob_get_clean();
            echo $output;
        }

        public function allSessions(){
            /*
            * All public sessions
            */
            $arsgs = array(
                'post_type'  => 'v_session',
                'post_status'    => 'publish',
                'posts_per_page' => -1
            );
            $posts = get_posts($arsgs);
            return $posts;
        }

        public function sessionSavePostCallback($post_id){
            $post_type = get_post_type( $post_id );
            $post_status = get_post_status( $post_id);
            if($post_type == 'v_session' && $post_status == 'publish'){
                $exists_meta = get_post_meta( $post_id, 'tokbox_session_id', true );
                // if(!$exists_meta){
                    $sessionID = $this->sessionId();
                    $token = $this->openTalkToken($sessionID);
                    update_post_meta( $post_id, 'tokbox_session_id', $sessionID );
                    update_post_meta( $post_id, 'tokbox_token', $token );
                // }


                // Save settings
                if(isset( $_POST['date_time'] ) ){
                    update_post_meta( $post_id, 'date_time', esc_attr( $_POST['date_time'] ) );
                }
                if(isset( $_POST['duration'] ) ){
                    update_post_meta( $post_id, 'duration', esc_attr( $_POST['duration'] ) );
                }
                if(isset( $_POST['select_contributor'] ) ){
                    update_post_meta( $post_id, 'select_contributor', esc_attr( $_POST['select_contributor'] ) );
                }


            }
        }


        function opentok(){
            // $apiKey = '46932124';
            // $apiSecret = 'e739bbfbcad1d07c724bfa0f06c2d617844e2d40';

            $apiKey = '47005864'; 
            $apiSecret = 'ba8a8c7ee7cb72f3f00f8178fee865c75d17279e';
            $opentok = new OpenTok($apiKey, $apiSecret);
            return $opentok; 
        }

        public function sessionId(){

            $opentok = $this->opentok();
            

            // Create a session that attempts to use peer-to-peer streaming:
            $session = $opentok->createSession();

            // A session that uses the OpenTok Media Router, which is required for archiving:
            $session = $opentok->createSession(array( 'mediaMode' => MediaMode::ROUTED ));

            // A session with a location hint:
            $session = $opentok->createSession(array( 'location' => '12.34.56.78' ));

            // An automatically archived session:
            $sessionOptions = array(
                'archiveMode' => ArchiveMode::ALWAYS,
                'mediaMode' => MediaMode::ROUTED
            );
            $session = $opentok->createSession($sessionOptions);


            // Store this sessionId in the database for later use
            $sessionId = $session->getSessionId();



            return $sessionId;

        }

        function broadcastid(){

            $sessionId = $this->sessionId();

            // Start a live streaming broadcast of a session
            $broadcast = $opentok->startBroadcast($sessionId);


            // Start a live streaming broadcast of a session, using broadcast options
            $options = array(
                'layout' => Layout::getBestFit(),
                'maxDuration' => 5400,
                'resolution' => '1280x720'
            );
            $broadcast = $opentok->startBroadcast($sessionId, $options);

            // Store the broadcast ID in the database for later use
            $broadcastId = $broadcast->id;

            return $broadcastId;

        }

        function test(){
            // $broadcastId = $this->broadcastId();
            $sessionId = $this->sessionId();
            echo 'token: ' . $this->openTalkToken($sessionId) . '<br/>';
            // echo 'token : ' . $token = $opentok->generateToken($sessionId);
            // echo 'jony' . $broadcast = $opentok->getBroadcast($broadcastId);
        }



        /*
        * Appointment backend Script
        */
        function larasoftNote_backend_script(){
            
            wp_enqueue_style( 'b_tv_chatCSS', $this->plugin_url . 'asset/css/tokbox_video_chat_backend.css', array(), true, 'all' );
            wp_enqueue_script( 'b_tv_chatJS', $this->plugin_url . 'asset/js/tokbox_video_chat_backend.js', array(), true );

            // Datepicker
            wp_enqueue_script( 'digitalcustdev-datetimepicker', $this->plugin_url . 'asset/js/jquery.datetimepicker.full.min.js', array( 'jquery' ), '1.0.0', true );
            wp_enqueue_style( 'digitalcustdev-datetimepicker', $this->plugin_url . 'asset/css/jquery.datetimepicker.css', false, '1.0.0' );
            wp_localize_script( 'b_tv_chatJS', 'tockboxAjax', admin_url( 'admin-ajax.php' ));


        }

        function tokbox_video_chatsettingssaveajax(){

            $formdata = $_POST['formVar']; 
            foreach($formdata as $sOption){
    
                update_option( $sOption['name'], $sOption['value']);
                
            }
    
            echo json_encode(
                array(
                    'message' => 'success',
                    'formData' => $formdata
                )
            );
            die();
        }

        
        function cr_session_post_type(){
            $args = array(
                'public'    => true,
                'label'  => __( 'Session', 'tokbox_video_chat' ),
                'supports'  => array( 'title', 'thumbnail' ),
                'menu_icon' => 'dashicons-video-alt2',
            );
            register_post_type( 'v_session ', $args );

            // $result = add_role(
            //     'tv_chat_customer',
            //     __( 'Customer', 'tokbox_video_chat' ),
            //     array(
            //         'read'         => true,
            //     )
            // );
             
            // if ( null !== $result ) {
            //     echo "Success: {$result->name} user role created.";
            // }
            // else {
            //     echo 'Failure: user role already exists.';
            // }
        }




        function tokbox_video_chat_admin_menu_function(){
            add_submenu_page( 'edit.php?post_type=v_session', 'Settings', 'Settings', 'manage_options', 'tokbox-video-chat-Settings', array($this, 'submenufunction') );
        }

        function submenufunction(){

            ?>
                <div class="tokbox-video-chat-submenu">
                    <div class="tokbox-video-chat-title-csv">
                        <div class="tokbox-video-chat-submenu-title">
                            <h1><?php _e('Tokbox Video Chat Settings', 'tokbox_video_chat'); ?></h1>
                        </div>
                    </div>
                    <!-- Settings -->
                    <div class="tokbox-video-chat">
                        <div class="tokbox-video-chat-loder">
                            <div class="tokbox-video-chat-gif">
                                <div class="gifInnter">
                                    <img src="<?php echo $this->plugin_url ?>/asset/css/images/loader.gif" alt="loding..." />
                                </div>
                            </div>
                        </div>

                        <div id="tokbox_video_chat_settings" class="tabcontent">
                            <div class="settingsInner">

                                <div class="tokbox-video-chat-section-cover tokbox-video-chat-section-cover-width-full">
                                    <div class="tokbox_video_chat-accordion tokbox_video_chat-accordion-true"><?php _e('Massage Settings', 'tokbox_video_chat'); ?></div>
                                    <div class="tokbox_video_chat-panel">
                                        <form id="tokbox_video_chat_settings_submit_form" method="post" action="">
                                            <table class="tokbox-video-chat-data-table">
                                                <tbody>
                                                    <tr>
                                                        <th class="text-left"><?php _e('Joining massage for subscriber', 'tokbox_video_chat' ); ?></th>
                                                        <td class="text-left">
                                                            <?php
                                                                $joining_massage_for_subscriber = ( !get_option( 'joining_massage_for_subscriber' ) ) ? 'Hi, chief I am joined, my name is : {user-name}' : get_option( 'joining_massage_for_subscriber' ); 
                                                            ?>
                                                            <input type="text" name="joining_massage_for_subscriber" class="tokbox_video_chat_input" value="<?php echo $joining_massage_for_subscriber; ?>">
                                                            <div class="tokbox-video-chat-note"><span>Note: </span>Use "{user_name}" for dynamic user name.</div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-left"></th>
                                                        <td class="text-left"><input type="submit" class="tokbox_video_chat-submit-settings" value="Save"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </form>
                                    </div>
                                </div>

                                <div class="tokbox-video-chat-section-cover tokbox-video-chat-section-cover-width-half">
                                    <div class="tokbox_video_chat-accordion tokbox_video_chat-accordion-true"><?php _e('Chat Profiles Settings', 'tokbox_video_chat'); ?></div>
                                    <div class="tokbox_video_chat-panel">
                                        <form id="tokbox_video_chat_settings_submit_form" method="post" action="">
                                            <table class="tokbox-video-chat-data-table">
                                                <tbody>
                                                    <tr>
                                                        <th class="text-left"><?php _e('Show Profile Role', 'tokbox_video_chat' ); ?></th>
                                                        <td class="text-left">
                                                            <?php global $wp_roles; ?>

                                                            <select name="show_profile_role" class="show_profile_role">
                                                                <?php foreach ( $wp_roles->roles as $key=>$value ): 
                                                                    $selected = ( get_option('show_profile_role') == $key ) ? 'selected' : '';
                                                                    $disabled = ( 'subscriber' == $key ) ? 'disabled' : '';
                                                                    ?>
                                                                    <option <?php echo $selected; ?> value="<?php echo $key; ?>" <?php echo $disabled; ?>><?php echo $value['name']; ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <div class="tokbox-video-chat-note"><span>Note: </span>You Select Only Contributor.</div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-left"></th>
                                                        <td class="text-left"><input type="submit" class="tokbox_video_chat-submit-settings" value="Save"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>



                
            <?php
        }


    } // End Class
} // End Class check if exist / not


?>

