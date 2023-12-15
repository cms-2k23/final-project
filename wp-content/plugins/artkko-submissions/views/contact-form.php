<?php

if (!defined('ABSPATH')) {
    die('You cannot be here');
}

add_shortcode('artkko_submission', 'show_submission_form');

add_action('rest_api_init', 'create_rest_endpoint');

add_shortcode('show_submission_button', 'show_submission_button');

add_filter('query_vars', 'register_custom_params');

add_action('init', 'create_submissions_page');

global $submissions_args;
$submissions_args = [
    'public' => true,
    'has_archive' => true,
    'menu_position' => 30,
    'publicly_queryable' => false,
    'labels' => [
        'name' => 'submissions',
        'singular_name' => 'submission',
        'edit_item' => 'View Submission'
    ],
    'supports' => false,
    'capability_type' => 'post',
    'capabilities' => array(
        'create_posts' => false,
    ),
    'map_meta_cap' => true,
    'post_type' => 'submission',
];

function create_submissions_page()
{
    global $submissions_args;
    register_post_type('submission', $submissions_args);
}

function show_submission_button($atts)
{
    if (is_user_logged_in()) {
        $id = um_profile_id();
        $id = is_array($atts) && isset($atts['text']) ? esc_attr(sprintf($atts['text'], $id)) : esc_attr($id);
        $url = site_url('/submission-form/');
        $url = add_query_arg('artist_id', $id, $url);
        return '<a href="' . esc_url($url) . '">Order comission</a>';
    }
    return '';
}

function register_custom_params($vars)
{
    $vars[] = "artist_id";
    return $vars;
}

function show_submission_form()
{
    $user_id = get_query_var('artist_id');
    $user = get_user_by('id', $user_id);
    $curr_user = wp_get_current_user();
    $submit_url = get_rest_url(null, 'v1/submission-page/submit');
    $wpnonce = wp_nonce_field('wp_rest');
    return <<<HTML
        <style>
           #artkko_submission_form {
               width: 80%;
               margin: 0 auto;
               padding: 20px;
               border: 1px solid #ccc;
               border-radius: 5px;
               align-content: center;
           }
           
           #artkko_submission_form input[type="text"],
           #artkko_submission_form input[type="date"],
           #artkko_submission_form textarea, 
           #artkko_submission_form fieldset{
               width: 95%;
               padding: 10px;
               border: 1px solid #ccc;
               border-radius: 5px;
               margin: 0 auto 10px auto;
           }
           
           #artkko_submission_form input[type="checkbox"] {
            margin-right: 10px;
           }
           
           #artkko_submission_form button[type="submit"] {
               padding: 10px 20px;
               background-color: #007BFF;
               color: #fff;
               border: none;
               border-radius: 5px;
               cursor: pointer;
           }
           
           #artkko_submission_form button[type="submit"]:hover {
            background-color: #0056b3;
           }
    </style>
    <html>
       <div id="form_success" style="background-color:green; color:#fff;"></div>
       <div id="form_error" style="background-color:red; color:#fff;"></div>
       <form id="artkko_submission_form">
          $wpnonce
          <input type="text"hidden="hidden" readonly value="$user_id" name="artist_id" >
          <label>Artist Name</label><br />
          <input type="text"  readonly value="$user->user_firstname $user->user_lastname" name="artist_name"> <br /><br />
          <label>Artist Email</label><br />
          <input type="text" readonly value="$user->user_email" name="artist_email"> <br /><br />
          <hr>
          <label>Name</label><br />
          <input type="text" name="name" required value="$curr_user->user_firstname $curr_user->user_lastname"><br /><br />
          <label>Email</label><br />
          <input type="text" name="email" required value="$curr_user->user_email"<br /><br />
          <label>Phone</label><br />
          <input type="text" id="phone_number" name="phone" minlength="11" maxlength="11" 
             pattern="[0-9]{3}-[0-9]{3}-[0-9]{3}" placeholder="e.g. 123-456-789"><br /><br />
          <fieldset>
             <legend>How should I let you know your commission is done?</legend>
             <div>
                <input type="checkbox" id="check_box_email" name="check_box_email" checked/>
                <label for="check_box_email">Email</label>
             </div>
             <div>
                <input type="checkbox" id="check_box_text" name="check_box_text" disabled/>
                <label for="check_box_text">Text</label>
             </div>
             <div>
                <input type="checkbox" id="check_box_call" name="check_box_call" disabled/>
                <label for="check_box_call">Call</label>
             </div>
          </fieldset>
          <label>Deadline</label><br />
          <input type="date" name="deadline" required><br /><br />
          <label>Message</label><br />
          <textarea name="message" rows="20" cols="50" required placeholder="Describe art you are dereaming of..." maxlength="1000"></textarea>
          <br /><br />
          <div class="captchaTarget" 
            data-auto-easycaptcha 
            data-okbtn-selector="#submit">
          </div>
          <button type="submit">Submit form</button>
       </form>
       <script>       
          jQuery(document).ready(function($) {
              $('#phone_number').on('keyup', function() {
                  const phone_number = $(this).val();
                  if(phone_number.length !== 11) {
                      $('#check_box_text').prop('disabled', true);
                      $('#check_box_call').prop('disabled', true);
                  } else {
                      $('#check_box_text').prop('disabled', false);
                      $('#check_box_call').prop('disabled', false);
                  }
              });
          });
          
          jQuery(document).ready(function($){
                $("#artkko_submission_form").submit( function(event){
                      event.preventDefault();
                      $("#form_error").hide();
                      const form = $(this);
                      console.log(form.serialize());
                      $.ajax({
                            type:"POST",
                            url: "$submit_url",
                            data: form.serialize(),
                            success:function(res){
                                  form.hide();
                                  $("#form_success").html(res).fadeIn();
                            },
                            error: function(){
                                  $("#form_error").html("There was an error submitting").fadeIn();
                            }
                      })
                });
          });
       </script>
    </html>
    HTML;
}

