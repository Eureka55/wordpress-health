<?php
namespace lsx_health_plan\classes\frontend;

/**
 * Holds the functionality to for the template redirects.
 *
 * @package lsx-health-plan
 */
class Template_Redirects {

	/**
	 * Holds class instance
	 *
	 * @since 1.0.0
	 *
	 * @var      object \lsx_health_plan\classes\frontend\Template_Redirects()
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'template_include', array( $this, 'archive_template_include' ), 99 );
		add_filter( 'template_include', array( $this, 'single_template_include' ), 99 );
		add_filter( 'template_include', array( $this, 'taxonomy_template_include' ), 99 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return    object \lsx_health_plan\classes\frontend\Template_Redirects()    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Archive template.
	 */
	public function archive_template_include( $template ) {
		$applicable_post_types = apply_filters( 'lsx_health_plan_archive_template', array() );
		if ( ! empty( $applicable_post_types ) && is_main_query() && is_post_type_archive( $applicable_post_types ) ) {
			$post_type = get_post_type();
			if ( empty( locate_template( array( 'archive-' . $post_type . '.php' ) ) ) && file_exists( LSX_HEALTH_PLAN_PATH . 'templates/archive-' . $post_type . '.php' ) ) {
				$template = LSX_HEALTH_PLAN_PATH . 'templates/archive-' . $post_type . '.php';
			}
		}
		return $template;
	}

	/**
	 * Single template.
	 */
	public function single_template_include( $template ) {
		$applicable_post_types = apply_filters( 'lsx_health_plan_single_template', array() );
		if ( ! empty( $applicable_post_types ) && is_main_query() && is_singular( $applicable_post_types ) ) {
			$post_type = get_post_type();
			if ( empty( locate_template( array( 'single-' . $post_type . '.php' ) ) ) && file_exists( LSX_HEALTH_PLAN_PATH . 'templates/single-' . $post_type . '.php' ) ) {
				$template = LSX_HEALTH_PLAN_PATH . 'templates/single-' . $post_type . '.php';
			}
		}
		return $template;
	}

	/**
	 * Redirect WordPress to the taxonomy located in the plugin
	 *
	 * @param     $template string
	 * @return    string
	 */
	public function taxonomy_template_include( $template ) {
		$applicable_taxonomies = apply_filters( 'lsx_health_plan_taxonomies_template', array() );
		if ( is_main_query() && is_tax( $applicable_taxonomies ) ) {
			$current_taxonomy = get_query_var( 'taxonomy' );
			if ( '' === locate_template( array( 'taxonomy-' . $current_taxonomy . '.php' ) ) && file_exists( LSX_HEALTH_PLAN_PATH . 'templates/taxonomy-' . $current_taxonomy . '.php' ) ) {
				$template = LSX_HEALTH_PLAN_PATH . 'templates/taxonomy-' . $current_taxonomy . '.php';
			}
		}
		return $template;
	}
}
