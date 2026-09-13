<?php
/**
 * Office Profile — admin screen.
 *
 * A single, standard WordPress Settings API screen. Deliberately has no
 * custom JavaScript (repeaters use a fixed number of slots instead of a
 * dynamic add/remove UI) — see Construction Order 002 §3: "過剰な
 * JavaScript UIや独自管理Frameworkは導入しないでください".
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\OfficeProfile\Admin;

use function Astrea\Core\OfficeProfile\get_office_profile;
use function Astrea\Core\OfficeProfile\weekday_label;
use function Astrea\Core\ProfessionalProfile\get_representatives;
use const Astrea\Core\OfficeProfile\OPTION_NAME;
use const Astrea\Core\OfficeProfile\SETTINGS_GROUP;
use const Astrea\Core\OfficeProfile\WEEKDAYS;
use const Astrea\Core\OfficeProfile\LEGACY_REPRESENTATIVE_NAME_KEY;
use const Astrea\Core\ProfessionalProfile\POST_TYPE as PROFESSIONAL_PROFILE_POST_TYPE;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const PAGE_SLUG = 'astrea-core';

/** Fixed number of repeater rows rendered for closures / SNS links. */
const EXCEPTION_ROWS = 5;
const SNS_ROWS       = 5;

add_action( 'admin_menu', __NAMESPACE__ . '\\add_menu' );

/**
 * Registers the top-level ASTREA admin menu and its Office Profile page.
 *
 * The Office Profile page is the same screen it has always been (same
 * slug, same callback, same option) — Construction 029-CG only gives its
 * own submenu entry an explicit, readable label ("事務所情報") instead of
 * inheriting the top-level menu's own label ("ASTREA"), so a site owner
 * scanning the ASTREA submenu can tell at a glance where to edit the
 * office name/address/phone/hours, without creating a second page or a
 * second option (see docs/research/2026-09-13_construction_029cg_*.md).
 *
 * @return void
 */
function add_menu() {
	add_menu_page(
		__( 'ASTREA', 'astrea-core' ),
		__( 'ASTREA', 'astrea-core' ),
		'manage_options',
		PAGE_SLUG,
		__NAMESPACE__ . '\\render_page',
		'dashicons-building',
		59
	);

	add_submenu_page(
		PAGE_SLUG,
		__( '事務所情報', 'astrea-core' ),
		__( '事務所情報', 'astrea-core' ),
		'manage_options',
		PAGE_SLUG,
		__NAMESPACE__ . '\\render_page'
	);

	move_office_information_to_top();
}

/**
 * Moves the "事務所情報" submenu entry to the top of the ASTREA submenu.
 *
 * WordPress core populates $submenu['astrea-core'] with every ASTREA
 * Custom Post Type's list-table entry (専門家プロフィール一覧 etc.) before
 * the `admin_menu` action fires at all, so add_submenu_page() above always
 * appends after them regardless of hook priority. There is no public API
 * to control submenu order, so — matching common WordPress plugin
 * practice — this reorders the array directly. Construction 029-CG:
 * Office Information is the first setting a site owner touches after
 * installing ASTREA, so it belongs above the content list tables.
 *
 * @return void
 */
