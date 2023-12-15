<?php

if (!defined('ABSPATH')) {
    die('You cannot be here');
}

add_shortcode('show_todo_commission_button', 'show_artists_submissions_button');
add_shortcode('commissions_list', 'show_submission_list');

function prevent_cpt_delete( $delete, $post, $force_delete ) {
    if ( 'submissions' === $post->post_type && ! $force_delete ) {
        return $delete;
    }
    return ;
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

function send_artist_rejection_to_customer($customer_email, $customer_name)
{
    $admin_email = get_bloginfo('admin_email');
    $admin_name = get_bloginfo('name');

    $headers[] = "From: {$admin_name} <{$admin_email}>";
    $headers[] = "Content-Type: text/html";
    $headers[] = "Reply-to: {$customer_name} <{$customer_email}>";
    $subject = "Commission rejected";

    $message = "<p>Your request was rejected by artist. We are so sorry!</p>";
    wp_mail($customer_email, $subject, $message, $headers);
}

function show_submission_list()
{
    $user_id = get_query_var('artist_id');
    $artist_id = get_current_user_id();

    if (!is_user_logged_in() || $user_id != $artist_id) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        get_template_part( 404 );
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

</style>

<form method="post" id="submission_form"><table>';
    $html .= '<tr><th>Artist ID</th><th>Customer Email</th><th>Customer Name</th><th>Due</th></tr>';
    foreach ($retrieve_data as $row) {
        $html .= '<tr>';
        $html .= '<td >' . $row->customer_name . '</td>';
        $html .= '<td >' . $row->commission_content . '</td>';
        $html .= '<td>' . $row->due . '</td>';
        $delRow = "delete_submission_{$row->id}";
        $html .= "<td><input type='submit' name=$delRow value=\"Won't do\"/></td>";
        $html .= '<td> <button>Submit</button></td>';
        $html .= '</tr>';
        if (isset($_POST[$delRow])) {
            send_artist_rejection_to_customer($row->customer_email, $row->customer_name);
            $wpdb->delete($table_name, array('id' => $row->id));
            wp_delete_post($row->submission_id);
            echo "<meta http-equiv='refresh' content='0'>";
        }
    }
    $html .= '</table></form>';
    return $html;
}