<?php

/**
 *
 * Plugin Name: Artkko Submissions
 * Description: This plugin allows users to make submissions fot commissions from their favourite artist.
 * Version: 0.0.1
 * Text Domain: options-plugin
 *
 */

if (!defined('ABSPATH')) {
    die("You should not be here!");
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
            include_once ARTKKO_SUBMISSIONS_PATH . 'contact-form/contact-form.php';
        }

    }

    $submissionsPlugin = new ArtkkoSubmissions;
    $submissionsPlugin->initialize();
}