function move_office_information_to_top() {
	global $submenu;

	if ( empty( $submenu[ PAGE_SLUG ] ) || ! is_array( $submenu[ PAGE_SLUG ] ) ) {
		return;
	}

	$target_index = null;
	foreach ( $submenu[ PAGE_SLUG ] as $index => $item ) {
		if ( isset( $item[2] ) && PAGE_SLUG === $item[2] ) {
			$target_index = $index;
			break;
		}
	}

	if ( null === $target_index || 0 === $target_index ) {
		return;
	}

	$entry = $submenu[ PAGE_SLUG ][ $target_index ];
	unset( $submenu[ PAGE_SLUG ][ $target_index ] );
	array_unshift( $submenu[ PAGE_SLUG ], $entry );
	// Re-index after array_unshift() — this reorders WordPress's own
	// $submenu global (there is no public API for submenu ordering), it
	// does not replace it with unrelated data.
	$submenu[ PAGE_SLUG ] = array_values( $submenu[ PAGE_SLUG ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}

add_action( 'admin_init', __NAMESPACE__ . '\\register_fields' );

/**
 * Registers the settings section and fields shown on the Office Profile page.
 *
 * @return void
 */
function register_fields() {
	add_settings_section(
		'astrea_core_office_profile_basic',
		__( '基本情報', 'astrea-core' ),
		'__return_false',
		PAGE_SLUG
	);

	add_settings_field(
		'astrea_core_office_name',
		__( '事務所名', 'astrea-core' ),
		__NAMESPACE__ . '\\field_office_name',
		PAGE_SLUG,
		'astrea_core_office_profile_basic'
	);

	add_settings_section(
		'astrea_core_office_profile_location',
		__( '所在地', 'astrea-core' ),
		__NAMESPACE__ . '\\section_location_description',
		PAGE_SLUG
	);

	add_settings_field(
		'astrea_core_address',
		__( '住所（サイト表示に使用中）', 'astrea-core' ),
		__NAMESPACE__ . '\\field_address',
		PAGE_SLUG,
		'astrea_core_office_profile_location'
	);

	add_settings_field(
		'astrea_core_postal_code',
		__( '郵便番号', 'astrea-core' ),
		__NAMESPACE__ . '\\field_postal_code',
		PAGE_SLUG,
		'astrea_core_office_profile_location'
	);

	add_settings_field(
		'astrea_core_prefecture',
		__( '都道府県', 'astrea-core' ),
		__NAMESPACE__ . '\\field_prefecture',
		PAGE_SLUG,
		'astrea_core_office_profile_location'
	);

	add_settings_field(
		'astrea_core_address_line',
		__( '市区町村・番地', 'astrea-core' ),
		__NAMESPACE__ . '\\field_address_line',
		PAGE_SLUG,
		'astrea_core_office_profile_location'
	);

	add_settings_field(
		'astrea_core_building',
		__( '建物名・部屋番号', 'astrea-core' ),
		__NAMESPACE__ . '\\field_building',
		PAGE_SLUG,
		'astrea_core_office_profile_location'
	);

	add_settings_section(
		'astrea_core_office_profile_contact',
		__( '連絡先', 'astrea-core' ),
		'__return_false',
		PAGE_SLUG
	);

	add_settings_field(
		'astrea_core_phone',
		__( '電話番号', 'astrea-core' ),
		__NAMESPACE__ . '\\field_phone',
		PAGE_SLUG,
		'astrea_core_office_profile_contact'
	);

	add_settings_field(
		'astrea_core_fax',
		__( 'FAX番号', 'astrea-core' ),
		__NAMESPACE__ . '\\field_fax',
		PAGE_SLUG,
		'astrea_core_office_profile_contact'
	);

	add_settings_section(
		'astrea_core_office_profile_hours',
		__( '営業情報', 'astrea-core' ),
		'__return_false',
		PAGE_SLUG
	);

	add_settings_field(
		'astrea_core_business_hours_weekly',
		__( '営業時間・定休日', 'astrea-core' ),
		__NAMESPACE__ . '\\field_weekly_hours',
		PAGE_SLUG,
		'astrea_core_office_profile_hours'
	);

	add_settings_field(
		'astrea_core_business_hours_exceptions',
		__( '臨時休業・年末年始・夏季休業など', 'astrea-core' ),
		__NAMESPACE__ . '\\field_exceptions',
		PAGE_SLUG,
		'astrea_core_office_profile_hours'
	);

	add_settings_field(
		'astrea_core_service_area',
		__( '対応エリア', 'astrea-core' ),
		__NAMESPACE__ . '\\field_service_area',
		PAGE_SLUG,
		'astrea_core_office_profile_hours'
	);

	add_settings_section(
		'astrea_core_office_profile_sns',
		__( 'SNS', 'astrea-core' ),
		'__return_false',
		PAGE_SLUG
	);

	add_settings_field(
		'astrea_core_sns_links',
		__( 'SNSリンク', 'astrea-core' ),
		__NAMESPACE__ . '\\field_sns_links',
		PAGE_SLUG,
		'astrea_core_office_profile_sns'
	);
}

/**
 * Description shown under the "所在地" section heading, explaining why a
 * newly added, structured address breakdown exists alongside the
 * pre-existing single-line address field without yet changing the site.
 *
 * @return void
 */
function section_location_description() {
	?>
	<p class="description">
		<?php esc_html_e( '「住所」は現在サイトの表示に使われている項目です。郵便番号・都道府県・市区町村番地・建物名は今回新しく追加した項目で、将来の機能拡張のために分けて保存しますが、現時点ではまだサイト表示には反映されません。', 'astrea-core' ); ?>
	</p>
	<?php
}

/**
 * Renders the Office Profile admin page.
 *
 * @return void
 */
function render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'この画面を表示する権限がありません。', 'astrea-core' ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( '事務所情報', 'astrea-core' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'ここで入力した情報は、テーマの表示（ヘッダー・フッター等）から共通で利用されます。すべての項目は任意です。未入力のまま公開しても問題ありません。', 'astrea-core' ); ?>
		</p>
		<?php
		/**
		 * Fires near the top of the ASTREA Office Profile page, before the
		 * settings form. Used by includes/setup-admin.php (Construction
		 * Order 007) to render the setup checklist without coupling this
		 * file to the Setup module directly.
		 */
		do_action( 'astrea_core_office_profile_page_top' );
		?>
		<?php settings_errors( OPTION_NAME ); ?>
		<form method="post" action="options.php" novalidate="novalidate">
			<?php
			settings_fields( SETTINGS_GROUP );
			do_settings_sections( PAGE_SLUG );
			submit_button( __( '事務所情報を保存', 'astrea-core' ) );
			?>
		</form>
		<p class="description">
			<?php
			printf(
				wp_kses(
					/* translators: %s: URL to the Professional Profile list admin screen */
					__( '代表者名・肩書・所有資格・プロフィール・代表者写真は<a href="%s">専門家プロフィール</a>から編集できます。', 'astrea-core' ),
					array( 'a' => array( 'href' => array() ) )
				),
				esc_url( admin_url( 'edit.php?post_type=' . PROFESSIONAL_PROFILE_POST_TYPE ) )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Office name field.
 *
 * @return void
 */
function field_office_name() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_office_name"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[office_name]"
		value="<?php echo esc_attr( $profile['office_name'] ); ?>"
		class="regular-text"
	/>
	<?php
}

/**
 * Address field.
 *
 * @return void
 */
function field_address() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_address"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[address]"
		value="<?php echo esc_attr( $profile['address'] ); ?>"
		class="regular-text"
	/>
	<?php
}

/**
 * Postal code field.
 *
 * @return void
 */
function field_postal_code() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_postal_code"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[postal_code]"
		value="<?php echo esc_attr( $profile['postal_code'] ); ?>"
		class="regular-text"
		autocomplete="postal-code"
	/>
	<p class="description"><?php esc_html_e( '例: 100-0001', 'astrea-core' ); ?></p>
	<?php
}

