<?php
require_once '/wordpress/wp-load.php';
update_option( 'permalink_structure', '/%postname%/' );
global $wp_rewrite;
$wp_rewrite->init();
$wp_rewrite->flush_rules( true );
echo "permalinks set\n";
