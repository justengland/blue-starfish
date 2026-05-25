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
	wp_enqueue_style(
		'ocean-breeze-extras',
		get_template_directory_uri() . '/assets/style-extras.css',
		array( 'ocean-breeze-style' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ocean_breeze_enqueue_styles' );

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
 * Custom AIOSEO Description for the Front Page
 */
function ocean_breeze_custom_front_page_description( $description ) {
	if ( is_front_page() ) {
		return 'Discover two private guesthouses in Corpus Christi for mid-term stays. Enjoy full kitchens, a shared courtyard, and coastal charm minutes from TAMUCC and McGee Beach.';
	}
	return $description;
}
add_filter( 'aioseo_description', 'ocean_breeze_custom_front_page_description' );
