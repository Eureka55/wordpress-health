<?php
namespace lsx_health_plan\classes;

/**
 * LSX Health Plan Frontend Class.
 *
 * @package lsx-health-plan
 */
class Frontend {

	/**
	 * Holds class instance
	 *
	 * @since 1.0.0
	 *
	 * @var      object \lsx_health_plan\classes\Frontend()
	 */
	protected static $instance = null;

	/**
	 * @var object \lsx_health_plan\classes\Endpoints();
	 */
	public $endpoints;

	/**
	 * @var object \lsx_health_plan\classes\frontend\Gallery();
	 */
	public $gallery;

	/**
	 * @var object \lsx_health_plan\classes\frontend\Plan_Status();
	 */
	public $plan_status;

	/**
	 * @var object \lsx_health_plan\classes\frontend\General();
	 */
	public $general;

	/**
	 * Contructor
	 */
	public function __construct() {
		$this->load_classes();
		

		if ( ! is_admin() ) {
			// Handle the template redirects.
			add_filter( 'template_include', array( $this, 'archive_template_include' ), 99 );
			add_filter( 'template_include', array( $this, 'single_template_include' ), 99 );
			add_filter( 'template_include', array( $this, 'taxonomy_template_include' ), 99 );
			add_action( 'template_redirect', array( $this, 'redirect' ) );
			
		}	
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return    object \lsx_health_plan\classes\Frontend()    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Loads the variable classes and the static classes.
	 */
	private function load_classes() {

		require_once LSX_HEALTH_PLAN_PATH . 'classes/frontend/class-endpoints.php';
		$this->endpoints = Endpoints::get_instance();

		require_once LSX_HEALTH_PLAN_PATH . 'classes/frontend/class-modals.php';
		$this->modals = Modals::get_instance();

		require_once LSX_HEALTH_PLAN_PATH . 'classes/frontend/class-gallery.php';
		$this->gallery = frontend\Gallery::get_instance();

		require_once LSX_HEALTH_PLAN_PATH . 'classes/frontend/class-plan-status.php';
		$this->plan_status = frontend\Plan_Status::get_instance();

		require_once LSX_HEALTH_PLAN_PATH . 'classes/frontend/class-general.php';
		$this->general = frontend\General::get_instance();

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

	/**
	 * Redirect the user from the cart or checkout page if they have purchased the product already.
	 *
	 * @return void
	 */
	public function redirect() {
		if ( ! is_user_logged_in() || ! function_exists( 'wc_get_page_id' ) || is_home() ) {
			return;
		}
		if ( lsx_health_plan_user_has_purchase() && ( is_page( wc_get_page_id( 'cart' ) ) || is_page( wc_get_page_id( 'checkout' ) ) ) ) {
			wp_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) );
			die;
		}

		$product_id = \lsx_health_plan\functions\get_option( 'membership_product', false );
		if ( false !== $product_id && is_single( $product_id ) ) {
			wp_redirect( home_url() );
			die;
		}
	}
}
