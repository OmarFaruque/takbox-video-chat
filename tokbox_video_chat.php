<?php
/**
 * Plugin Name: Tokbox Video Chat
 * Plugin URI: http://larasoftbd.net/
 * Description: Tokbox Video Chat. 
 * Version: 1.0.0
 * Author: larasoft
 * Author URI: https://larasoftbd.net
 * Text Domain: wp_tokbox
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 4.8
 *
 * @package     tokbox_video_chat
 * @category 	Core
 * @author 		LaraSoft
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define('tokbox_video_chatDIR', plugin_dir_path( __FILE__ ));
define('tokbox_video_chatURL', plugin_dir_url( __FILE__ ));


require_once(tokbox_video_chatDIR . 'vendor/autoload.php');
require_once(tokbox_video_chatDIR . 'inc/class.php');

new tokbox_video_chatClass;