function create_rest_endpoint()
{
    register_rest_route('v1/submission-page', 'submit', array(
        'methods' => 'POST',
        'callback' => 'handle_submission_form'
    ));
}

function send_confirmation_to_customer($headers, $params)
{
    $customer_email = strtolower(trim(sanitize_email($params['email'])));
    $customer_name = sanitize_text_field($params['name']);

    $headers[] = "Reply-to: {$customer_name} <{$customer_email}>";
    $subject = "Commission confirmation";

    $message = "<p>Your request was successfully sent. Artist should contact you in a couple of days!</p>";
    wp_mail($customer_email, $subject, $message, $headers);
}

function send_system_rejection_to_customer($headers, $params, $word)
{
    $customer_email = strtolower(trim(sanitize_email($params['email'])));
    $customer_name = sanitize_text_field($params['name']);

    $headers[] = "Reply-to: {$customer_name} <{$customer_email}>";
    $subject = "Commission rejected";

    $message = "<p>Your request was rejected. In your request contains prohibited word {$word}!</p>";
    wp_mail($customer_email, $subject, $message, $headers);
}

function send_confirmation_to_artist($headers, $params)
{
    $artist_email = strtolower(trim(sanitize_email($params['artist_email'])));
    $customer_email = strtolower(trim(sanitize_email($params['email'])));

    $artist_name = sanitize_email($params['artist_name']);
    $customer_name = sanitize_text_field($params['name']);

    $headers[] = "Reply-to: {$artist_name} <{$artist_email}>";
    $subject = "New commission request";

    $message = "<p>You have new request from {$customer_name}. To contact him write on {$customer_email}</p>";
    $postarr = [
        'post_title' => $params['name'],
        'post_type' => 'submission',
        'post_status' => 'publish'
    ];

    $post_id = wp_insert_post($postarr);
    foreach ($params as $label => $value) {
        $value = match ($label) {
            'message' => sanitize_textarea_field($value),
            'email' => sanitize_email($value),
            default => sanitize_text_field($value),
        };

        add_post_meta($post_id, sanitize_text_field($label), $value);
        $message .= '<strong>' . sanitize_text_field(ucfirst($label)) . ':</strong> ' . $value . '<br />';
    }
    wp_mail($customer_email, $subject, $message, $headers);
}

function handle_submission_form($data)
{
    $params = $data->get_params();

    if (!wp_verify_nonce($params['_wpnonce'], 'wp_rest')) {
        return new WP_Rest_Response('Message not sent', 422);
    }

    unset($params['_wpnonce']);
    unset($params['_wp_http_referer']);

    $headers = [];

    $admin_email = get_bloginfo('admin_email');
    $admin_name = get_bloginfo('name');

    $headers[] = "From: {$admin_name} <{$admin_email}>";
    $headers[] = "Content-Type: text/html";

    $filter = new BlacklistedWordFilterIterator();
    list($contains, $word) = $filter->containsBlacklistedWords(explode(' ', $params['message']), true);
    if ($contains) {
        send_system_rejection_to_customer($headers, $params, $word);
        return new WP_Rest_Response("The submission was rejected. Check your email for details.", 200);
    }

    send_confirmation_to_customer($headers, $params);
    send_confirmation_to_artist($headers, $params);

    global $wpdb;
    $table_name = $wpdb->prefix . 'artkko_submissions';
    $customer_email = strtolower(trim(sanitize_email($params['email'])));

    $customer_name = sanitize_text_field($params['name']);
    global $submissions_args;
    $submissions = get_posts($submissions_args);

    $wpdb->insert($table_name, array
        ('submission_id' => $submissions[count($submissions) - 1]->ID,
            'artist_id' => $params['artist_id'],
            'customer_email' => $customer_email,
            'customer_name' => $customer_name,
            'commission_content' => $params['message'],
            'done' => false,
            'due' => $params['deadline'])
    );

    return new WP_Rest_Response("The submission was accepted!!", 200);
}