/**
 * Prefecture field.
 *
 * @return void
 */
function field_prefecture() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_prefecture"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[prefecture]"
		value="<?php echo esc_attr( $profile['prefecture'] ); ?>"
		class="regular-text"
	/>
	<p class="description"><?php esc_html_e( '例: 東京都', 'astrea-core' ); ?></p>
	<?php
}

/**
 * Address line (city/street/number) field.
 *
 * @return void
 */
function field_address_line() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_address_line"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[address_line]"
		value="<?php echo esc_attr( $profile['address_line'] ); ?>"
		class="regular-text"
	/>
	<p class="description"><?php esc_html_e( '例: 千代田区千代田1-1', 'astrea-core' ); ?></p>
	<?php
}

/**
 * Building name / room number field.
 *
 * @return void
 */
function field_building() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_building"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[building]"
		value="<?php echo esc_attr( $profile['building'] ); ?>"
		class="regular-text"
	/>
	<p class="description"><?php esc_html_e( '例: ASTREAビル 3F（任意）', 'astrea-core' ); ?></p>
	<?php
}

/**
 * Phone field.
 *
 * @return void
 */
function field_phone() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_phone"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[phone]"
		value="<?php echo esc_attr( $profile['phone'] ); ?>"
		class="regular-text"
		autocomplete="tel"
	/>
	<p class="description"><?php esc_html_e( '例: 03-1234-5678', 'astrea-core' ); ?></p>
	<?php
}

/**
 * Fax field.
 *
 * @return void
 */
function field_fax() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_fax"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[fax]"
		value="<?php echo esc_attr( $profile['fax'] ); ?>"
		class="regular-text"
		autocomplete="fax"
	/>
	<p class="description"><?php esc_html_e( '例: 03-1234-5679（任意）', 'astrea-core' ); ?></p>
	<?php
}

/**
 * Weekly business hours table field.
 *
 * @return void
 */
