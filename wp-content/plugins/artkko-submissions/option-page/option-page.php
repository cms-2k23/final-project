<?php

if (!defined('ABSPATH')) {
    die("You should not be here!");
}

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('after_setup_theme', 'load_carbon_fields');
add_action('carbon_fields_register_fields', 'create_options_page');

function load_carbon_fields()
{
    \Carbon_Fields\Carbon_Fields::boot();
}

function create_options_page()
{
    Container::make('theme_options', __('Submissions Form'))
        ->set_page_menu_position(30)
        ->set_icon('dashicons-buddicons-activity')
        ->add_fields(array(
            Field::make('checkbox', 'artkko_plugin_active', __('Active')),
            Field::make('text', 'artist_id', __('artist_id')),
            Field::make('text', 'artkko_customer_name', __('Name'))
                ->set_attribute('placeholder', 'eg. Joe Doe')->set_help_text('Your name'),
            Field::make('text', 'artkko_customer_email', __('Email'))
                ->set_attribute('placeholder', 'eg. your@email.com')->set_help_text('The email that the form is submitted to'),
            Field::make('text', 'artkko_customer_phone', __('Phone Number'))
                ->set_attribute('placeholder', 'eg. 000-000-000')->set_help_text('Your phone number'),
            Field::make('radio', 'artkko_contact_form', __('How should I let you know your commission is done?'))
                ->add_options(array(
                    'text' => __('Text'),
                    'call' => __('Call'),
                    'email' => __('Email'),
                )),
            Field::make('date', 'artkko_deadline', __('Deadline'))
                ->set_storage_format('Y-m-d'),
            Field::make('textarea', 'artkko_description', __('Details'))
                ->set_attribute('placeholder', 'Enter details about your desired art.')
                ->set_help_text('Type the message you want the submitted to receive'),
        ));
}