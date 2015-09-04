<?php

//========= Anonymous Access ================================

// bypass authentication if sent from the following IP addresses
Config::set("system.allow_from_ip", array());

// or bypass authentication for the following modules
Config::set("system.allow_module", array(
    // "rest", // uncomment this to switch on REST access to the database objects. Tread with CAUTION!
));

Config::set('system.allow_action', array(
    "auth/login",
    "auth/forgotpassword",
    "auth/resetpassword",
    "admin/datamigration",
	"install-steps/details",
	"install-steps/database",
	"install-steps/import",
	"install-steps/finish"
));

Config::set('system.password_salt', 'override this in your project config');

//========= REST Configuration ==============================
// check the following configuration carefully to secure
// access to the REST infrastructure.

// use the API_KEY to authenticate with username and password
Config::set('system.rest_api_key', "abcdefghijklmnopqrstuvwxyz1234567890");

// include class of objects that you want available via REST
Config::set('system.rest_include', array(
	// "Contact"
));

/**
 * Syntax for csrf config
 */
Config::set('system.csrf', array(
    'enabled' => true,
    'protected' => array(
        'auth' => array(
            'login',
            'forgotpassword'
        )
    )
));

Config::append('email.transports', array(
	'smtp' => 'SwiftMailerTransport',
	'swiftmailer' => 'SwiftMailerTransport',
	'sendmail' => 'SwiftMailerTransport'
));