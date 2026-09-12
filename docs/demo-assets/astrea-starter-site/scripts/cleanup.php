<?php
/**
 * Construction 028 hardening: the original version of this script deleted
 * posts 1/2/3 unconditionally, assuming a fresh WordPress install always
 * assigns those exact IDs to Hello World / Sample Page / Privacy Policy.
 * That is true for a genuinely fresh install, but is a real
 * Existing-Site-Safety hazard (Order 028 Phase 11: DESTRUCTIVE) if this
 * script were ever pointed at a site where those IDs belong to something
 * else. Each delete is now guarded by a title match against WordPress's
 * own default core content, so an unexpected post at that ID is left
 * alone instead of silently deleted.
 */
require_once '/wordpress/wp-load.php';

function astrea_cleanup_delete_if_default( int $id, string $expected_title ) {
	$post = get_post( $id );
	if ( ! $post ) {
		echo "post $id does not exist — nothing to delete.\n";
		return;
	}
	if ( $post->post_title !== $expected_title ) {
		echo "SKIP: post $id title is \"{$post->post_title}\", not the expected default \"{$expected_title}\" — not deleting.\n";
		return;
	}
	wp_delete_post( $id, true );
	echo "post $id (\"{$expected_title}\") deleted.\n";
}

astrea_cleanup_delete_if_default( 1, 'Hello world!' );
astrea_cleanup_delete_if_default( 2, 'Sample Page' );
astrea_cleanup_delete_if_default( 3, 'Privacy Policy' );
echo "cleanup done\n";
