<?php
/**
 * DreamHost SMTP for WordPress mail (contact form and site email).
 *
 * Credentials: add local-smtp.php at site root (gitignored) or WPMS_* defines in wp-config.php.
 *
 * @package OceanBreeze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load optional SMTP credentials from local-smtp.php (not in git).
 */
function ocean_breeze_mail_load_smtp_config() {
	if ( defined( 'WPMS_ON' ) ) {
		return;
	}

	$candidates = array(
		ABSPATH . 'local-smtp.php',
		dirname( get_template_directory(), 3 ) . '/local-smtp.php',
	);

	foreach ( $candidates as $path ) {
		if ( is_readable( $path ) ) {
			require_once $path;
			break;
		}
	}
}
ocean_breeze_mail_load_smtp_config();

/**
 * Whether SMTP is configured for outbound mail.
 *
 * @return bool
 */
function ocean_breeze_mail_smtp_is_configured() {
	return defined( 'WPMS_ON' )
		&& WPMS_ON
		&& defined( 'WPMS_SMTP_USER' )
		&& defined( 'WPMS_SMTP_PASS' )
		&& WPMS_SMTP_USER
		&& WPMS_SMTP_PASS;
}

/**
 * Configure PHPMailer for DreamHost SMTP when WP Mail SMTP is not handling it.
 */
function ocean_breeze_mail_phpmailer_smtp( $phpmailer ) {
	if ( ! ocean_breeze_mail_smtp_is_configured() ) {
		return;
	}

	// WP Mail SMTP plugin takes over when active.
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( is_plugin_active( 'wp-mail-smtp/wp_mail_smtp.php' ) ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host       = defined( 'WPMS_SMTP_HOST' ) ? WPMS_SMTP_HOST : 'smtp.dreamhost.com';
	$phpmailer->Port       = defined( 'WPMS_SMTP_PORT' ) ? (int) WPMS_SMTP_PORT : 587;
	$phpmailer->SMTPSecure = defined( 'WPMS_SSL' ) ? WPMS_SSL : 'tls';
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Username   = WPMS_SMTP_USER;
	$phpmailer->Password   = WPMS_SMTP_PASS;
	$phpmailer->SMTPAutoTLS = true;

	if ( defined( 'WPMS_SET_RETURN_PATH' ) && WPMS_SET_RETURN_PATH && defined( 'WPMS_MAIL_FROM' ) ) {
		$phpmailer->Sender = WPMS_MAIL_FROM;
	}
}
add_action( 'phpmailer_init', 'ocean_breeze_mail_phpmailer_smtp', 5 );

/**
 * Extra BCC recipients for contact form notifications (comma-separated constant).
 *
 * @return string[]
 */
function ocean_breeze_contact_form_bcc_emails() {
	$emails = array();

	if ( defined( 'OB_CONTACT_FORM_BCC' ) && OB_CONTACT_FORM_BCC ) {
		$parts = explode( ',', OB_CONTACT_FORM_BCC );
		foreach ( $parts as $part ) {
			$addr = sanitize_email( trim( $part ) );
			if ( $addr ) {
				$emails[] = $addr;
			}
		}
	}

	$notify = function_exists( 'ocean_breeze_contact_form_notify_email' )
		? ocean_breeze_contact_form_notify_email()
		: '';

	$admin = get_option( 'admin_email' );
	if ( $admin && is_email( $admin ) && $admin !== $notify ) {
		$emails[] = $admin;
	}

	return array_values( array_unique( $emails ) );
}
