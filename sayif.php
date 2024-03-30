<?php

/*
Plugin Name: Sayif
Plugin URI: https://sayif.org
Description: Unifies commenting on telegram channel and WordPress website
Version: 1.0
Author: Amir Khalife
Author URI: https://www.linkedin.com/in/amir-mahmoodi/
License: AGPL3
*/

add_action('admin_init', 'register_settings_stuff');
add_action( 'rest_api_init', 'register_routes');
//routes
function register_routes() {
    register_rest_route( 'sayif', '/ping', array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'get_endpoint_ping',
    ) );
}
function get_endpoint_ping($request) {
    $all_headers = getallheaders();
    $submitted_secret_code = isset( $all_headers['sayif_secret_code'] ) ? $all_headers['sayif_secret_code'] : '';
    if (!$submitted_secret_code){
        $secret_code_status = 'not_provided';
    } else {
        $sayif_secret_code = get_option("sayif_secret_code");
        if ($sayif_secret_code != $submitted_secret_code){
            $secret_code_status = 'wrong';
        } else {
            $secret_code_status = 'successful';
        }
    }
    $response_data = array(
        'secret_code'=> $secret_code_status
    );
    return new WP_REST_Response( $response_data, 200 );
}

//settings
function register_settings_stuff(){
    add_settings_section(
        "sayif_setting_section", "SayIf settings", "sayif_settings_section_callback", "discussion");
    add_settings_field(
        'sayif_initiation_code',
        'Enter the code your recived from bot', 'sayif_settings_initiation_code_field_callback',
        'discussion',
        'sayif_setting_section'
    );
    register_setting("discussion", "sayif_initiation_code");
}
function sayif_settings_section_callback($arg)
{
    echo "<p>refer to <a href='https://t.me/SayIfTestBot'>SayIfTestBot</a> telegram bot</p>";
}
function sayif_settings_initiation_code_field_callback()
{
    $sayif_initiation_code = get_option("sayif_initiation_code");
    $sayif_apikey = get_option("sayif_apikey");
    if ($sayif_initiation_code and !$sayif_apikey) {
        $new_secret_code = wp_generate_password(127, true, true);
        update_option("sayif_secret_code", $new_secret_code);
        $response = wp_remote_post(
            "http://127.0.0.1:8010/rest/wp-site-apikey/",
            array(
                'body'        => array(
                    'initiation_code'    => $sayif_initiation_code,
                    'site_url'   => get_option("siteurl"),
                    'secret_code' => $new_secret_code,
                ),
            )
        );
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $json_data = json_decode( $response_body , true);
        if ($response_code != 201) {
            var_dump($json_data);
        } else {
            update_option("sayif_apikey", $json_data['apikey']);
        }
    }
    echo "<input type='text' name='sayif_initiation_code' value='${sayif_initiation_code}'>";
}
