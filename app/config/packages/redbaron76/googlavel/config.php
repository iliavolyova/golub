<?php

return [

    // OAuth2 Setting, you can get these keys in Google Developers Console
    'oauth2_client_id'      => Config::get('google_api.oauth2_client_id'),
    'oauth2_client_secret'  => Config::get('google_api.oauth2_client_secret'),
    'oauth2_redirect_uri'   => Config::get('google_api.oauth2_redirect_uri'),

    // Definition of service specific values like scopes, OAuth token URLs, etc
    'services' => array(

        'mailreader' => array(
            'scope' => 'https://www.googleapis.com/auth/gmail.readonly'
        ),
        /*'books' => [
            'scope' => 'https://www.googleapis.com/auth/books'
        ]*/

    ),

    // Service file name prefix
    'service_class_prefix' => 'Google_Service_',

    // Custom settings
    'access_type' => 'online',    
    'approval_prompt' => 'auto',

];