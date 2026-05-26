<?php
/**
 * Turnstile-gated Google Calendar embeds for guesthouse availability.
 *
 * @package OceanBreeze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared Google Calendar embed options (same for Guesthouse A and B).
 *
 * @return array<string, string>
 */
function ocean_breeze_availability_calendar_embed_options() {
	$options = array(
		'height'        => '600',
		'wkst'          => '1',
		'ctz'           => 'America/Chicago',
		'showPrint'     => '0',
		'showTitle'     => '0',
		'showTabs'      => '0',
		'showCalendars' => '0',
	);

	/**
	 * Filter shared embed query options for availability calendars.
	 *
	 * @param array<string, string> $options
	 */
	return apply_filters( 'ocean_breeze_availability_calendar_embed_options', $options );
}

/**
 * Per-unit calendar titles, sources, and colors for the embed URL.
 *
 * @param string $unit a|b.
 * @return array{title: string, src: string[], color: string[]}|null
 */
function ocean_breeze_availability_calendar_embed_sources( $unit ) {
	$sources = array(
		'a' => array(
			'title' => 'Guesthouse A',
			'src'   => array(
				'Y18yNzg5MTE0Yzg5ZjllMTM2MmI1NDVkNmYyYjdiMzM2ZWM3NGZhYjY4NTQ1NTdlOWM4NDE1MTg2MjlmYmUwZDhkQGdyb3VwLmNhbGVuZGFyLmdvb2dsZS5jb20',
				'OGwxM2VkY2xmbnVjNjk1Y3VscWlhYWVucW80Y3FsOW9AaW1wb3J0LmNhbGVuZGFyLmdvb2dsZS5jb20',
			),
			'color' => array( '%23e4c441', '%23039be5' ),
		),
		'b' => array(
			'title' => 'Guesthouse B',
			'src'   => array(
				'OGwxM2VkY2xmbnVjNjk1Y3VscWlhYWVucW80Y3FsOW9AaW1wb3J0LmNhbGVuZGFyLmdvb2dsZS5jb20',
				'ZW4udXNhI2hvbGlkYXlAZ3JvdXAudi5jYWxlbmRhci5nb29nbGUuY29t',
			),
			'color' => array( '%23009688', '%230b8043' ),
		),
	);

	if ( ! isset( $sources[ $unit ] ) ) {
		return null;
	}

	/**
	 * Filter calendar sources for a availability unit.
	 *
	 * @param array{title: string, src: string[], color: string[]} $config
	 * @param string                                                $unit   a|b.
	 */
	return apply_filters( 'ocean_breeze_availability_calendar_embed_sources', $sources[ $unit ], $unit );
}

/**
 * Build a Google Calendar embed URL for a guesthouse unit.
 *
 * @param string $unit a|b.
 * @return string
 */
function ocean_breeze_availability_calendar_embed_url( $unit ) {
	$config = ocean_breeze_availability_calendar_embed_sources( $unit );
	if ( null === $config ) {
		return '';
	}

	$query = array();
	foreach ( ocean_breeze_availability_calendar_embed_options() as $key => $value ) {
		$query[] = rawurlencode( $key ) . '=' . rawurlencode( $value );
	}
	$query[] = 'title=' . rawurlencode( $config['title'] );
	foreach ( $config['src'] as $src ) {
		$query[] = 'src=' . $src;
	}
	foreach ( $config['color'] as $color ) {
		$query[] = 'color=' . $color;
	}

	return 'https://calendar.google.com/calendar/embed?' . implode( '&', $query );
}

/**
 * Calendar embed URLs keyed by unit (a, b).
 *
 * @return array<string, string>
 */
function ocean_breeze_availability_calendar_urls() {
	$urls = array(
		'a' => ocean_breeze_availability_calendar_embed_url( 'a' ),
		'b' => ocean_breeze_availability_calendar_embed_url( 'b' ),
	);

	/**
	 * Filter Google Calendar embed URLs for availability units.
	 *
	 * @param array<string, string> $urls Keys `a` and `b`.
	 */
	return apply_filters( 'ocean_breeze_availability_calendar_urls', $urls );
}