function field_weekly_hours() {
	$profile = get_office_profile();
	$weekly  = $profile['business_hours']['weekly'];
	?>
	<table class="widefat astrea-core-weekly-hours" style="max-width: 640px;">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( '曜日', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( '定休日', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( '開始', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( '終了', 'astrea-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( WEEKDAYS as $day ) : ?>
				<?php $row = $weekly[ $day ]; ?>
				<tr>
					<th scope="row"><?php echo esc_html( weekday_label( $day ) ); ?></th>
					<td>
						<label class="screen-reader-text" for="astrea_core_hours_<?php echo esc_attr( $day ); ?>_closed">
							<?php
							printf(
								/* translators: %s: weekday label */
								esc_html__( '%sを定休日にする', 'astrea-core' ),
								esc_html( weekday_label( $day ) )
							);
							?>
						</label>
						<input
							type="checkbox"
							id="astrea_core_hours_<?php echo esc_attr( $day ); ?>_closed"
							name="<?php echo esc_attr( OPTION_NAME ); ?>[business_hours][weekly][<?php echo esc_attr( $day ); ?>][closed]"
							value="1"
							<?php checked( $row['closed'] ); ?>
						/>
					</td>
					<td>
						<label class="screen-reader-text" for="astrea_core_hours_<?php echo esc_attr( $day ); ?>_open">
							<?php
							printf(
								/* translators: %s: weekday label */
								esc_html__( '%sの開始時刻', 'astrea-core' ),
								esc_html( weekday_label( $day ) )
							);
							?>
						</label>
						<input
							type="time"
							id="astrea_core_hours_<?php echo esc_attr( $day ); ?>_open"
							name="<?php echo esc_attr( OPTION_NAME ); ?>[business_hours][weekly][<?php echo esc_attr( $day ); ?>][open]"
							value="<?php echo esc_attr( $row['open'] ); ?>"
						/>
					</td>
					<td>
						<label class="screen-reader-text" for="astrea_core_hours_<?php echo esc_attr( $day ); ?>_close">
							<?php
							printf(
								/* translators: %s: weekday label */
								esc_html__( '%sの終了時刻', 'astrea-core' ),
								esc_html( weekday_label( $day ) )
							);
							?>
						</label>
						<input
							type="time"
							id="astrea_core_hours_<?php echo esc_attr( $day ); ?>_close"
							name="<?php echo esc_attr( OPTION_NAME ); ?>[business_hours][weekly][<?php echo esc_attr( $day ); ?>][close]"
							value="<?php echo esc_attr( $row['close'] ); ?>"
						/>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Closure exceptions repeater field (fixed number of rows, no JS).
 *
 * @return void
 */
function field_exceptions() {
	$profile    = get_office_profile();
	$exceptions = $profile['business_hours']['exceptions'];
	?>
	<table class="widefat astrea-core-exceptions" style="max-width: 720px;">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( '名称（例：年末年始）', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( '開始日', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( '終了日', 'astrea-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php for ( $i = 0; $i < EXCEPTION_ROWS; $i++ ) : ?>
				<?php
				$row = $exceptions[ $i ] ?? array(
					'label'      => '',
					'start_date' => '',
					'end_date'   => '',
				);
				?>
				<tr>
					<td>
						<label class="screen-reader-text" for="astrea_core_exception_<?php echo esc_attr( $i ); ?>_label">
							<?php esc_html_e( '休業名称', 'astrea-core' ); ?>
						</label>
						<input
							type="text"
							id="astrea_core_exception_<?php echo esc_attr( $i ); ?>_label"
							name="<?php echo esc_attr( OPTION_NAME ); ?>[business_hours][exceptions][<?php echo esc_attr( $i ); ?>][label]"
							value="<?php echo esc_attr( $row['label'] ); ?>"
							class="regular-text"
						/>
					</td>
					<td>
						<label class="screen-reader-text" for="astrea_core_exception_<?php echo esc_attr( $i ); ?>_start">
							<?php esc_html_e( '開始日', 'astrea-core' ); ?>
						</label>
						<input
							type="date"
							id="astrea_core_exception_<?php echo esc_attr( $i ); ?>_start"
							name="<?php echo esc_attr( OPTION_NAME ); ?>[business_hours][exceptions][<?php echo esc_attr( $i ); ?>][start_date]"
							value="<?php echo esc_attr( $row['start_date'] ); ?>"
						/>
					</td>
					<td>
						<label class="screen-reader-text" for="astrea_core_exception_<?php echo esc_attr( $i ); ?>_end">
							<?php esc_html_e( '終了日', 'astrea-core' ); ?>
						</label>
						<input
							type="date"
							id="astrea_core_exception_<?php echo esc_attr( $i ); ?>_end"
							name="<?php echo esc_attr( OPTION_NAME ); ?>[business_hours][exceptions][<?php echo esc_attr( $i ); ?>][end_date]"
							value="<?php echo esc_attr( $row['end_date'] ); ?>"
						/>
					</td>
				</tr>
			<?php endfor; ?>
		</tbody>
	</table>
	<p class="description"><?php esc_html_e( '使わない行は空欄のままで構いません。', 'astrea-core' ); ?></p>
	<?php
}

/**
 * Service area field (free text, e.g. a list of prefectures covered).
 *
 * @return void
 */
function field_service_area() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_service_area"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[service_area]"
		value="<?php echo esc_attr( $profile['service_area'] ); ?>"
		class="regular-text"
	/>
	<p class="description"><?php esc_html_e( '例: 東京都・神奈川県・埼玉県・千葉県（任意）', 'astrea-core' ); ?></p>
	<?php
}

/**
 * SNS links repeater field (fixed number of rows, no JS).
 *
 * @return void
 */
function field_sns_links() {
	$profile = get_office_profile();
	$links   = $profile['sns_links'];
	?>
	<table class="widefat astrea-core-sns-links" style="max-width: 720px;">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( '名称（例：X、Instagram）', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'URL', 'astrea-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php for ( $i = 0; $i < SNS_ROWS; $i++ ) : ?>
				<?php
				$row = $links[ $i ] ?? array(
					'label' => '',
					'url'   => '',
				);
				?>
				<tr>
					<td>
						<label class="screen-reader-text" for="astrea_core_sns_<?php echo esc_attr( $i ); ?>_label">
							<?php esc_html_e( 'SNS名称', 'astrea-core' ); ?>
						</label>
						<input
							type="text"
							id="astrea_core_sns_<?php echo esc_attr( $i ); ?>_label"
							name="<?php echo esc_attr( OPTION_NAME ); ?>[sns_links][<?php echo esc_attr( $i ); ?>][label]"
							value="<?php echo esc_attr( $row['label'] ); ?>"
							class="regular-text"
						/>
					</td>
					<td>
						<label class="screen-reader-text" for="astrea_core_sns_<?php echo esc_attr( $i ); ?>_url">
							<?php esc_html_e( 'SNS URL', 'astrea-core' ); ?>
						</label>
						<input
							type="url"
							id="astrea_core_sns_<?php echo esc_attr( $i ); ?>_url"
							name="<?php echo esc_attr( OPTION_NAME ); ?>[sns_links][<?php echo esc_attr( $i ); ?>][url]"
							value="<?php echo esc_attr( $row['url'] ); ?>"
							class="regular-text"
						/>
					</td>
				</tr>
			<?php endfor; ?>
		</tbody>
	</table>
	<p class="description"><?php esc_html_e( '使わない行は空欄のままで構いません。', 'astrea-core' ); ?></p>
	<?php
}

add_action( 'admin_notices', __NAMESPACE__ . '\\maybe_render_legacy_representative_notice' );

/**
 * Prompts the site owner to assign a pre-existing (schema v1) office
 * representative name to a Professional Profile (Decision 023).
 *
 * Intentionally not dismissible: it has no state of its own to persist.
 * Its visibility condition — a legacy name exists AND no Professional
 * Profile is currently flagged as representative — is exactly the
 * condition that means the migration isn't done yet, so the notice
 * disappears on its own the moment the site owner marks someone as
 * representative. No AJAX/JS dismiss handling needed.
 *
 * @return void
 */
function maybe_render_legacy_representative_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'toplevel_page_' . PAGE_SLUG, 'edit-astrea_professional', 'astrea_professional' ), true ) ) {
		return;
	}

	$legacy_name = get_office_profile()[ LEGACY_REPRESENTATIVE_NAME_KEY ] ?? '';
	if ( '' === $legacy_name ) {
		return;
	}

	if ( ! empty( get_representatives() ) ) {
		return; // Already resolved — someone is flagged as representative.
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		wp_kses(
			sprintf(
				/* translators: 1: the previously entered representative name, 2: URL to add a new Professional Profile */
				__( '以前入力されていた代表者名「%1$s」があります。この情報は現在、事務所情報ではなく専門家プロフィールで管理します。<a href="%2$s">専門家プロフィールを追加</a>し、「代表者として表示」にチェックを入れてください。', 'astrea-core' ),
				esc_html( $legacy_name ),
				esc_url( admin_url( 'post-new.php?post_type=astrea_professional' ) )
			),
			array( 'a' => array( 'href' => array() ) )
		)
	);
}
