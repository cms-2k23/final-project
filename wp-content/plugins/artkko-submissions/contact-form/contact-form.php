<?php

if (!defined('ABSPATH')) {
    die('You cannot be here');
}

add_shortcode('artkko_submission', 'show_submission_form');

add_action('rest_api_init', 'create_rest_endpoint');

add_shortcode('show_submission_button', 'show_submission_button');

add_filter('query_vars', 'register_custom_params');

function show_submission_button($atts)
{
    if (is_user_logged_in()) {
        $id = get_current_user_id();
        $id = is_array($atts) && isset($atts['text']) ? esc_attr(sprintf($atts['text'], $id)) : esc_attr($id);
        $url = site_url('/submission-page/');
        $url = add_query_arg('artist_id', $id, $url);
        return '<html><a href="' . esc_url($url) . '">click me</a></html>';
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
    $user = get_user_by('id', get_query_var('artist_id'));
    $curr_user = wp_get_current_user();
    $submit_url = get_rest_url(null, 'v1/submission-page/submit');
    $wpnonce = wp_nonce_field('wp_rest');
//    $path = ARTKKO_SUBMISSIONS_PATH . 'includes/js/easycaptcha.min.js';
//    wp_enqueue_script( 'script', ARTKKO_SUBMISSIONS_PATH . 'includes/js/easycaptcha.min.js', array ( 'jquery' ), 1.1, true);
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
          <label>Artist Name</label><br />
          <input type="text" disabled value="$user->user_firstname $user->user_lastname" name="artist_name"> <br /><br />
          <label>Artist Email</label><br />
          <input type="text" disabled value="$user->user_email" name="artist_email"> <br /><br />
          <hr>
          <label>Name</label><br />
          <input type="text" name="name" required value="$curr_user->user_firstname $curr_user->user_lastname"><br /><br />
          <label>Email</label><br />
          <input type="text" name="email" required value="$curr_user->user_email"<br /><br />
          <label>Phone</label><br />
          <input type="text" id="phone_number" name="phone" minlength="11" maxlength="11" 
             pattern="[0-9]{3}-[0-9]{3}-[0-9]{3}"><br /><br />
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
          <textarea name="message" rows="20" cols="50" required></textarea>
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

function handle_submission_form($data)
{
    // Handle the form data that is posted

    // Get all parameters from form
    $params = $data->get_params();
    $field_name = sanitize_text_field($params['name']);
    $field_email = sanitize_email($params['email']);
    $field_phone = sanitize_text_field($params['phone']);
    $field_message = sanitize_textarea_field($params['message']);


    // Check if nonce is valid, if not, respond back with error
    if (!wp_verify_nonce($params['_wpnonce'], 'wp_rest')) {

        return new WP_Rest_Response('Message not sent', 422);
    }

    // Remove unneeded data from paramaters
    unset($params['_wpnonce']);
    unset($params['_wp_http_referer']);

}