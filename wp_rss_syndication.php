<?php
/*
Plugin Name: WordPress RSS Syndication Client
Plugin URI: http://arstropica.com
Description: Display remote WordPress posts in your blog.
Version: 1.0
Author: ArsTropica
Author URI: http://arstropica.com
*/

// Definitions
define('WRS_PLUGIN_FILE', __FILE__);
define('WRS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WRS_PLUGIN_PATH', trailingslashit(dirname(__FILE__)));
define('WRS_PLUGIN_DIR', trailingslashit(WP_PLUGIN_URL) . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));
if ( !defined( 'WP_PLUGIN_DIR' ) )
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
  
require_once(WRS_PLUGIN_PATH . 'classes/admin.php');
require_once(WRS_PLUGIN_PATH . 'classes/loop.php');
require_once(WRS_PLUGIN_PATH . 'classes/shortcodes.php');

?>
