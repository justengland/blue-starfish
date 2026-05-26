<?php
/**
 * Simple Contact Form with Math Captcha
 *
 * @package OceanBreeze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL for the contact page (form submissions post here).
 *
 * @return string
 */
function ocean_breeze_contact_page_url() {
	$page = get_page_by_path( 'contact' );
	if ( $page ) {
		return (string) get_permalink( $page );
	}

	return home_url( '/contact/' );
}

/**
 * Render contact form shortcode [simple_contact_form].
 *
 * @return string
 */
function ocean_breeze_contact_form_shortcode() {
	$message = '';

	if (
		'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' )
		&& isset( $_POST['ob_contact_submit'] )
	) {
		$nonce = isset( $_POST['ob_contact_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['ob_contact_nonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, 'ob_contact_form' ) ) {
			$message = ocean_breeze_contact_form_notice(
				__( 'Security check failed. Please try again.', 'ocean-breeze' ),
				'error'
			);
		} else {
			$name    = sanitize_text_field( wp_unslash( $_POST['ob_name'] ?? '' ) );
			$email   = sanitize_email( wp_unslash( $_POST['ob_email'] ?? '' ) );
			$content = sanitize_textarea_field( wp_unslash( $_POST['ob_message'] ?? '' ) );
			$turnstile_response = sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) );

			if ( empty( $name ) || empty( $email ) || empty( $content ) ) {
				$message = ocean_breeze_contact_form_notice(
					__( 'Please fill out all fields.', 'ocean-breeze' ),
					'error'
				);
			} elseif ( ! is_email( $email ) ) {
				$message = ocean_breeze_contact_form_notice(
					__( 'Please enter a valid email address.', 'ocean-breeze' ),
					'error'
				);
			} elseif ( defined( 'TURNSTILE_SECRET_KEY' ) && TURNSTILE_SECRET_KEY ) {
				// Verify Turnstile response
				$verify_response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
					'body' => array(
						'secret'   => TURNSTILE_SECRET_KEY,
						'response' => $turnstile_response,
						'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
					),
				) );

				$verify_success = false;
				if ( ! is_wp_error( $verify_response ) ) {
					$verify_body = json_decode( wp_remote_retrieve_body( $verify_response ), true );
					if ( isset( $verify_body['success'] ) && $verify_body['success'] ) {
						$verify_success = true;
					}
				}

				if ( ! $verify_success ) {
					$message = ocean_breeze_contact_form_notice(
						__( 'Bot protection check failed. Please try again.', 'ocean-breeze' ),
						'error'
					);
				}
			}

			if ( empty( $message ) ) {
				$to      = 'rentals@bluestarfishguesthouse.com';
				$subject = sprintf(
					/* translators: %s: sender name */
					__( 'New contact form submission from %s', 'ocean-breeze' ),
					$name
				);
				$body    = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$content}";
				$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

				if ( wp_mail( $to, $subject, $body, $headers ) ) {
					$message = ocean_breeze_contact_form_notice(
						__( 'Thank you! Your message has been sent.', 'ocean-breeze' ),
						'success'
					);
				} else {
					$message = ocean_breeze_contact_form_notice(
						__( 'There was a problem sending your message. Please try again later.', 'ocean-breeze' ),
						'error'
					);
				}
			}
		}
	}

	// Try to load local keys file for testing if it exists (not committed to git)
	if ( file_exists( ABSPATH . 'local-keys.php' ) && ! defined( 'TURNSTILE_SITE_KEY' ) ) {
		require_once ABSPATH . 'local-keys.php';
	}
	
	// Also check project root since ABSPATH might just be the wp dir during testing
	if ( file_exists( dirname( __DIR__, 3 ) . '/local-keys.php' ) && ! defined( 'TURNSTILE_SITE_KEY' ) ) {
		require_once dirname( __DIR__, 3 ) . '/local-keys.php';
	}

	ob_start();
	?>
	<div class="ocean-breeze-contact-form wp-block-group">
		<?php
		if ( $message ) {
			echo wp_kses_post( $message );
		}
		
		// Add Turnstile script to head/footer
		if ( defined( 'TURNSTILE_SITE_KEY' ) && TURNSTILE_SITE_KEY && ! wp_script_is( 'cloudflare-turnstile' ) ) {
			wp_enqueue_script( 'cloudflare-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
		}
		?>
		<form method="post" action="<?php echo esc_url( ocean_breeze_contact_page_url() ); ?>#contact-form" id="contact-form" class="ocean-breeze-contact-form__form">
			<?php wp_nonce_field( 'ob_contact_form', 'ob_contact_nonce' ); ?>
			<p class="ocean-breeze-contact-form__field">
				<label for="ob_name"><?php esc_html_e( 'Name', 'ocean-breeze' ); ?></label>
				<input type="text" name="ob_name" id="ob_name" required autocomplete="name">
			</p>
			<p class="ocean-breeze-contact-form__field">
				<label for="ob_email"><?php esc_html_e( 'Email', 'ocean-breeze' ); ?></label>
				<input type="email" name="ob_email" id="ob_email" required autocomplete="email">
			</p>
			<p class="ocean-breeze-contact-form__field">
				<label for="ob_message"><?php esc_html_e( 'Message', 'ocean-breeze' ); ?></label>
				<textarea name="ob_message" id="ob_message" rows="5" required></textarea>
			</p>
			
			<?php if ( defined( 'TURNSTILE_SITE_KEY' ) && TURNSTILE_SITE_KEY ) : ?>
				<div class="ocean-breeze-contact-form__field">
					<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( TURNSTILE_SITE_KEY ); ?>"></div>
				</div>
			<?php endif; ?>

			<p class="ocean-breeze-contact-form__actions">
				<button type="submit" name="ob_contact_submit" class="wp-block-button__link wp-element-button">
					<?php esc_html_e( 'Send message', 'ocean-breeze' ); ?>
				</button>
			</p>
		</form>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'simple_contact_form', 'ocean_breeze_contact_form_shortcode' );

/**
 * Build a styled notice for the contact form.
 *
 * @param string $text Notice text.
 * @param string $type success|error.
 * @return string
 */
function ocean_breeze_contact_form_notice( $text, $type = 'success' ) {
	$is_success = 'success' === $type;

	$bg     = $is_success ? '#dcfce7' : '#fee2e2';
	$color  = $is_success ? '#166534' : '#991b1b';
	$escaped = esc_html( $text );

	return '<div class="ocean-breeze-contact-form__notice ocean-breeze-contact-form__notice--' . esc_attr( $type ) . '" style="background-color:' . esc_attr( $bg ) . ';color:' . esc_attr( $color ) . '"><p>' . $escaped . '</p></div>';
}
