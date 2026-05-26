<?php
/**
 * Simple Contact Form with Cloudflare Turnstile
 *
 * @package OceanBreeze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load Turnstile keys from wp-config constants or local-keys.php (gitignored).
 */
function ocean_breeze_turnstile_load_keys() {
	if ( defined( 'TURNSTILE_SITE_KEY' ) ) {
		return;
	}

	$candidates = array(
		ABSPATH . 'local-keys.php',
		dirname( get_template_directory(), 3 ) . '/local-keys.php',
	);

	foreach ( $candidates as $path ) {
		if ( is_readable( $path ) ) {
			require_once $path;
			break;
		}
	}
}
ocean_breeze_turnstile_load_keys();

/**
 * Whether Turnstile is configured (non-empty site + secret keys).
 *
 * @return bool
 */
function ocean_breeze_turnstile_is_configured() {
	return defined( 'TURNSTILE_SITE_KEY' )
		&& defined( 'TURNSTILE_SECRET_KEY' )
		&& TURNSTILE_SITE_KEY
		&& TURNSTILE_SECRET_KEY
		&& 'your_actual_site_key_here' !== TURNSTILE_SITE_KEY;
}

/**
 * Whether to show submission debug output on the contact form (temporary).
 *
 * @return bool
 */
function ocean_breeze_contact_form_debug_enabled() {
	if ( defined( 'OB_CONTACT_FORM_DEBUG' ) ) {
		return (bool) OB_CONTACT_FORM_DEBUG;
	}

	// Disabled by default; set OB_CONTACT_FORM_DEBUG to true in wp-config to troubleshoot mail.
	return false;
}

/**
 * Verify a Turnstile token with Cloudflare.
 *
 * @param string $token cf-turnstile-response value.
 * @param array  $debug Optional. Filled with debug lines when debug mode is on.
 * @return bool
 */
