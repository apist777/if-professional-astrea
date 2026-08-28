<?php
/**
 * Contact Form — Dynamic Block + submission handling (Construction Order 005).
 *
 * Per the order's explicit instruction ("Themeへフォーム処理ロジックを持たせず、
 * Core側を責任主体としてください"), this single Core-owned Dynamic Block
 * (`astrea/contact-form`) owns the ENTIRE form lifecycle — rendering the
 * fields, showing validation errors, showing the success state, and
 * handling the POST — exactly like astrea/price-list (Construction Order
 * 004) reused Core's public API from a Dynamic Block rather than Query
 * Loop. Theme only places it via theme/patterns/contact-form.php.
 *
 * Submission flow (Decision 004's required order):
 *   Nonce/Honeypot/Rate-Limit check -> Validation -> Inquiry\create()
 *   (save) -> notify_new_inquiry() (best-effort) -> redirect.
 * A mail failure can never un-save an already-created inquiry, because
 * notification only runs after create() has already returned.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Inquiry;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const SUBMIT_ACTION  = 'astrea_submit_inquiry';
const NONCE_ACTION   = 'astrea_contact_form_submit';
const NONCE_FIELD    = 'astrea_contact_nonce';
const HONEYPOT_FIELD = 'astrea_contact_website';

/** Rate limit thresholds (research doc §1.6 — implementation judgment, not spec-mandated). */
const RATE_LIMIT_MIN_INTERVAL = 20; // seconds between submissions from the same sender.
const RATE_LIMIT_MAX_PER_HOUR = 5;

/** TTL for the one-time "retain submitted values on validation error" transient. */
const RETRY_STATE_TTL = 5 * MINUTE_IN_SECONDS;

add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Registers the astrea/contact-form Dynamic Block.
 *
 * @return void
 */
function register_block() {
	register_block_type(
		'astrea/contact-form',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_contact_form_block',
			'attributes'      => array(),
		)
	);
}

/**
 * Renders the success message, or the form (optionally with validation
 * errors and retained input) depending on the current request's query
 * args. Reads only — never writes anything.
 *
 * @return string
 */
