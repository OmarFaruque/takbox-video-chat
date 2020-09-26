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
        }



        public function reduseUserCreditWhileSessionOn(){
            // Reduce user Credits
            $userid = esc_attr( $_POST['userid'] );
            $existingCredit = get_user_meta( $userid, 'v_credit', true );
            $newCredit = (int)$existingCredit - 0.5; 
            update_user_meta( $userid, 'v_credit', $newCredit );

            wp_send_json( array(
                'msg' => 'success'
            ) );

        }


        public function opentok_register_meta_boxes(){
            /*
            * Register metabox for session post type
            */
            add_meta_box( 'opentalk_metabox', __( 'Setting\'s', 'opentalk' ), array($this, 'opentalk_metaboxCallback'), 'v_session' );
        }


        public function opentalk_metaboxCallback($post){
            ob_start();
            $date_time = get_post_meta( $post->ID, 'date_time', true );
            $duration = get_post_meta( $post->ID, 'duration', true );
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


        public function extra_profile_fields( $user ) { ?>
            <h3><?php _e('User Credit'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="v_credit"><?php _e('Credit', 'tock_box'); ?></label></th>
                    <td>
                    <input type="number" step="0.01" min="0" name="v_credit" id="v_credit" value="<?php echo esc_attr( get_the_author_meta( 'v_credit', $user->ID ) ); ?>" class="regular-text" /><br />
                    <span class="description"><?php _e('Set Credit as Minute\'s', 'tock_box'); ?></span>
                    </td>
                </tr>
            </table>
        <?php
        }

        public function sessionVEnqueueScriptCallback(){
            $apiKey = '46932124';
            $apiSecret = 'e739bbfbcad1d07c724bfa0f06c2d617844e2d40';
            // Add opentalk js from api server 
            wp_enqueue_script( 'openTalkJS', 'https://static.opentok.com/v2/js/opentok.min.js', array('jquery'), time(), true );
            wp_enqueue_script('f_tv_chatJS', $this->plugin_url . 'asset/js/tokbox_video_chat_frontend.js', array('jquery'), time(), true);

            // Css Files 
            wp_enqueue_style( 'f_tv_chatCSS', $this->plugin_url . 'asset/css/tokbox_video_chat_frontend.css', array(), true, 'all' );

            // localize script 
            wp_localize_script( 'f_tv_chatJS', 'tockbox', array(
                'tockbox_api' => $apiKey, 
                'wp_user' => get_current_user_id(), 
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
            }
        }


        function opentok(){
            $apiKey = '46932124';
            $apiSecret = 'e739bbfbcad1d07c724bfa0f06c2d617844e2d40';
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
            // echo $sessionId = $this->sessionId();
            echo 'token: ' . $this->openTalkToken() . '<br/>';
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

        }


    } // End Class
} // End Class check if exist / not


?>

