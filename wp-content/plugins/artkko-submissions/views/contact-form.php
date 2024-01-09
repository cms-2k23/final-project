<?php

if (!defined('ABSPATH')) {
    die('You cannot be here');
}

add_shortcode('artkko_submission', 'show_submission_form');

add_action('rest_api_init', 'create_rest_endpoint');

add_action('init', 'create_submissions_page');

global $submissions_args;
$submissions_args = [
    'public' => true,
    'has_archive' => true,
    'menu_position' => 30,
    'publicly_queryable' => false,
    'labels' => [
        'name' => 'Submissions',
        'singular_name' => 'Submission',
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


function show_submission_form()
{
    $user = get_user_by('id', um_profile_id());
    $curr_user = wp_get_current_user();
    $submit_url = get_rest_url(null, 'v1/submission-page/submit');
    $wpnonce = wp_nonce_field('wp_rest');
    return <<<HTML
    <style>
        #artkko_submission_form {
            border: 1px solid #666 !important;
            padding: 2em;
            border-radius: 5px !important;
            flex-direction: column !important;
            background-color: #111;
        }

        #artkko_submission_form input[type="text"],
        #artkko_submission_form input[type="date"],
        #artkko_submission_form textarea,
        #artkko_submission_form fieldset {
            border: 1px solid #ccc !important;
            border-radius: 5px !important;
            margin: 0 0 1.5em 0 !important;
            color: #fff !important;
            background-color: #1a1a1a !important;
        }

        #artkko_submission_form input:focus {
            border: 1px solid var(--wp--preset--color--primary)!important;
        }
        #artkko_submission_form input[type="date"] {
            padding: 0 12px !important;
            height: 40px !important;
            line-height: 32px !important;
            width: 100%;
            display: block !important;
            -moz-border-radius: 2px;
            -webkit-border-radius: 2px;
            border-radius: 2px;
            outline: 0 !important;
            cursor: text !important;
            font-size: 15px !important;
            box-sizing: border-box !important;
            box-shadow: none !important;
            position: static;
        }

        #artkko_submission_form input[type="checkbox"] {
            margin-right: 0.5em !important;
            accent-color: var(--wp--preset--color--primary)
        }

        #artkko_submission_form button[type="submit"] {
            width: fit-content !important;
            align-self: center !important;
            font-weight: bold !important;
            background-color: var(--wp--preset--color--primary);
            border: none;
            border-radius: 5px;
            padding: 0.75em 1em;
            font-size: 14px;
            cursor: pointer !important;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }

        #artkko_submission_form button[type="submit"]:hover {
            background-color: #89DB1D !important;
        }

        #artkko_submission_form > hr {
            width: 100% !important;
            border: 1px solid #666 !important;
        }

        #artist_id {
            display: none !important;
        }

        #artkko_submission_form > label {
            font-weight: 500 !important;
            color: #fff;
        }

        #commission_message {
            width: 100% !important;
            overflow: hidden;
            box-sizing: border-box;
            resize: none;
        }

        #commission_message:focus {
            border: 1px solid var(--wp--preset--color--primary) !important;
        }
    </style>
        <div id="form_success" style="background-color: var(--wp--preset--color--primary);color: #000;"></div>
        <div id="form_error" style="background-color: #FF495C; color: #000;"></div>
        <form id="artkko_submission_form">
          $wpnonce
          <input type="text" id=artist_id readonly value="$user->id" name="artist_id">
          <label>Artist name</label>
          <input type="text" readonly value="$user->user_firstname $user->user_lastname" name="artist_name"> 
          <label>Artist e-mail</label>
          <input type="text" readonly value="$user->user_email" name="artist_email"> 
          <hr>
          <label>Your name</label>
          <input type="text" name="name" readonly required value="$curr_user->user_firstname $curr_user->user_lastname">
          <label>Your e-mail</label>
          <input type="text" name="email" readonly required value="$curr_user->user_email" />
          <label>Your phone no.</label>
          <input type="text" id="phone_number" name="phone" minlength="9" maxlength="9" 
             pattern="[0-9]{9}" placeholder="e.g. 123456789">
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
          <label>Deadline</label>
          <input id=deadline type="date" name="deadline" required>
          <label>Message</label>
          <textarea name="message" id="commission_message" required placeholder="Describe the artwork you're dreaming of..." maxlength="1000"
          oninput='this.style.height = "";this.style.height = this.scrollHeight + "px"; updateCharacterCount()'></textarea>
          <div id="charCount" style="margin-top: -1em;">0/1000 characters</div>
          <div class="captchaTarget" 
            data-auto-easycaptcha 
            data-okbtn-selector="#submit">
          </div>
          <button type="submit">Submit form</button>
       </form>
       <script>

        function updateCharacterCount() {
            var maxLength = 1000; // Set the maximum length
            var textInput = document.getElementById("commission_message").value;
            var charCount = textInput.length;
            document.getElementById("charCount").textContent = charCount + "/" + maxLength + " characters";
        }
    
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

    $message = "<p>Your request was rejected.\n\nIt contained a prohibited word \"{$word}\"!</p>";
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
        if ($label == 'artist_id' || $label == 'artist_email' || $label == 'artist_name') {
            continue;
        }

        $value = match ($label) {
            'message' => sanitize_textarea_field($value),
            'email' => sanitize_email($value),
            default => sanitize_text_field($value),
        };

        if ($label == 'check_box_email') {
            $label = 'Contact via e-mail?';
        }

        add_post_meta($post_id, sanitize_text_field($label), $value);
        $message .= '<strong>' . sanitize_text_field(ucfirst($label)) . ':</strong> ' . $value . '<br />';
    }
    wp_mail($artist_email, $subject, $message, $headers);
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

    $wpdb->insert(
        $table_name,
        array(
            'submission_id' => $submissions[count($submissions) - 1]->ID,
            'artist_id' => $params["artist_id"],
            'customer_email' => $customer_email,
            'customer_name' => $customer_name,
            'commission_content' => $params['message'],
            'done' => false,
            'due' => $params['deadline']
        )
    );

    return new WP_Rest_Response("The submission was accepted!", 200);
}
