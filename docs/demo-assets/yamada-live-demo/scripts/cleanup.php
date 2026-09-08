<?php
require_once '/wordpress/wp-load.php';
wp_delete_post( 1, true ); // Hello world!
wp_delete_post( 2, true ); // Sample Page
wp_delete_post( 3, true ); // Privacy Policy
echo "cleanup done\n";
