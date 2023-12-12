<?php

if ( ! $_WORDPRESS_DEVELOP_DIR = getenv('WORDPRESS_DEVELOP_DIR') ) {
	$_WORDPRESS_DEVELOP_DIR = __DIR__ . '/../wordpress-develop/';
}

require_once $_WORDPRESS_DEVELOP_DIR . '/tests/phpunit/includes/functions.php';

$_PLUGIN_ENTRYPOINT = __DIR__ . '/../plugin.php';

tests_add_filter( 'muplugins_loaded', function() use ( $_PLUGIN_ENTRYPOINT ) {
	require $_PLUGIN_ENTRYPOINT;
} );

require $_WORDPRESS_DEVELOP_DIR . '/tests/phpunit/includes/bootstrap.php';

require __DIR__ . '/case.php';
