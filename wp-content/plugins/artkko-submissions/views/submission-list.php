<?php

if (!defined('ABSPATH')) {
    die('You cannot be here');
}

add_shortcode('show_todo_commission_button', 'show_artists_submissions_button');
add_shortcode('commissions_list', 'show_submission_list');

function prevent_cpt_delete($delete, $post, $force_delete)
{
    if ('submissions' === $post->post_type && !$force_delete) {
        return $delete;
    }
    return;
}

add_filter('pre_delete_post', 'prevent_cpt_delete', 10, 3);

function show_artists_submissions_button($atts)
{
    if (is_user_logged_in() && um_profile_id() == get_current_user_id()) {
        $id = um_profile_id();
        $id = is_array($atts) && isset($atts['text']) ? esc_attr(sprintf($atts['text'], $id)) : esc_attr($id);
        $url = site_url('/received-commissions/');
        $url = add_query_arg('artist_id', $id, $url);
        return '<a href="' . esc_url($url) . '">Show your comissions</a>';
    }
    return '';
}

function show_submission_list()
{
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $admin_email = get_bloginfo('admin_email');
    $admin_name = get_bloginfo('name');

    $headers[] = "From: {$admin_name} <{$admin_email}>";
    $headers[] = "Content-Type: text/html";

    $user_id = get_query_var('artist_id');
    $artist_id = get_current_user_id();

    if (!is_user_logged_in() || $user_id != $artist_id) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        get_template_part(404);
        exit();
    }
    global $wpdb, $submissions_args;

    $table_name = $wpdb->prefix . "artkko_submissions";

    $retrieve_data = $wpdb->get_results("SELECT * FROM $table_name where artist_id = '$artist_id' and done = 0");
    $html = '<style>
               th {
                   color: #3dd538; 
                   font-size: 12px; 
                   font-family: Roboto; 
                   font-weight: 700; 
                   line-height: 18px; 
                   word-wrap: break-word;
                   font-family: sans-serif
               }
               
               td {
                   color: #ffffff; 
                   font-size: 14px; 
                   font-weight: 400; 
                   line-height: 20px; 
                   letter-spacing: 0.04px; 
                   word-wrap: break-word;
               }
               
               tr {
                border: 1px solid rgba(255,255,255,0.12)
               }
               
               table {
                   font-family: arial, sans-serif;
                   border-collapse: collapse;
                   width: 100%;
                   border-radius: 4px;
                   margin: auto;
                   transition: .7s
               }
               
               td, th {
                   border-top: 1px solid #dddddd;
                   text-align: left;
                   padding: 8px;
               }
               
               input {
                   padding: 10px 20px;
                   color: #fff;
                   border: none;
                   border-radius: 5px;
                   cursor: pointer;
               }
               
               .reject-btn {
                   background-color: #dc3545;
               }
               
               .reject-btn:hover {
                    background-color: #CC0000;
               }
               
               .submit-btn{
                    background-color: #3dd538;
               }
               
               .submit-btn:hover {
                    background-color: #277224;
               }
               
               table.fixed { table-layout:fixed;}
               table.fixed td { overflow: hidden; }
               table.fixed th:nth-of-type(1) {width:80px;}
               table.fixed th:nth-of-type(2) {width:800px;}
               table.fixed th:nth-of-type(3) {width:90px;}
               table.fixed th:nth-of-type(4) {width:110px;}
               table.fixed th:nth-of-type(6) {width:110px;}
               
               input[type=file]::file-selector-button {
                   padding: 10px 20px;
                   background-color: #3dd538;
                   color: #fff;
                   border: none;
                   border-radius: 5px;
                   cursor: pointer;
               }
               
               input[type=file]::file-selector-button:hover {
                background-color: #277224;
               }
            </style>
            <form method="post" id="submission_form" enctype=\'multipart/form-data\'>
            <table class="fixed">';

    $html .= '<tr><th>Customer Name</th><th>Commission content</th><th>Due</th><th></th><th></th><th></th></tr>';
    foreach ($retrieve_data as $row) {
        $html .= '<tr>';
        $html .= '<td >' . $row->customer_name . '</td>';
        $html .= '<td >' . $row->commission_content . '</td>';
        $html .= '<td>' . $row->due . '</td>';
        $delRow = "delete_submission_{$row->id}";
        $subRow = "submit_submission_{$row->id}";
        $html .= "<td><input class='reject-btn' type='submit' name=$delRow value=\"Won't do\"/></td>";
        $html .= "<td><input class='custom-file-input' type='file' name='fileToUpload' id='fileToUpload'></td>";
        $html .= "<td><input class='submit-btn' type='submit' name=$subRow value=\"Submit\"/></td>";
        $html .= '</tr>';
        if (isset($_POST[$delRow])) {
            $headers[] = "Reply-to: {$row->customer_name} <{$row->customer_email}>";
            $subject = "Commission completed";
            $message = "<p>Your request was rejected by artist. We are so sorry!</p>";
            wp_mail($row->customer_email, $subject, $message, $headers,);

            $wpdb->delete($table_name, array('id' => $row->id));
            wp_delete_post($row->submission_id);
            echo "<meta http-equiv='refresh' content='0'>";
        }

        if (isset($_POST[$subRow])) {
            $attachment_id = media_handle_upload( 'fileToUpload', $_POST['post_id'] );
            $attachments = get_attached_file( $attachment_id );

            $headers[] = "Reply-to: {$row->customer_name} <{$row->customer_email}>";
            $subject = "Commission completed";
            $message = "<p>Your request was completed!</p>";
            wp_mail($row->customer_email, $subject, $message, $headers, array($attachments) );
            wp_delete_attachment( $attachment_id, true );

            $wpdb->update($table_name, array('done' => 1), array('ID' => $row->id));
            echo "<meta http-equiv='refresh' content='0'>";
        }
    }
    $html .= '</table></form>';
    return $html;
}