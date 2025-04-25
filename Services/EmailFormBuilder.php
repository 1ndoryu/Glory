<?php


function glory_ajax_register_email()
{


    $nonceAction = isset($_POST['nonce_action']) ? sanitize_key($_POST['nonce_action']) : 'glory_email_signup_nonce'; // Get nonce action from form data if possible
    check_ajax_referer($nonceAction, '_ajax_nonce');

    // 2. Sanitize and Validate Email
    if (!isset($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error(['message' => 'Please provide a valid email address.']);
        wp_die();
    }
    $email = sanitize_email($_POST['email']);


    if (email_exists($email)) {
        wp_send_json_error(['message' => 'This email address is already registered.']);
        wp_die();
    }

    $password = wp_generate_password(12, true, true);

    $user_id = wp_create_user($email, $password, $email);


    if (is_wp_error($user_id)) {
        error_log('Glory Signup - User Creation Error: ' . $user_id->get_error_message());
        wp_send_json_error(['message' => 'Could not create account. Please try again later.']);
    } else {

        // Optional: Log in the user immediately
        // wp_set_current_user($user_id);
        // wp_set_auth_cookie($user_id);

        // Send success response with user ID for the next step
        wp_send_json_success([
            'message' => 'Account created successfully!',
            'userId' => $user_id
        ]);
    }

    wp_die();
}

add_action('wp_ajax_glory_register_email', 'glory_ajax_register_email');
add_action('wp_ajax_nopriv_glory_register_email', 'glory_ajax_register_email');



function glory_ajax_update_user_details()
{

    $nonceAction = isset($_POST['nonce_action']) ? sanitize_key($_POST['nonce_action']) : 'glory_email_signup_nonce';
    check_ajax_referer($nonceAction, '_ajax_nonce');


    if (!isset($_POST['user_id']) || empty($_POST['user_id']) || !absint($_POST['user_id'])) {
        wp_send_json_error(['message' => 'Invalid user identifier.']);
        wp_die();
    }
    $user_id = absint($_POST['user_id']);

    if (!get_userdata($user_id)) {
        wp_send_json_error(['message' => 'User not found.']);
        wp_die();
    }

    $first_name = '';
    if (isset($_POST['first_name'])) {
        $first_name = sanitize_text_field($_POST['first_name']);
    }

    $last_name = '';
    if (isset($_POST['last_name'])) {
        $last_name = sanitize_text_field($_POST['last_name']);
    }


    $updated_fname = update_user_meta($user_id, 'first_name', $first_name);
    $updated_lname = update_user_meta($user_id, 'last_name', $last_name);


    wp_send_json_success([
        'message' => 'Profile updated successfully!'
    ]);


    wp_die();
}
add_action('wp_ajax_glory_update_user_details', 'glory_ajax_update_user_details');
add_action('wp_ajax_nopriv_glory_update_user_details', 'glory_ajax_update_user_details');