/**
 * Human-readable labels for calendar units.
 *
 * @return array<string, string>
 */
function ocean_breeze_availability_unit_labels() {
	return array(
		'a' => __( 'Guesthouse A', 'ocean-breeze' ),
		'b' => __( 'Guesthouse B', 'ocean-breeze' ),
	);
}

/**
 * Default block markup for seeding availability pages (create only).
 *
 * @param string $which hub|guest-house-a|guest-house-b.
 * @return string
 */
function ocean_breeze_availability_default_content( $which ) {
	$url_a = esc_url( home_url( '/availability/guest-house-a/' ) );
	$url_b = esc_url( home_url( '/availability/guest-house-b/' ) );

	switch ( $which ) {
		case 'hub':
			return '<!-- wp:paragraph -->
<p>' . esc_html__( 'View real-time availability for each guesthouse. Open a guesthouse page and complete verification to load its calendar.', 'ocean-breeze' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . $url_a . '">' . esc_html__( 'Guesthouse A', 'ocean-breeze' ) . '</a></div>
<!-- /wp:button -->

<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . $url_b . '">' . esc_html__( 'Guesthouse B', 'ocean-breeze' ) . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->';

		case 'guest-house-a':
			return '<!-- wp:paragraph -->
<p>' . esc_html__( '1 bedroom · 1 queen bed · shared bath. Check booked dates below after verification.', 'ocean-breeze' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[availability_calendars unit="a"]
<!-- /wp:shortcode -->';

		case 'guest-house-b':
			return '<!-- wp:paragraph -->
<p>' . esc_html__( '1 bedroom · 1 queen bed · private bath. Check booked dates below after verification.', 'ocean-breeze' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[availability_calendars unit="b"]
<!-- /wp:shortcode -->';

		default:
			return '';
	}
}

/**
 * Whether the current request should load availability calendar assets.
 *
 * @return bool
 */