function render_contact_form_block(): string {
	if ( isset( $_GET['astrea_contact_success'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag, no state change.
		return '<div class="wp-block-astrea-contact-form" role="status"><p>' .
			esc_html__( 'お問い合わせいただきありがとうございます。内容を確認のうえ、ご連絡いたします。', 'astrea-core' ) .
			'</p></div>';
	}

	$values = array(
		'name'    => '',
		'email'   => '',
		'phone'   => '',
		'subject' => '',
		'message' => '',
	);
	$errors = array();

	if ( isset( $_GET['astrea_contact_error'], $_GET['astrea_contact_retry'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, the retry token itself is the one-time credential.
		$retry_token = sanitize_text_field( wp_unslash( $_GET['astrea_contact_retry'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, the retry token itself is the one-time credential.
		$state       = get_transient( retry_transient_key( $retry_token ) );

		if ( is_array( $state ) ) {
			$values = array_merge( $values, $state['values'] ?? array() );
			$errors = $state['errors'] ?? array();
			delete_transient( retry_transient_key( $retry_token ) ); // Single use.
		}
	}

	return render_form( $values, $errors );
}

/**
 * Builds the Transient key for a retry token.
 *
 * @param string $token Retry token.
 * @return string
 */
function retry_transient_key( string $token ): string {
	return 'astrea_contact_retry_' . md5( $token );
}

/**
 * Renders the form markup.
 *
 * @param array $values Retained field values (empty strings by default).
 * @param array $errors Field key => error message.
 * @return string
 */
function render_form( array $values, array $errors ): string {
	$settings           = get_contact_settings();
	$show_privacy_field = is_privacy_consent_required();
	$current_url        = get_permalink();
	$current_url        = $current_url ? $current_url : home_url( '/' );

	ob_start();
	?>
	<form class="wp-block-astrea-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
		<?php if ( ! empty( $errors ) ) : ?>
			<div class="astrea-contact-form__errors" role="alert">
				<p>
					<?php
					echo isset( $errors['_form'] )
						? esc_html( $errors['_form'] )
						: esc_html__( '入力内容をご確認ください。', 'astrea-core' );
					?>
				</p>
			</div>
		<?php endif; ?>

		<input type="hidden" name="action" value="<?php echo esc_attr( SUBMIT_ACTION ); ?>" />
		<input type="hidden" name="astrea_contact_redirect" value="<?php echo esc_url( untrailingslashit( $current_url ) ); ?>" />
		<?php wp_nonce_field( NONCE_ACTION, NONCE_FIELD ); ?>

		<p class="astrea-contact-form__honeypot" aria-hidden="true" style="position:absolute;left:-9999px;">
			<label for="<?php echo esc_attr( HONEYPOT_FIELD ); ?>"><?php esc_html_e( 'ウェブサイト（入力しないでください）', 'astrea-core' ); ?></label>
			<input type="text" id="<?php echo esc_attr( HONEYPOT_FIELD ); ?>" name="<?php echo esc_attr( HONEYPOT_FIELD ); ?>" value="" tabindex="-1" autocomplete="off" />
		</p>

		<p>
			<label for="astrea_contact_name"><?php esc_html_e( 'お名前', 'astrea-core' ); ?> <span aria-hidden="true">*</span></label><br />
			<input
				type="text"
				id="astrea_contact_name"
				name="name"
				value="<?php echo esc_attr( $values['name'] ); ?>"
				required
				aria-required="true"
				<?php echo isset( $errors['name'] ) ? 'aria-describedby="astrea_contact_name_error" aria-invalid="true"' : ''; ?>
			/>
			<?php if ( isset( $errors['name'] ) ) : ?>
				<span id="astrea_contact_name_error" class="astrea-contact-form__field-error"><?php echo esc_html( $errors['name'] ); ?></span>
			<?php endif; ?>
		</p>

		<p>
			<label for="astrea_contact_email"><?php esc_html_e( 'メールアドレス', 'astrea-core' ); ?> <span aria-hidden="true">*</span></label><br />
			<input
				type="email"
				id="astrea_contact_email"
				name="email"
				value="<?php echo esc_attr( $values['email'] ); ?>"
				required
				aria-required="true"
				<?php echo isset( $errors['email'] ) ? 'aria-describedby="astrea_contact_email_error" aria-invalid="true"' : ''; ?>
			/>
			<?php if ( isset( $errors['email'] ) ) : ?>
				<span id="astrea_contact_email_error" class="astrea-contact-form__field-error"><?php echo esc_html( $errors['email'] ); ?></span>
			<?php endif; ?>
		</p>

		<?php if ( $settings['phone_enabled'] ) : ?>
			<p>
				<label for="astrea_contact_phone">
					<?php esc_html_e( '電話番号', 'astrea-core' ); ?>
					<?php echo $settings['phone_required'] ? '<span aria-hidden="true">*</span>' : '（' . esc_html__( '任意', 'astrea-core' ) . '）'; ?>
				</label><br />
				<input
					type="tel"
					id="astrea_contact_phone"
					name="phone"
					value="<?php echo esc_attr( $values['phone'] ); ?>"
					<?php echo $settings['phone_required'] ? 'required aria-required="true"' : ''; ?>
					<?php echo isset( $errors['phone'] ) ? 'aria-describedby="astrea_contact_phone_error" aria-invalid="true"' : ''; ?>
				/>
				<?php if ( isset( $errors['phone'] ) ) : ?>
					<span id="astrea_contact_phone_error" class="astrea-contact-form__field-error"><?php echo esc_html( $errors['phone'] ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php if ( $settings['subject_enabled'] ) : ?>
			<p>
				<label for="astrea_contact_subject">
					<?php esc_html_e( '件名', 'astrea-core' ); ?>
					<?php echo $settings['subject_required'] ? '<span aria-hidden="true">*</span>' : '（' . esc_html__( '任意', 'astrea-core' ) . '）'; ?>
				</label><br />
				<input
					type="text"
					id="astrea_contact_subject"
					name="subject"
					value="<?php echo esc_attr( $values['subject'] ); ?>"
					<?php echo $settings['subject_required'] ? 'required aria-required="true"' : ''; ?>
					<?php echo isset( $errors['subject'] ) ? 'aria-describedby="astrea_contact_subject_error" aria-invalid="true"' : ''; ?>
				/>
				<?php if ( isset( $errors['subject'] ) ) : ?>
					<span id="astrea_contact_subject_error" class="astrea-contact-form__field-error"><?php echo esc_html( $errors['subject'] ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<p>
			<label for="astrea_contact_message"><?php esc_html_e( 'お問い合わせ内容', 'astrea-core' ); ?> <span aria-hidden="true">*</span></label><br />
			<textarea
				id="astrea_contact_message"
				name="message"
				rows="6"
				required
				aria-required="true"
				<?php echo isset( $errors['message'] ) ? 'aria-describedby="astrea_contact_message_error" aria-invalid="true"' : ''; ?>
			><?php echo esc_textarea( $values['message'] ); ?></textarea>
			<?php if ( isset( $errors['message'] ) ) : ?>
				<span id="astrea_contact_message_error" class="astrea-contact-form__field-error"><?php echo esc_html( $errors['message'] ); ?></span>
			<?php endif; ?>
		</p>

		<?php if ( $show_privacy_field ) : ?>
			<p>
				<label for="astrea_contact_privacy_consent">
					<input
						type="checkbox"
						id="astrea_contact_privacy_consent"
						name="privacy_consent"
						value="1"
						required
						aria-required="true"
						<?php echo isset( $errors['privacy_consent'] ) ? 'aria-describedby="astrea_contact_privacy_error" aria-invalid="true"' : ''; ?>
					/>
					<?php
					printf(
						/* translators: %s: Privacy Policy page URL */
						esc_html__( '%sに同意する', 'astrea-core' ),
						'<a href="' . esc_url( get_privacy_policy_url() ) . '">' . esc_html__( 'プライバシーポリシー', 'astrea-core' ) . '</a>'
					);
					?>
				</label>
				<?php if ( isset( $errors['privacy_consent'] ) ) : ?>
					<br /><span id="astrea_contact_privacy_error" class="astrea-contact-form__field-error"><?php echo esc_html( $errors['privacy_consent'] ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<p>
			<button type="submit" class="wp-element-button"><?php esc_html_e( '送信する', 'astrea-core' ); ?></button>
		</p>
	</form>
	<?php
	return (string) ob_get_clean();
}

add_action( 'admin_post_' . SUBMIT_ACTION, __NAMESPACE__ . '\\handle_submit' );
add_action( 'admin_post_nopriv_' . SUBMIT_ACTION, __NAMESPACE__ . '\\handle_submit' );

/**
 * Handles the form POST.
 *
 * @return void
 */
function handle_submit() {
	$redirect_base = isset( $_POST['astrea_contact_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['astrea_contact_redirect'] ) ) : home_url( '/' );

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ NONCE_FIELD ] ?? '' ) ), NONCE_ACTION ) ) {
		redirect_with_errors( $redirect_base, array(), array( '_form' => __( 'フォームの有効期限が切れました。もう一度お試しください。', 'astrea-core' ) ) );
	}

	// Honeypot: a real visitor never fills this hidden field. Pretend success
	// and silently drop — not a "vague spam judgment" against already-stored
	// data (Decision 007), but a pre-save binary signal (research doc §1.5).
	$honeypot_value = isset( $_POST[ HONEYPOT_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ HONEYPOT_FIELD ] ) ) : '';

	if ( '' !== trim( $honeypot_value ) ) {
		wp_safe_redirect( add_query_arg( 'astrea_contact_success', '1', $redirect_base ) );
		exit;
	}

	if ( is_rate_limited() ) {
		redirect_with_errors(
			$redirect_base,
			collect_submitted_values(),
			array( '_form' => __( '送信回数が多すぎます。しばらくしてから再度お試しください。', 'astrea-core' ) )
		);
	}

	$values = collect_submitted_values();
	$errors = validate( $values );

	if ( ! empty( $errors ) ) {
		redirect_with_errors( $redirect_base, $values, $errors );
	}

	record_rate_limit();

	$post_id = create( $values );

	if ( $post_id > 0 ) {
		notify_new_inquiry( $post_id );
	}

	wp_safe_redirect( add_query_arg( 'astrea_contact_success', '1', $redirect_base ) );
	exit;
}

/**
 * Reads and lightly sanitizes raw $_POST values (full sanitization happens
 * again in create(); this copy is only for validation + error-state
 * retention).
 *
 * @return array
 */
function collect_submitted_values(): array {
	return array(
		'name'            => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified by the caller before this runs.
		'email'           => sanitize_text_field( wp_unslash( $_POST['email'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'phone'           => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'subject'         => sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'message'         => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'privacy_consent' => ! empty( $_POST['privacy_consent'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
	);
}

/**
 * Validates submitted values against the current field settings.
 *
 * @param array $values collect_submitted_values() output.
 * @return array Field key => error message. Empty when valid.
 */
function validate( array $values ): array {
	$settings = get_contact_settings();
	$errors   = array();

	if ( '' === $values['name'] ) {
		$errors['name'] = __( 'お名前を入力してください。', 'astrea-core' );
	}

	if ( '' === $values['email'] ) {
		$errors['email'] = __( 'メールアドレスを入力してください。', 'astrea-core' );
	} elseif ( ! is_email( $values['email'] ) ) {
		$errors['email'] = __( '正しいメールアドレスを入力してください。', 'astrea-core' );
	}

	if ( $settings['phone_required'] && '' === $values['phone'] ) {
		$errors['phone'] = __( '電話番号を入力してください。', 'astrea-core' );
	}

	if ( $settings['subject_required'] && '' === $values['subject'] ) {
		$errors['subject'] = __( '件名を入力してください。', 'astrea-core' );
	}

	if ( '' === $values['message'] ) {
		$errors['message'] = __( 'お問い合わせ内容を入力してください。', 'astrea-core' );
	} elseif ( mb_strlen( $values['message'] ) > MESSAGE_MAX_LENGTH ) {
		$errors['message'] = sprintf(
			/* translators: %d: maximum character count */
			__( 'お問い合わせ内容は%d文字以内で入力してください。', 'astrea-core' ),
			MESSAGE_MAX_LENGTH
		);
	}

	if ( is_privacy_consent_required() && ! $values['privacy_consent'] ) {
		$errors['privacy_consent'] = __( 'プライバシーポリシーへの同意が必要です。', 'astrea-core' );
	}

	return $errors;
}

/**
 * Stores validation errors + retained values in a one-time Transient and
 * redirects back to the form with only a token in the URL — never the
 * actual submitted values or error text (Privacy: nothing personal in the
 * URL/server logs).
 *
 * @param string $redirect_base Base URL to redirect back to.
 * @param array  $values        Retained field values.
 * @param array  $errors        Field key => error message.
 * @return never
 */
function redirect_with_errors( string $redirect_base, array $values, array $errors ) {
	$token = wp_generate_password( 32, false );

	set_transient(
		retry_transient_key( $token ),
		array(
			'values' => $values,
			'errors' => $errors,
		),
		RETRY_STATE_TTL
	);

	$redirect = add_query_arg(
		array(
			'astrea_contact_error' => '1',
			'astrea_contact_retry' => $token,
		),
		$redirect_base
	);

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Rate-limit key: a hash of the submitter's IP. The IP itself is never
 * stored — only its hash, only inside short-lived Transients, never
 * attached to the Inquiry record (research doc §1.7).
 *
 * @return string
 */
function rate_limit_ip_hash(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return hash( 'sha256', $ip . wp_salt() );
}

/**
 * Whether the current sender has exceeded the submission rate limit.
 *
 * @return bool
 */
function is_rate_limited(): bool {
	$hash = rate_limit_ip_hash();

	if ( false !== get_transient( 'astrea_contact_rl_min_' . $hash ) ) {
		return true; // Too soon since the last submission.
	}

	$count = (int) get_transient( 'astrea_contact_rl_hour_' . $hash );

	return $count >= RATE_LIMIT_MAX_PER_HOUR;
}

/**
 * Records a successful submission for rate-limiting purposes.
 *
 * @return void
 */
function record_rate_limit() {
	$hash = rate_limit_ip_hash();

	set_transient( 'astrea_contact_rl_min_' . $hash, true, RATE_LIMIT_MIN_INTERVAL );

	$count = (int) get_transient( 'astrea_contact_rl_hour_' . $hash );
	set_transient( 'astrea_contact_rl_hour_' . $hash, $count + 1, HOUR_IN_SECONDS );
}
