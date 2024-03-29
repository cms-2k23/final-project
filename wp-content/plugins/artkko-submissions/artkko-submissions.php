<?php

/**
 *
 * Plugin Name: Artkko Submissions
 * Description: This plugin allows users to request commissions from their favourite artists.
 * Version: 1.1.0
 * Text Domain: options-plugin
 *
 */

if (!defined('ABSPATH')) {
    die("You should not be here!");
}

register_activation_hook( __FILE__, "activate_artkko" );
function activate_artkko() {

        // WP Globals
        global $table_prefix, $wpdb;

        // Customer Table
        $customerTable = $table_prefix . 'artkko_submissions';

        // Create Customer Table if not exist
        if( $wpdb->get_var( "show tables like '$customerTable'" ) != $customerTable ) {

            // Query - Create Table
            $sql = "CREATE TABLE `$customerTable` (";
            $sql .= " `id` int(11) NOT NULL auto_increment, ";
            $sql .= " `submission_id` varchar(10) NOT NULL, ";
            $sql .= " `artist_id` varchar(10) NOT NULL, ";
            $sql .= " `customer_email` varchar(80) NOT NULL, ";
            $sql .= " `customer_name` varchar(100), ";
            $sql .= " `commission_content` TEXT, ";
            $sql .= " `done` boolean NOT NULL, ";
            $sql .= " `due` date NOT NULL, ";
            $sql .= " PRIMARY KEY `customer_id` (`id`) ";
            $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";

            // Include Upgrade Script
            require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

            // Create Table
            dbDelta( $sql );
        }
}

if (!class_exists('ArtkkoSubmissions')) {

    class ArtkkoSubmissions
    {
        public function __construct()
        {
            define('ARTKKO_SUBMISSIONS_PATH', plugin_dir_path(__FILE__));
            require_once(ARTKKO_SUBMISSIONS_PATH . 'vendor/autoload.php');
        }

        public function initialize(): void
        {
            include_once ARTKKO_SUBMISSIONS_PATH . 'includes/sanitization.php';
            include_once ARTKKO_SUBMISSIONS_PATH . 'option-page/option-page.php';
            include_once ARTKKO_SUBMISSIONS_PATH . 'views/contact-form.php';
            include_once ARTKKO_SUBMISSIONS_PATH . 'includes/ultimate-member-tabs.php';
        }

    }

    $submissionsPlugin = new ArtkkoSubmissions;
    $submissionsPlugin->initialize();
}