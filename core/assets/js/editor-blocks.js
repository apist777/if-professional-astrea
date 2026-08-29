/**
 * ASTREA Core — minimal Editor (client-side) registration for the Dynamic
 * Blocks that already have a server-side render_callback (Construction
 * Order 013, Finding 5 from the Construction 012 Integrated Release
 * Quality Audit).
 *
 * These blocks were, until now, registered via PHP's register_block_type()
 * only — which is enough for front-end rendering (Decision 013's Core/
 * Theme split) but leaves the Block/Site Editor's client-side registry
 * with no matching entry, so any page/template containing one shows an
 * "unsupported block" warning and, on save-validation, a "recovery"
 * prompt. Registering a minimal client-side counterpart here removes both
 * warnings.
 *
 * Deliberately minimal, matching Construction 013's kickoff instructions:
 * `edit` is a static placeholder (no live preview, no ServerSideRender,
 * no Inspector Controls — the real content only ever renders on the
 * published site), and `save` always returns null. Because every one of
 * these blocks is already stored as a self-closing
 * `<!-- wp:astrea/xxx {...} /-->` comment (no inner content), a `save`
 * that returns null re-serializes to the exact same self-closing form —
 * so this introduces no migration and no risk to already-published
 * content. `supports.inserter` is `false` for every entry: none of these
 * blocks are meant to be found and inserted directly by a site owner —
 * each one is only ever embedded inside an existing ASTREA Pattern.
 *
 * Uses only WordPress's own already-enqueued globals (wp.blocks,
 * wp.element, wp.i18n) — no build step, no bundler, no external
 * dependency, no remote script, no eval, no innerHTML.
 *
 * @package Astrea\Core
 */
( function ( blocks, element, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	/**
	 * Renders the shared static placeholder shown in the editor canvas for
	 * every block registered below.
	 *
	 * @param {string} title Human-readable block title, shown in the placeholder.
	 * @return {Object} A wp.element tree.
	 */
	function renderPlaceholder( title ) {
		return el(
			'div',
			{ className: 'astrea-editor-block-placeholder' },
			el( 'strong', {}, title ),
			el(
				'p',
				{},
				__(
					'ASTREA Coreが公開ページで実際のデータを表示します。編集画面では内容は表示されません。',
					'astrea-core'
				)
			)
		);
	}

	/**
	 * Registers one Dynamic Block's minimal client-side counterpart.
	 *
	 * @param {string} name       Block name, e.g. "astrea/price-list".
	 * @param {Object} settings   title, icon, category, attributes (must match the PHP registration).
	 * @return {void}
	 */
	function registerDynamicBlock( name, settings ) {
		blocks.registerBlockType( name, {
			title: settings.title,
			icon: settings.icon,
			category: settings.category,
			description: settings.description,
			attributes: settings.attributes || {},
			supports: { inserter: false, html: false },
			edit: function () {
				return renderPlaceholder( settings.title );
			},
			save: function () {
				return null;
			},
		} );
	}

	var headingAndEmpty = {
		heading: { type: 'string', default: '' },
		emptyMessage: { type: 'string', default: '' },
	};

	var listAttributes = Object.assign( { limit: { type: 'number', default: 0 } }, headingAndEmpty );

	registerDynamicBlock( 'astrea/price-list', {
		title: __( '料金一覧', 'astrea-core' ),
		icon: 'money-alt',
		category: 'widgets',
		description: __( 'ASTREA Core — 料金の一覧を表示します。', 'astrea-core' ),
		attributes: headingAndEmpty,
	} );

	registerDynamicBlock( 'astrea/faq-list', {
		title: __( 'FAQ一覧', 'astrea-core' ),
		icon: 'editor-help',
		category: 'widgets',
		description: __( 'ASTREA Core — よくある質問の一覧を表示します。', 'astrea-core' ),
		attributes: Object.assign(
			{ mode: { type: 'string', default: 'important' } },
			listAttributes
		),
	} );

	registerDynamicBlock( 'astrea/representative', {
		title: __( '代表者紹介', 'astrea-core' ),
		icon: 'groups',
		category: 'widgets',
		description: __( 'ASTREA Core — 代表者として指定された専門家プロフィールを1名表示します。', 'astrea-core' ),
		attributes: headingAndEmpty,
	} );

	registerDynamicBlock( 'astrea/case-list', {
		title: __( '対応事例一覧', 'astrea-core' ),
		icon: 'portfolio',
		category: 'widgets',
		description: __( 'ASTREA Core — 対応事例の一覧を表示します。', 'astrea-core' ),
		attributes: listAttributes,
	} );

	registerDynamicBlock( 'astrea/results-list', {
		title: __( '実績一覧', 'astrea-core' ),
		icon: 'chart-bar',
		category: 'widgets',
		description: __( 'ASTREA Core — 実績の一覧を表示します。', 'astrea-core' ),
		attributes: headingAndEmpty,
	} );

	registerDynamicBlock( 'astrea/voice-list', {
		title: __( 'お客様の声一覧', 'astrea-core' ),
		icon: 'testimonial',
		category: 'widgets',
		description: __( 'ASTREA Core — お客様の声の一覧を表示します。', 'astrea-core' ),
		attributes: listAttributes,
	} );

	registerDynamicBlock( 'astrea/service-list', {
		title: __( '取扱業務一覧', 'astrea-core' ),
		icon: 'list-view',
		category: 'widgets',
		description: __( 'ASTREA Core — 取扱業務の一覧を表示します。', 'astrea-core' ),
		attributes: listAttributes,
	} );

	registerDynamicBlock( 'astrea/professional-field', {
		title: __( '専門家プロフィール項目', 'astrea-core' ),
		icon: 'id',
		category: 'widgets',
		description: __( 'ASTREA Core — 現在の専門家プロフィールの1項目（資格・経歴等）を表示します。', 'astrea-core' ),
		attributes: {
			field: { type: 'string', default: '' },
			label: { type: 'string', default: '' },
		},
	} );

	registerDynamicBlock( 'astrea/office-hours', {
		title: __( '営業時間', 'astrea-core' ),
		icon: 'clock',
		category: 'widgets',
		description: __( 'ASTREA Core — 事務所の営業時間を表示します。', 'astrea-core' ),
		attributes: headingAndEmpty,
	} );

	registerDynamicBlock( 'astrea/office-sns', {
		title: __( 'SNSリンク', 'astrea-core' ),
		icon: 'share',
		category: 'widgets',
		description: __( 'ASTREA Core — 事務所のSNSリンク一覧を表示します。', 'astrea-core' ),
		attributes: headingAndEmpty,
	} );

	registerDynamicBlock( 'astrea/breadcrumb', {
		title: __( 'パンくずリスト', 'astrea-core' ),
		icon: 'admin-links',
		category: 'widgets',
		description: __( 'ASTREA Core — 現在のページの位置を示すパンくずリストを表示します。', 'astrea-core' ),
		attributes: {},
	} );

	registerDynamicBlock( 'astrea/contact-form', {
		title: __( 'お問い合わせフォーム', 'astrea-core' ),
		icon: 'email',
		category: 'widgets',
		description: __( 'ASTREA Core — お問い合わせフォームを表示します。', 'astrea-core' ),
		attributes: {},
	} );

	registerDynamicBlock( 'astrea/closing-cta', {
		title: __( 'お問い合わせへのご案内', 'astrea-core' ),
		icon: 'megaphone',
		category: 'widgets',
		description: __( 'ASTREA Core — ページ末尾のお問い合わせ導線を表示します。', 'astrea-core' ),
		attributes: {
			heading: { type: 'string', default: '' },
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.i18n );
