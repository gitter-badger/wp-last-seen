<?php
/*
 * Plugin Name:       WP Last Seen
 * Plugin URI:        http://github.com/foae/wp-last-seen
 * Description:       This plugin will track when was the last time your registered users interacted with your WordPress site. Has the ability to place a Widget in front end which contains an icon status (three colours), username and a last seen timer (minutes/hours/days).
 * Version:           1.0.0
 * Author:            Lucian Alexandru
 * Author URI:        https://plainsight.ro
 * Text Domain:       wp-last-seen-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

class WP_LastSeen {

    /**
     * Holds the plugin version
     * @var string
     */
    public $version = '1.0.0';

    /**
     * WordPress database wrapper
     * @var object wpdb
     */
    public $wpdb;

    /**
     * The current logged in user ID
     * @var int
     */
    public $userId;

    /**
     * Interval at which we mark a user as active, idle or offline
     * @var int
     */
    public $updateInterval;

    /**
     * Static access to our instance
     * @var null
     */
    protected static $instance = NULL;

    /**
     * Constructor
     */
    public function __construct() {

        // Access to the WordPress database wrapper
        global $wpdb;
        $this->wpdb = &$wpdb;

        // Grab the current user ID
        //add_action('init', array($this, 'getCurrentUserId'));
        $this->getCurrentUserId();

        // Activation / deactivation methods
        $this->controlRunningState();

        // Set the timezone to UTC
        $this->setTimezone();

        // Set the update interval (in seconds)
        $this->updateInterval = 30;

        // Update the timestamp for the current logged in user
        $this->updateTimestamp();

        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontScripts'));
        //add_action('init', array($this, 'getInstance'));
    }

    /**
     * Returns class name as a string. Useful in setting up transients, meta and such
     * @return string
     */
    public function __toString() {
        return __CLASS__;
    }

    /**
     * Static instance access
     * @return null|WP_LastSeen
     */
    public static function getInstance() {

        if (self::$instance === NULL) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Activation & deactivation hooks
     */
    public function controlRunningState() {
        register_activation_hook(__FILE__, array($this, 'activatePlugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivatePlugin'));
    }

    /**
     * Action on plugin install / activation. Used in method controlRunningState
     */
    public function activatePlugin() {}

    /**
     * Action on plugin deactivation. Used in method controlRunningState
     */
    public function deactivatePlugin() {}

    /**
     * Return the plugin URL
     * @return string
     */
    public function pluginURL() {
        return plugins_url() . '/wp-last-seen/';
    }

    /**
     * Return the path to our plugin (server path)
     * @return string
     */
    public function pluginPath() {
        return plugin_dir_path(__FILE__) . 'wp-last-seen/';
    }

    /**
     * Given just the image name, it returns the full URL to that image
     * @param $img
     * @return string
     */
    public function pluginImgURL($img) {
        return plugins_url() . '/wp-last-seen/static/img/' . $img;
    }

    /**
     * Enqueue admin side CSS and JavaScript
     * @param $hook
     *
     * v1.0.0 - Currently deactivated since we don't have an admin menu
     */
    public function enqueueAdminScripts($hook) {
        if ($hook == 'tools_page_view-last-seen') {
            wp_enqueue_style(__CLASS__, $this->pluginURL() . 'static/css/admin-last-seen.css', array(), $this->version, 'all' );
            wp_enqueue_script(__CLASS__, $this->pluginURL() . 'static/js/admin-last-seen.js', array('jquery'), $this->version, FALSE);
        }
    }

    /**
     * Enqueue front end CSS and JavaScript
     */
    public function enqueueFrontScripts() {
        wp_enqueue_style(__CLASS__, $this->pluginURL() . 'static/css/front-last-seen.css', array(), $this->version, 'all' );
        wp_enqueue_script(__CLASS__, $this->pluginURL() . 'static/js/front-last-seen.js', array('jquery'), $this->version, FALSE);
    }

    /**
     * Set the plugin timezone to UTC
     */
    public function setTimezone() {
        date_default_timezone_set("UTC");
    }

    /**
     * Get the current logged in user ID
     * @return int
     */
    public function getCurrentUserId() {
        $this->userId = get_current_user_id();
        return $this->userId;
    }

    /**
     * Query the entire user database and return it in 'raw' format
     * @return array
     */
    public function getAllUsersRaw() {
        $args = array(
            'blog_id'      => $GLOBALS['blog_id'],
            'role'         => '',
            'meta_key'     => __CLASS__,
            'meta_value'   => '',
            'meta_compare' => '',
            'meta_query'   => array(),
            'date_query'   => array(),
            'include'      => array(),
            'exclude'      => array(),
            'orderby'      => 'login',
            'order'        => 'ASC',
            'offset'       => '',
            'search'       => '',
            'number'       => '',
            'count_total'  => false,
            'fields'       => 'all',
            'who'          => ''
        );

        $users = get_users($args);
        return $users;
    }

    /**
     * Accepts an $users array and tries to simplify it
     * @param array $users
     * @return array
     */
    public function getAllUsers(array $users) {

        $results = array();
        for ($i = count($users) - 1; $i >= 0; $i--) {
            $results[] = $users[$i]->data;
        }

        return $results;

    }

    /**
     * Update the meta (timestamp) every 60 seconds
     * @return bool|int
     */
    public function updateTimestamp() {
        // set_transient($transient, $value, $expiration_in_seconds);
        if (get_transient(__CLASS__) === false) {
            set_transient(__CLASS__, time(), 60);
            return update_user_meta($this->userId, __CLASS__, time());
        }

    }

    /**
     * __toString alternative
     * @return string
     */
    public function getClassName() {
        return __CLASS__;
    }

    /**
     * Receives a time() argument and returns it in a human readable format
     * @param $seconds
     * @return array
     */
    public function secondsToTime($seconds) {

        $dtF = new DateTime("@0");
        $dtT = new DateTime("@$seconds");

        $export = array(
            'day' => $dtF->diff($dtT)->format("%a"),
            'hour' => $dtF->diff($dtT)->format("%h"),
            'minute' => $dtF->diff($dtT)->format("%i"),
            'second' => $dtF->diff($dtT)->format("%s")
        );

        return $export;
    }
}

/* Including the widget */
include 'wp-last-seen-widget.php';

/* Make our object available in our widget (and globally) */
add_action('init', function(){

    if (!isset($WP_LastSeen) || !is_object($WP_LastSeen)) {
        $WP_LastSeen = new WP_LastSeen();
    }

    // TODO: There must a better way:
    $GLOBALS['WP_LastSeen'] = $WP_LastSeen;
});