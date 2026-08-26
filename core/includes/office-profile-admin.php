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
use const Astrea\Core\OfficeProfile\OPTION_NAME;
use const Astrea\Core\OfficeProfile\SETTINGS_GROUP;
use const Astrea\Core\OfficeProfile\WEEKDAYS;

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
		__( '事務所基本情報', 'astrea-core' ),
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

	add_settings_field(
		'astrea_core_representative_name',
		__( '代表者名', 'astrea-core' ),
		__NAMESPACE__ . '\\field_representative_name',
		PAGE_SLUG,
		'astrea_core_office_profile_basic'
	);

	add_settings_field(
		'astrea_core_address',
		__( '所在地', 'astrea-core' ),
		__NAMESPACE__ . '\\field_address',
		PAGE_SLUG,
		'astrea_core_office_profile_basic'
	);

	add_settings_field(
		'astrea_core_phone',
		__( '電話番号', 'astrea-core' ),
		__NAMESPACE__ . '\\field_phone',
		PAGE_SLUG,
		'astrea_core_office_profile_basic'
	);

	add_settings_section(
		'astrea_core_office_profile_hours',
		__( '営業時間', 'astrea-core' ),
		'__return_false',
		PAGE_SLUG
	);

	add_settings_field(
		'astrea_core_business_hours_weekly',
		__( '通常の営業時間', 'astrea-core' ),
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
		<?php settings_errors( OPTION_NAME ); ?>
		<form method="post" action="options.php" novalidate="novalidate">
			<?php
			settings_fields( SETTINGS_GROUP );
			do_settings_sections( PAGE_SLUG );
			submit_button( __( '事務所情報を保存', 'astrea-core' ) );
			?>
		</form>
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
 * Representative name field.
 *
 * @return void
 */
function field_representative_name() {
	$profile = get_office_profile();
	?>
	<input
		type="text"
		id="astrea_core_representative_name"
		name="<?php echo esc_attr( OPTION_NAME ); ?>[representative_name]"
		value="<?php echo esc_attr( $profile['representative_name'] ); ?>"
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