function ocean_breeze_turnstile_verify( $token, &$debug = null ) {
	$log = static function ( $key, $value ) use ( &$debug ) {
		if ( null !== $debug ) {
			$debug[ $key ] = $value;
		}
	};

	if ( ! ocean_breeze_turnstile_is_configured() ) {
		$log( 'turnstile', 'not configured (skipped)' );
		return true;
	}

	$log( 'turnstile_configured', 'yes' );
	$log( 'turnstile_token_length', strlen( $token ) );

	if ( '' === $token ) {
		$log( 'turnstile_verify', 'fail — empty token' );
		return false;
	}

	$response = wp_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'timeout' => 15,
			'body'    => array(
				'secret'   => TURNSTILE_SECRET_KEY,
				'response' => $token,
				'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		$log( 'turnstile_verify', 'fail — HTTP error' );
		$log( 'turnstile_error', $response->get_error_message() );
		return false;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$log( 'turnstile_http_code', $code );

	if ( is_array( $body ) ) {
		$log( 'turnstile_success', ! empty( $body['success'] ) ? 'yes' : 'no' );
		if ( ! empty( $body['error-codes'] ) && is_array( $body['error-codes'] ) ) {
			$log( 'turnstile_error_codes', implode( ', ', $body['error-codes'] ) );
		}
	}

	return is_array( $body ) && ! empty( $body['success'] );
}

/**
 * Enqueue Turnstile script on pages that render the contact form.
 */
function ocean_breeze_turnstile_enqueue_scripts() {
	if ( ! ocean_breeze_turnstile_is_configured() ) {
		return;
	}

	$should_load = false;

	if ( is_page( 'contact' ) ) {
		$should_load = true;
	} elseif ( is_singular() ) {
		$post = get_post();
		if ( $post && has_shortcode( $post->post_content, 'simple_contact_form' ) ) {
			$should_load = true;
		}
	}

	if ( ! $should_load ) {
		return;
	}

	wp_enqueue_script(
		'cloudflare-turnstile',
		'https://challenges.cloudflare.com/turnstile/v0/api.js',
		array(),
		null,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ocean_breeze_turnstile_enqueue_scripts' );

/**
 * From address for outbound contact form mail (must be @your domain on DreamHost).
 *
 * @return string
 */
function ocean_breeze_contact_form_from_email() {
	if ( defined( 'OB_CONTACT_FORM_FROM' ) && OB_CONTACT_FORM_FROM ) {
		return OB_CONTACT_FORM_FROM;
	}

	if (
		function_exists( 'ocean_breeze_mail_smtp_is_configured' )
		&& ocean_breeze_mail_smtp_is_configured()
		&& defined( 'WPMS_MAIL_FROM' )
		&& WPMS_MAIL_FROM
	) {
		return WPMS_MAIL_FROM;
	}

	return 'notification@bluestarfishguesthouse.com';
}

/**
 * Staff inbox for new contact form submissions (DreamHost forwards to Gmail, etc.).
 *
 * @return string
 */
function ocean_breeze_contact_form_notify_email() {
	if ( defined( 'OB_CONTACT_FORM_NOTIFY' ) && OB_CONTACT_FORM_NOTIFY ) {
		return OB_CONTACT_FORM_NOTIFY;
	}

	return 'rentals@bluestarfishguesthouse.com';
}

/**
 * Reply-To address for customer auto-replies (visitor replies go here).
 *
 * @return string
 */
function ocean_breeze_contact_form_reply_email() {
	if ( defined( 'OB_CONTACT_FORM_REPLY' ) && OB_CONTACT_FORM_REPLY ) {
		return OB_CONTACT_FORM_REPLY;
	}

	return ocean_breeze_contact_form_notify_email();
}

/**
 * Format an email address for mail headers (Name <email@example.com>).
 *
 * @param string $email Address.
 * @param string $name  Optional display name.
 * @return string
 */
function ocean_breeze_contact_form_format_address( $email, $name = '' ) {
	$email = sanitize_email( $email );
	if ( ! $email ) {
		return '';
	}

	$name = trim( wp_strip_all_tags( $name ) );
	if ( '' === $name ) {
		return $email;
	}

	return sprintf( '%s <%s>', $name, $email );
}

/**
 * Send contact form notification via wp_mail with a domain From address.
 *
 * DreamHost rejects PHP mail when From is the site admin Gmail; use a domain address.
 *
 * @param string $to      Recipient.
 * @param string $subject Subject line.
 * @param string $body    Plain-text body.
 * @param string $reply_to Reply-To header value (visitor); empty to omit.
 * @param array  $debug    Optional. Filled with mail debug lines.
 * @param array  $args     Optional. from_name, include_bcc, debug_prefix.
 * @return bool
 */
function ocean_breeze_contact_form_send_mail( $to, $subject, $body, $reply_to = '', &$debug = null, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'from_name'    => __( 'Blue Starfish Contact Form', 'ocean-breeze' ),
			'include_bcc'  => true,
			'debug_prefix' => '',
		)
	);

	$from_email = ocean_breeze_contact_form_from_email();
	$from_name  = $args['from_name'];
	$prefix     = $args['debug_prefix'];

	$log = static function ( $key, $value ) use ( &$debug, $prefix ) {
		if ( null !== $debug ) {
			$debug[ $prefix . $key ] = $value;
		}
	};

	$log( 'mail_to', $to );
	$log( 'mail_from', $from_email );
	$log( 'mail_reply_to', $reply_to ? $reply_to : '(none)' );
	$log( 'wp_admin_email', get_option( 'admin_email' ) );
	$log(
		'smtp_configured',
		function_exists( 'ocean_breeze_mail_smtp_is_configured' ) && ocean_breeze_mail_smtp_is_configured() ? 'yes' : 'no'
	);
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$log( 'wp_mail_smtp_active', is_plugin_active( 'wp-mail-smtp/wp_mail_smtp.php' ) ? 'yes' : 'no' );

	$from_filter = static function () use ( $from_email ) {
		return $from_email;
	};
	$name_filter = static function () use ( $from_name ) {
		return $from_name;
	};

	$apply_from = static function ( $phpmailer ) use ( $from_email, $from_name, $log ) {
		$phpmailer->From     = $from_email;
		$phpmailer->FromName = $from_name;
		$log( 'phpmailer_mailer', $phpmailer->Mailer );
		$log( 'phpmailer_host', $phpmailer->Host ?? '' );
		$log( 'phpmailer_from_name', $phpmailer->FromName );
	};

	add_filter( 'wp_mail_from', $from_filter );
	add_filter( 'wp_mail_from_name', $name_filter );
	// After WP Mail SMTP (may force a global from name).
	add_action( 'phpmailer_init', $apply_from, 9999 );

	$headers = array();
	if ( $reply_to ) {
		$headers[] = 'Reply-To: ' . $reply_to;
	}

	if ( $args['include_bcc'] && function_exists( 'ocean_breeze_contact_form_bcc_emails' ) ) {
		$bcc_list = ocean_breeze_contact_form_bcc_emails();
		foreach ( $bcc_list as $bcc ) {
			$headers[] = 'Bcc: ' . $bcc;
		}
		if ( null !== $debug && ! empty( $bcc_list ) ) {
			$log( 'mail_bcc', implode( ', ', $bcc_list ) );
		}
	}

	$sent = wp_mail( $to, $subject, $body, $headers );

	remove_filter( 'wp_mail_from', $from_filter );
	remove_filter( 'wp_mail_from_name', $name_filter );
	remove_action( 'phpmailer_init', $apply_from, 9999 );

	global $phpmailer;
	if ( isset( $phpmailer ) && is_object( $phpmailer ) ) {
		$log( 'phpmailer_from', $phpmailer->From ?? '' );
		$log( 'phpmailer_error', $phpmailer->ErrorInfo ?? '' );
	}

	$log( 'wp_mail_returned', $sent ? 'true' : 'false' );

	return $sent;
}

/**
 * Send an auto-reply confirmation to the visitor.
 *
 * @param string $name  Visitor name.
 * @param string $email Visitor email.
 * @param array  $debug Optional debug lines (prefixed with autoreply_).
 * @return bool
 */
function ocean_breeze_contact_form_send_autoreply( $name, $email, &$debug = null ) {
	$subject = __( 'Thanks for contacting Blue Starfish Guesthouse', 'ocean-breeze' );
	$body    = sprintf(
		/* translators: %s: visitor name */
		__(
			"Hi %s,\n\nThank you for reaching out to Blue Starfish Guesthouse. We have received your message and someone will get back to you soon.\n\n— Blue Starfish Guesthouse\nhttps://www.bluestarfishguesthouse.com/\n",
			'ocean-breeze'
		),
		$name
	);

	$reply_to = ocean_breeze_contact_form_format_address(
		ocean_breeze_contact_form_reply_email(),
		__( 'Blue Starfish Guesthouse', 'ocean-breeze' )
	);

	return ocean_breeze_contact_form_send_mail(
		$email,
		$subject,
		$body,
		$reply_to,
		$debug,
		array(
			'from_name'    => __( 'Blue Starfish', 'ocean-breeze' ),
			'include_bcc'  => false,
			'debug_prefix' => 'autoreply_',
		)
	);
}

/**
 * Render a temporary debug panel after form submission.
 *
 * @param array $lines Key/value debug lines.
 * @return string
 */
function ocean_breeze_contact_form_render_debug( $lines ) {
	if ( empty( $lines ) ) {
		return '';
	}

	$out  = '<details class="ocean-breeze-contact-form__debug" style="margin:1rem 0;padding:1rem;background:#1e293b;color:#e2e8f0;border-radius:6px;font-family:monospace;font-size:0.8125rem;">';
	$out .= '<summary style="cursor:pointer;font-weight:600;">Submission debug (temporary)</summary>';
	$out .= '<dl style="margin:0.75rem 0 0;padding:0;">';

	foreach ( $lines as $key => $value ) {
		if ( is_array( $value ) ) {
			$value = wp_json_encode( $value );
		}
		$out .= '<dt style="margin-top:0.5rem;color:#94a3b8;">' . esc_html( (string) $key ) . '</dt>';
		$out .= '<dd style="margin:0.15rem 0 0 0;padding:0;word-break:break-all;">' . esc_html( (string) $value ) . '</dd>';
	}

	$out .= '</dl></details>';

	return $out;
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
	$debug   = array();

	if (
		'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' )
		&& isset( $_POST['ob_contact_submit'] )
	) {
		if ( ocean_breeze_contact_form_debug_enabled() ) {
			$debug['submitted_at_utc'] = gmdate( 'Y-m-d H:i:s' );
			$debug['request_method']   = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
			$debug['remote_addr']      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		}

		$nonce = isset( $_POST['ob_contact_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['ob_contact_nonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, 'ob_contact_form' ) ) {
			if ( ocean_breeze_contact_form_debug_enabled() ) {
				$debug['nonce'] = 'invalid';
			}
			$message = ocean_breeze_contact_form_notice(
				__( 'Security check failed. Please try again.', 'ocean-breeze' ),
				'error'
			);
		} else {
			if ( ocean_breeze_contact_form_debug_enabled() ) {
				$debug['nonce'] = 'valid';
			}

			$name               = sanitize_text_field( wp_unslash( $_POST['ob_name'] ?? '' ) );
			$email              = sanitize_email( wp_unslash( $_POST['ob_email'] ?? '' ) );
			$content            = sanitize_textarea_field( wp_unslash( $_POST['ob_message'] ?? '' ) );
			$turnstile_response = sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) );

			if ( ocean_breeze_contact_form_debug_enabled() ) {
				$debug['name_length']    = strlen( $name );
				$debug['email']          = $email;
				$debug['email_valid']    = is_email( $email ) ? 'yes' : 'no';
				$debug['message_length'] = strlen( $content );
			}

			if ( empty( $name ) || empty( $email ) || empty( $content ) ) {
				if ( ocean_breeze_contact_form_debug_enabled() ) {
					$debug['validation'] = 'missing required fields';
				}
				$message = ocean_breeze_contact_form_notice(
					__( 'Please fill out all fields.', 'ocean-breeze' ),
					'error'
				);
			} elseif ( ! is_email( $email ) ) {
				if ( ocean_breeze_contact_form_debug_enabled() ) {
					$debug['validation'] = 'invalid email';
				}
				$message = ocean_breeze_contact_form_notice(
					__( 'Please enter a valid email address.', 'ocean-breeze' ),
					'error'
				);
			} elseif ( ! ocean_breeze_turnstile_verify( $turnstile_response, $debug ) ) {
				if ( ocean_breeze_contact_form_debug_enabled() ) {
					$debug['validation'] = 'turnstile failed';
				}
				$message = ocean_breeze_contact_form_notice(
					__( 'Bot protection check failed. Please try again.', 'ocean-breeze' ),
					'error'
				);
			} else {
				if ( ocean_breeze_contact_form_debug_enabled() ) {
					$debug['validation'] = 'passed — sending mail';
				}

				$subject = sprintf(
					/* translators: %s: sender name */
					__( 'New contact form submission from %s', 'ocean-breeze' ),
					$name
				);
				$body     = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$content}";
				$reply_to = $name . ' <' . $email . '>';

				$mail_debug = ocean_breeze_contact_form_debug_enabled() ? array() : null;

				$notify_sent = ocean_breeze_contact_form_send_mail(
					ocean_breeze_contact_form_notify_email(),
					$subject,
					$body,
					$reply_to,
					$mail_debug,
					array(
						'from_name'    => $name,
						'debug_prefix' => 'notify_',
						'include_bcc'  => true,
					)
				);

				$autoreply_sent = ocean_breeze_contact_form_send_autoreply( $name, $email, $mail_debug );

				$sent = $notify_sent && $autoreply_sent;

				if ( ocean_breeze_contact_form_debug_enabled() ) {
					$debug['notify_to']      = ocean_breeze_contact_form_notify_email();
					$debug['notify_sent']    = $notify_sent ? 'yes' : 'no';
					$debug['autoreply_sent'] = $autoreply_sent ? 'yes' : 'no';
					if ( ! empty( $mail_debug ) ) {
						$debug = array_merge( $debug, $mail_debug );
					}
				}

				if ( $sent ) {
					if ( ocean_breeze_contact_form_debug_enabled() ) {
						$debug['result'] = 'notification and auto-reply sent';
					}
					// Return success response to handle frontend modal display
					if ( isset( $_POST['is_ajax'] ) ) {
						wp_send_json_success( __( 'Thank you! Your message has been sent.', 'ocean-breeze' ) );
						exit;
					}
					$message = ocean_breeze_contact_form_notice(
						__( 'Thank you! Your message has been sent.', 'ocean-breeze' ),
						'success'
					);
				} else {
					if ( ocean_breeze_contact_form_debug_enabled() ) {
						$debug['result'] = 'one or both emails failed (see notify_sent / autoreply_sent)';
					}
					if ( isset( $_POST['is_ajax'] ) ) {
						wp_send_json_error( __( 'There was a problem sending your message. Please try again later.', 'ocean-breeze' ) );
						exit;
					}
					$message = ocean_breeze_contact_form_notice(
						__( 'There was a problem sending your message. Please try again later.', 'ocean-breeze' ),
						'error'
					);
				}
			}
		}
	}

	ob_start();
	?>
	<div class="ocean-breeze-contact-form wp-block-group">
		<?php
		if ( $message ) {
			echo wp_kses_post( $message );
		}
		if ( ocean_breeze_contact_form_debug_enabled() && ! empty( $debug ) ) {
			echo wp_kses_post( ocean_breeze_contact_form_render_debug( $debug ) );
		}
		?>
		
		<!-- Modal template, hidden by default -->
		<dialog id="contact-success-modal" class="ocean-breeze-contact-modal">
			<div class="ocean-breeze-contact-modal__content">
				<h3 class="ocean-breeze-contact-modal__title"><?php esc_html_e( 'Message Sent', 'ocean-breeze' ); ?></h3>
				<p class="ocean-breeze-contact-modal__text" id="contact-success-message"></p>
				<button type="button" class="ocean-breeze-contact-modal__close wp-block-button__link wp-element-button">
					<?php esc_html_e( 'Return to Home', 'ocean-breeze' ); ?>
				</button>
			</div>
		</dialog>

		<form method="post" action="<?php echo esc_url( ocean_breeze_contact_page_url() ); ?>#contact-form" id="contact-form" class="ocean-breeze-contact-form__form">
			<?php wp_nonce_field( 'ob_contact_form', 'ob_contact_nonce' ); ?>
			<input type="hidden" name="is_ajax" value="1">
			
			<div class="ocean-breeze-contact-form__row">
				<p class="ocean-breeze-contact-form__field">
					<label for="ob_name"><?php esc_html_e( 'Name', 'ocean-breeze' ); ?></label>
					<input type="text" name="ob_name" id="ob_name" required autocomplete="name">
				</p>
				<p class="ocean-breeze-contact-form__field">
					<label for="ob_email"><?php esc_html_e( 'Email', 'ocean-breeze' ); ?></label>
					<input type="email" name="ob_email" id="ob_email" required autocomplete="email">
				</p>
			</div>
			<p class="ocean-breeze-contact-form__field">
				<label for="ob_message"><?php esc_html_e( 'Message', 'ocean-breeze' ); ?></label>
				<textarea name="ob_message" id="ob_message" rows="5" required></textarea>
			</p>

			<?php if ( ocean_breeze_turnstile_is_configured() ) : ?>
				<div class="ocean-breeze-contact-form__field ocean-breeze-contact-form__turnstile">
					<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( TURNSTILE_SITE_KEY ); ?>"></div>
				</div>
			<?php endif; ?>

			<div id="contact-form-error" class="ocean-breeze-contact-form__notice ocean-breeze-contact-form__notice--error" style="display:none;" aria-live="polite"></div>

			<p class="ocean-breeze-contact-form__actions">
				<button type="submit" name="ob_contact_submit" class="wp-block-button__link wp-element-button">
					<?php esc_html_e( 'Send message', 'ocean-breeze' ); ?>
				</button>
			</p>
		</form>
	</div>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			var form = document.getElementById('contact-form');
			if (!form) return;
			
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				
				var submitButton = form.querySelector('button[type="submit"]');
				var errorDiv = document.getElementById('contact-form-error');
				var originalText = submitButton.textContent;
				
				submitButton.disabled = true;
				submitButton.textContent = '<?php esc_attr_e( 'Sending...', 'ocean-breeze' ); ?>';
				errorDiv.style.display = 'none';
				
				var formData = new FormData(form);
				// Make sure we pass the submit name that the backend checks for
				formData.append('ob_contact_submit', '1');
				
				fetch(form.action, {
					method: 'POST',
					body: formData,
					headers: {
						'X-Requested-With': 'XMLHttpRequest'
					}
				})
				.then(function(response) {
					return response.json();
				})
				.then(function(data) {
					submitButton.disabled = false;
					submitButton.textContent = originalText;
					
					if (data.success) {
						var modal = document.getElementById('contact-success-modal');
						var messageEl = document.getElementById('contact-success-message');
						
						if (modal && messageEl) {
							messageEl.textContent = data.data;
							modal.showModal();
							
							var closeBtn = modal.querySelector('.ocean-breeze-contact-modal__close');
							if (closeBtn) {
								closeBtn.addEventListener('click', function() {
									modal.close();
									window.location.href = '<?php echo esc_url( home_url( '/' ) ); ?>';
								});
							}
							
							// Close if clicked outside
							modal.addEventListener('click', function(e) {
								var dialogDimensions = modal.getBoundingClientRect()
								if (
									e.clientX < dialogDimensions.left ||
									e.clientX > dialogDimensions.right ||
									e.clientY < dialogDimensions.top ||
									e.clientY > dialogDimensions.bottom
								) {
									modal.close();
									window.location.href = '<?php echo esc_url( home_url( '/' ) ); ?>';
								}
							});
						} else {
							// Fallback if modal not supported or missing
							alert(data.data);
							window.location.href = '<?php echo esc_url( home_url( '/' ) ); ?>';
						}
					} else {
						if (errorDiv) {
							errorDiv.innerHTML = '<p>' + (data.data || 'Error submitting form.') + '</p>';
							errorDiv.style.display = 'block';
						} else {
							alert(data.data || 'Error submitting form.');
						}
						
						// Reset turnstile if exists
						if (typeof turnstile !== 'undefined') {
							turnstile.reset();
						}
					}
				})
				.catch(function(error) {
					submitButton.disabled = false;
					submitButton.textContent = originalText;
					if (errorDiv) {
						errorDiv.innerHTML = '<p><?php esc_attr_e( 'A network error occurred. Please try again.', 'ocean-breeze' ); ?></p>';
						errorDiv.style.display = 'block';
					} else {
						alert('<?php esc_attr_e( 'A network error occurred. Please try again.', 'ocean-breeze' ); ?>');
					}
					
					if (typeof turnstile !== 'undefined') {
						turnstile.reset();
					}
				});
			});
		});
	</script>
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

	$bg      = $is_success ? '#dcfce7' : '#fee2e2';
	$color   = $is_success ? '#166534' : '#991b1b';
	$escaped = esc_html( $text );

	return '<div class="ocean-breeze-contact-form__notice ocean-breeze-contact-form__notice--' . esc_attr( $type ) . '" style="background-color:' . esc_attr( $bg ) . ';color:' . esc_attr( $color ) . '"><p>' . $escaped . '</p></div>';
}
