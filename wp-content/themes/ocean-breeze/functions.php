<?php
/**
 * Ocean Breeze functions and definitions
 *
 * @package OceanBreeze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ocean_breeze_setup' ) ) :
	function ocean_breeze_setup() {
		// Enqueue editor styles so the block editor matches the front-end.
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/editor-style.css' );

		// Make theme available for translation.
		load_theme_textdomain( 'ocean-breeze', get_template_directory() . '/languages' );

		// Adds RSS feed links to <head>.
		add_theme_support( 'automatic-feed-links' );

		// Lets WP manage the document <title>.
		add_theme_support( 'title-tag' );

		// HTML5 markup for core elements.
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);

		// Support responsive embeds.
		add_theme_support( 'responsive-embeds' );
	}
endif;
add_action( 'after_setup_theme', 'ocean_breeze_setup' );

/**
 * Enqueue front-end stylesheet (mostly for the theme header; theme.json handles styling).
 */
function ocean_breeze_enqueue_styles() {
	wp_enqueue_style(
		'ocean-breeze-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	// Optional small front-end refinements not expressible in theme.json.
	$extras_path = get_template_directory() . '/assets/style-extras.css';
	$extras_ver  = file_exists( $extras_path ) ? filemtime( $extras_path ) : wp_get_theme()->get( 'Version' );
	
	wp_enqueue_style(
		'ocean-breeze-extras',
		get_template_directory_uri() . '/assets/style-extras.css',
		array( 'ocean-breeze-style' ),
		$extras_ver
	);
}
add_action( 'wp_enqueue_scripts', 'ocean_breeze_enqueue_styles' );

/**
 * Output favicon links in <head> if no Site Icon is set in the Customizer.
 */
function ocean_breeze_favicons() {
	if ( ! has_site_icon() ) {
		$theme_uri = get_template_directory_uri();
		echo '<link rel="icon" href="' . esc_url( $theme_uri . '/assets/images/favicon/favicon.ico' ) . '" sizes="any">' . "\n";
		echo '<link rel="icon" href="' . esc_url( $theme_uri . '/assets/images/favicon/icon.svg' ) . '" type="image/svg+xml">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( $theme_uri . '/assets/images/favicon/apple-touch-icon.png' ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'ocean_breeze_favicons' );

// DreamHost SMTP + contact form (Turnstile keys: wp-config.php or local-keys.php).
require_once get_template_directory() . '/inc-mail-smtp.php';
require_once get_template_directory() . '/inc-contact-form.php';
require_once get_template_directory() . '/inc-availability-calendars.php';

/**
 * Ensure a published Contact page exists at /contact/.
 */
function ocean_breeze_ensure_contact_page() {
	if ( get_page_by_path( 'contact' ) ) {
		return;
	}

	wp_insert_post(
		array(
			'post_title'  => __( 'Contact', 'ocean-breeze' ),
			'post_name'   => 'contact',
			'post_status' => 'publish',
			'post_type'   => 'page',
		)
	);
}
add_action( 'init', 'ocean_breeze_ensure_contact_page' );

/**
 * Ensure a published Location page exists at /location/.
 */
function ocean_breeze_ensure_location_page() {
	if ( get_page_by_path( 'location' ) ) {
		return;
	}

	wp_insert_post(
		array(
			'post_title'  => __( 'Location', 'ocean-breeze' ),
			'post_name'   => 'location',
			'post_status' => 'publish',
			'post_type'   => 'page',
		)
	);
}
add_action( 'init', 'ocean_breeze_ensure_location_page' );

/**
 * Register block pattern category.
 */
function ocean_breeze_register_pattern_categories() {
	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category(
			'ocean-breeze',
			array(
				'label'       => __( 'Ocean Breeze', 'ocean-breeze' ),
				'description' => __( 'Patterns for the Ocean Breeze theme.', 'ocean-breeze' ),
			)
		);
	}
}
add_action( 'init', 'ocean_breeze_register_pattern_categories' );

/**
 * Custom AIOSEO meta title for key pages.
 *
 * @param string $title Current title.
 * @return string
 */
function ocean_breeze_custom_aioseo_title( $title ) {
	if ( is_front_page() ) {
		return $title;
	}
	if ( is_page( 'location' ) ) {
		return 'Location & Directions | Blue Starfish Guesthouse | Corpus Christi Bay Area';
	}
	return $title;
}
add_filter( 'aioseo_title', 'ocean_breeze_custom_aioseo_title' );

/**
 * Custom AIOSEO meta description for key pages.
 *
 * @param string $description Current description.
 * @return string
 */
function ocean_breeze_custom_aioseo_description( $description ) {
	if ( is_front_page() ) {
		return 'Discover two private guesthouses in Corpus Christi for mid-term stays. Enjoy full kitchens, a shared courtyard, and coastal charm minutes from TAMUCC and McGee Beach.';
	}
	if ( is_page( 'location' ) ) {
		return 'Stay at Blue Starfish Guesthouse, 225 Waverly Dr, Corpus Christi TX 78412. Bay-area mid-term rentals near TAMUCC, the Medical District, and McGee Beach.';
	}
	return $description;
}
add_filter( 'aioseo_description', 'ocean_breeze_custom_aioseo_description' );