function ocean_breeze_availability_should_load_assets() {
	if ( is_page( array( 'guest-house-a', 'guest-house-b' ) ) ) {
		return true;
	}

	if ( is_singular() ) {
		$post = get_post();
		if ( $post && has_shortcode( $post->post_content, 'availability_calendars' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Register availability calendars script.
 */
function ocean_breeze_availability_register_script() {
	$path = get_template_directory() . '/assets/availability-calendars.js';
	$ver  = file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' );

	wp_register_script(
		'ocean-breeze-availability-calendars',
		get_template_directory_uri() . '/assets/availability-calendars.js',
		array(),
		$ver,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ocean_breeze_availability_register_script', 5 );

/**
 * Enqueue availability script + localized config.
 */
function ocean_breeze_availability_enqueue_script() {
	if ( ! wp_script_is( 'ocean-breeze-availability-calendars', 'registered' ) ) {
		ocean_breeze_availability_register_script();
	}

	wp_enqueue_script( 'ocean-breeze-availability-calendars' );

	wp_localize_script(
		'ocean-breeze-availability-calendars',
		'oceanBreezeAvailability',
		array(
			'calendars'    => ocean_breeze_availability_calendar_urls(),
			'labels'       => ocean_breeze_availability_unit_labels(),
			'hasTurnstile' => ocean_breeze_turnstile_is_configured(),
			'prompt'       => __( 'Complete verification below to view the availability calendar.', 'ocean-breeze' ),
		)
	);
}

/**
 * Normalize shortcode unit attribute to list of unit keys.
 *
 * @param string $unit a|b|both.
 * @return string[] List of `a` and/or `b`.
 */
function ocean_breeze_availability_parse_units( $unit ) {
	$unit = strtolower( (string) $unit );
	if ( 'a' === $unit ) {
		return array( 'a' );
	}
	if ( 'b' === $unit ) {
		return array( 'b' );
	}
	return array( 'a', 'b' );
}

/**
 * Shortcode [availability_calendars unit="a|b|both"].
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function ocean_breeze_availability_calendars_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'unit' => 'both',
		),
		$atts,
		'availability_calendars'
	);

	$units       = ocean_breeze_availability_parse_units( $atts['unit'] );
	$labels      = ocean_breeze_availability_unit_labels();
	$has_gate    = ocean_breeze_turnstile_is_configured();
	$widget_id   = 'ocean-breeze-availability-turnstile-' . wp_unique_id();

	ob_start();
	?>
	<section class="ocean-breeze-availability" data-unit="<?php echo esc_attr( implode( ',', $units ) ); ?>">
		<?php if ( $has_gate ) : ?>
			<p class="ocean-breeze-availability__prompt"><?php esc_html_e( 'Complete verification below to view the availability calendar.', 'ocean-breeze' ); ?></p>
		<?php endif; ?>

		<div class="ocean-breeze-availability__calendars"<?php echo $has_gate ? ' hidden' : ''; ?>>
			<?php foreach ( $units as $key ) : ?>
				<?php
				$label = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
				?>
				<div class="ocean-breeze-availability__unit" data-calendar="<?php echo esc_attr( $key ); ?>">
					<?php if ( count( $units ) > 1 ) : ?>
						<h3 class="ocean-breeze-availability__unit-title"><?php echo esc_html( $label ); ?></h3>
					<?php endif; ?>
					<div
						class="ocean-breeze-availability__frame"
						role="region"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: guesthouse name */ __( '%s availability calendar', 'ocean-breeze' ), $label ) ); ?>"
					></div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( $has_gate ) : ?>
			<div class="ocean-breeze-availability__turnstile">
				<div
					id="<?php echo esc_attr( $widget_id ); ?>"
					class="cf-turnstile"
					data-sitekey="<?php echo esc_attr( TURNSTILE_SITE_KEY ); ?>"
					data-callback="oceanBreezeAvailabilityTurnstileSuccess"
					data-expired-callback="oceanBreezeAvailabilityTurnstileExpired"
					data-error-callback="oceanBreezeAvailabilityTurnstileError"
				></div>
			</div>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'availability_calendars', 'ocean_breeze_availability_calendars_shortcode' );

/**
 * Create availability parent + child pages with seeded block content (once).
 */
function ocean_breeze_ensure_availability_pages() {
	$parent = get_page_by_path( 'availability' );

	if ( ! $parent ) {
		$parent_id = wp_insert_post(
			array(
				'post_title'   => __( 'Availability', 'ocean-breeze' ),
				'post_name'    => 'availability',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => ocean_breeze_availability_default_content( 'hub' ),
			),
			true
		);

		if ( is_wp_error( $parent_id ) ) {
			return;
		}

		$parent = get_post( $parent_id );
	}

	if ( ! $parent ) {
		return;
	}

	$children = array(
		'guest-house-a' => array(
			'title'   => __( 'Guesthouse A', 'ocean-breeze' ),
			'content' => ocean_breeze_availability_default_content( 'guest-house-a' ),
		),
		'guest-house-b' => array(
			'title'   => __( 'Guesthouse B', 'ocean-breeze' ),
			'content' => ocean_breeze_availability_default_content( 'guest-house-b' ),
		),
	);

	foreach ( $children as $slug => $meta ) {
		$existing = get_page_by_path( 'availability/' . $slug );
		if ( $existing ) {
			continue;
		}

		wp_insert_post(
			array(
				'post_title'   => $meta['title'],
				'post_name'    => $slug,
				'post_parent'  => (int) $parent->ID,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $meta['content'],
			)
		);
	}
}
add_action( 'init', 'ocean_breeze_ensure_availability_pages' );
