<?php
/**
 * Client-side Editor registration for ASTREA Core's Dynamic Blocks
 * (Construction Order 013, Finding 5).
 *
 * Registers exactly one small JS asset (assets/js/editor-blocks.js) that
 * gives every server-rendered-only Dynamic Block a minimal client-side
 * counterpart, so the Block/Site Editor stops treating them as
 * unregistered ("このサイトは...ブロックに対応していません") or
 * failing validation ("復旧を試みる"). See that file's own docblock for
 * the full reasoning and the exact compatibility guarantee (save() always
 * returns null, matching every already-stored self-closing block comment
 * byte-for-byte — no migration).
 *
 * This script is registered with `wp_register_script()` and attached to
 * each Dynamic Block's own `register_block_type()` call via the standard
 * `editor_script_handles` argument (WP_Block_Type property since 6.1) —
 * it is only ever enqueued by WordPress core itself when a page/template
 * containing one of these blocks is opened in the Editor, never on the
 * public front end.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\EditorBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Public contract: the script handle every Dynamic Block's `register_block_type()` call attaches via `editor_script_handles`. */
const SCRIPT_HANDLE = 'astrea-core-editor-blocks';

add_action( 'init', __NAMESPACE__ . '\\register_editor_script' );

/**
 * Registers (but does not enqueue) the shared Editor-only script.
 * WordPress core enqueues it automatically, only in Editor contexts,
 * whenever a block listing this handle in `editor_script_handles` is
 * present on the screen being edited.
 *
 * @return void
 */
function register_editor_script() {
	wp_register_script(
		SCRIPT_HANDLE,
		plugins_url( 'assets/js/editor-blocks.js', \ASTREA_CORE_FILE ),
		array( 'wp-blocks', 'wp-element', 'wp-i18n' ),
		\ASTREA_CORE_VERSION,
		true
	);

	wp_set_script_translations( SCRIPT_HANDLE, 'astrea-core', \ASTREA_CORE_DIR . 'languages' );
}
