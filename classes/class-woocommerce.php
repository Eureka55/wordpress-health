<?php
namespace lsx_health_plan\classes;

/**
 * Contains the downlaods functions post type
 *
 * @package lsx-health-plan
 */
class Woocommerce {

	/**
	 * Holds class instance
	 *
	 * @since 1.0.0
	 *
	 * @var      object \lsx_health_plan\classes\Woocommerce()
	 */
	protected static $instance = null;

	/**
	 * Contructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 20, 1 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'only_one_in_cart' ), 99, 2 );
		add_filter( 'woocommerce_order_button_text', array( $this, 'checkout_button_text' ), 10, 1 );
		add_filter( 'woocommerce_get_breadcrumb', array( $this, 'breadcrumbs' ), 30, 1 );
		add_filter( 'the_content', array( $this, 'edit_my_account' ) );
		add_action( 'woocommerce_register_form', 'iconic_print_user_frontend_fields', 10 ); // register form
		add_action( 'woocommerce_edit_account_form', 'iconic_print_user_frontend_fields', 10 ); // my account
		add_filter( 'iconic_account_fields', 'iconic_add_post_data_to_account_fields', 10, 1 );
		add_action( 'show_user_profile', 'iconic_print_user_admin_fields', 30 ); // admin: edit profile
		add_action( 'edit_user_profile', 'iconic_print_user_admin_fields', 30 ); // admin: edit other users
		add_action( 'personal_options_update', 'iconic_save_account_fields' ); // edit own account admin
		add_action( 'edit_user_profile_update', 'iconic_save_account_fields' ); // edit other account
		add_action( 'woocommerce_save_account_details', 'iconic_save_account_fields' ); // edit WC account
		add_filter( 'woocommerce_save_account_details_errors', 'iconic_validate_user_frontend_fields', 10 );
		add_filter( 'woocommerce_form_field_text', 'lsx_profile_photo_field_filter', 10, 4 );
		// add the action
		add_action( 'woocommerce_after_edit_account_form', 'action_woocommerce_after_edit_account_form', 10, 0 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return    object \lsx_health_plan\classes\Woocommerce()    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Runs on init
	 *
	 * @return void
	 */
	public function init() {
		remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation' );
	}

	/**
	 * Empties the cart before a product is added
	 *
	 * @param [type] $passed
	 * @param [type] $added_product_id
	 * @return void
	 */
	public function only_one_in_cart( $passed, $added_product_id ) {
		wc_empty_cart();
		return $passed;
	}

	/**
	 * Return the Place Order Text
	 *
	 * @param string $label
	 * @return void
	 */
	public function checkout_button_text( $label = '' ) {
		$label = __( 'Place order', 'lsx-health-plan' );
		return $label;
	}

	/**
	 * Add the "Blog" link to the breadcrumbs
	 * @param $crumbs
	 * @return array
	 */
	public function breadcrumbs( $crumbs ) {

		if ( is_singular( 'plan' ) ) {

			$new_crumbs    = array();
			$new_crumbs[0] = $crumbs[0];

			$new_crumbs[1] = array(
				0 => get_the_title( wc_get_page_id( 'myaccount' ) ),
				1 => get_permalink( wc_get_page_id( 'myaccount' ) ),
			);

			$endpoint = get_query_var( 'endpoint' );
			if ( '' === $endpoint || false === $endpoint ) {
				$new_crumbs[2] = array(
					0 => get_the_title(),
					1 => false,
				);
			} else {
				$new_crumbs[2] = array(
					0 => get_the_title(),
					1 => get_permalink(),
				);
				$new_crumbs[3] = array(
					0 => ucwords( $endpoint ),
					1 => false,
				);
			}

			$crumbs = $new_crumbs;
		}
		return $crumbs;
	}

	/**
	 * Outputs the my account shortcode if its the edit account endpoint.
	 *
	 * @param string $content
	 * @return string
	 */
	public function edit_my_account( $content = '' ) {
		if ( is_wc_endpoint_url( 'edit-account' ) ) {
			$content = '<div id="edit-account-tab">[lsx_health_plan_my_profile_tabs]<div class="edict-account-section"><h2 class="title-lined">My Profile</h2><p>Update your details below</p>[woocommerce_my_account]</div><div class="stat-section"><h2 class="title-lined">My Stats</h2></div></div>';
		}
		return $content;
	}

	/**
	 * Add post values to account fields if set.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	function iconic_add_post_data_to_account_fields( $fields ) {
		if ( empty( $_POST ) ) {
			return $fields;
		}

		foreach ( $fields as $key => $field_args ) {
			if ( empty( $_POST[ $key ] ) ) {
				$fields[ $key ]['value'] = '';
				continue;
			}

			$fields[ $key ]['value'] = $_POST[ $key ];
		}

		return $fields;
	}


	/**
	 * Add fields to registration form and account area.
	 */
	function iconic_print_user_frontend_fields() {
		$fields            = iconic_get_account_fields();
		$is_user_logged_in = is_user_logged_in();

		foreach ( $fields as $key => $field_args ) {
			$value = null;

			if ( ! iconic_is_field_visible( $field_args ) ) {
				continue;
			}

			if ( $is_user_logged_in ) {
				$user_id = iconic_get_edit_user_id();
				$value   = iconic_get_userdata( $user_id, $key );
			}

			$value = isset( $field_args['value'] ) ? $field_args['value'] : $value;

			woocommerce_form_field( $key, $field_args, $value );
		}
	}

	/**
	 * Get user data.
	 *
	 * @param $user_id
	 * @param $key
	 *
	 * @return mixed|string
	 */
	function iconic_get_userdata( $user_id, $key ) {
		if ( ! iconic_is_userdata( $key ) ) {
			return get_user_meta( $user_id, $key, true );
		}

		$userdata = get_userdata( $user_id );

		if ( ! $userdata || ! isset( $userdata->{$key} ) ) {
			return '';
		}

		return $userdata->{$key};
	}

	/**
	 * Get currently editing user ID (frontend account/edit profile/edit other user).
	 *
	 * @return int
	 */
	function iconic_get_edit_user_id() {
		return isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : get_current_user_id();
	}


	/**
	 * Save registration fields.
	 *
	 * @param int $customer_id
	 */
	function iconic_save_account_fields( $customer_id ) {
		$fields         = iconic_get_account_fields();
		$sanitized_data = array();

		foreach ( $fields as $key => $field_args ) {
			if ( ! iconic_is_field_visible( $field_args ) ) {
				continue;
			}

			$sanitize = isset( $field_args['sanitize'] ) ? $field_args['sanitize'] : 'wc_clean';
			$value    = isset( $_POST[ $key ] ) ? call_user_func( $sanitize, $_POST[ $key ] ) : '';

			if ( iconic_is_userdata( $key ) ) {
				$sanitized_data[ $key ] = $value;
				continue;
			}

			if ( 'profile_photo' === $key ) {

				// This handles the image uploads
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/media.php' );

				$id = media_handle_upload( $key, 0, '' );
				if ( ! is_wp_error( $id ) ) {
					update_term_meta( $customer_id, $key . '_id', $id );
					update_term_meta( $customer_id, $key, $id );
				}
			} else {
				update_user_meta( $customer_id, $key, $value );
			}
		}

		if ( ! empty( $sanitized_data ) ) {
			$sanitized_data['ID'] = $customer_id;
			wp_update_user( $sanitized_data );
		}
	}


	/**
	 * Is this field core user data.
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	function iconic_is_userdata( $key ) {
		$userdata = array(
			'user_pass',
			'user_login',
			'user_nicename',
			'user_url',
			'user_email',
			'display_name',
			'nickname',
			'first_name',
			'last_name',
			'description',
			'rich_editing',
			'user_registered',
			'role',
			'jabber',
			'aim',
			'yim',
			'show_admin_bar_front',
		);

		return in_array( $key, $userdata );
	}

	/**
	 * Is field visible.
	 *
	 * @param $field_args
	 *
	 * @return bool
	 */
	function iconic_is_field_visible( $field_args ) {
		$visible = true;
		$action  = filter_input( INPUT_POST, 'action' );

		if ( is_admin() && ! empty( $field_args['hide_in_admin'] ) ) {
			$visible = false;
		} elseif ( ( is_account_page() || 'save_account_details' === $action ) && is_user_logged_in() && ! empty( $field_args['hide_in_account'] ) ) {
			$visible = false;
		} elseif ( ( is_account_page() || 'save_account_details' === $action ) && ! is_user_logged_in() && ! empty( $field_args['hide_in_registration'] ) ) {
			$visible = false;
		} elseif ( is_checkout() && ! empty( $field_args['hide_in_checkout'] ) ) {
			$visible = false;
		}

		return $visible;
	}

	/**
	 * Add fields to admin area.
	 */
	function iconic_print_user_admin_fields() {
		$fields = iconic_get_account_fields();
		?>
		<h2><?php esc_html_e( 'Additional Information', 'iconic' ); ?></h2>
		<table class="form-table" id="iconic-additional-information">
			<tbody>
			<?php foreach ( $fields as $key => $field_args ) { ?>
				<?php
				if ( ! iconic_is_field_visible( $field_args ) ) {
					continue;
				}

				$user_id = iconic_get_edit_user_id();
				$value   = get_user_meta( $user_id, $key, true );
				?>
				<tr>
					<th>
						<label for="<?php echo $key; ?>"><?php echo $field_args['label']; ?></label>
					</th>
					<td>
						<?php $field_args['label'] = false; ?>
						<?php woocommerce_form_field( $key, $field_args, $value ); ?>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Validate fields on frontend.
	 *
	 * @param WP_Error $errors
	 *
	 * @return WP_Error
	 */
	function iconic_validate_user_frontend_fields( $errors ) {
		$fields = iconic_get_account_fields();

		foreach ( $fields as $key => $field_args ) {
			if ( empty( $field_args['required'] ) ) {
				continue;
			}

			if ( ! isset( $_POST['register'] ) && ! empty( $field_args['hide_in_account'] ) ) {
				continue;
			}

			if ( isset( $_POST['register'] ) && ! empty( $field_args['hide_in_registration'] ) ) {
				continue;
			}

			if ( empty( $_POST[ $key ] ) ) {
				$message = sprintf( __( '%s is a required field.', 'iconic' ), '<strong>' . $field_args['label'] . '</strong>' );
				$errors->add( $key, $message );
			}
		}

		return $errors;
	}

	/**
	 * Changes the text into a file upload.
	 * @param $field
	 * @param $key
	 * @param $args
	 * @param $value
	 *
	 * @return mixed
	 */
	function lsx_profile_photo_field_filter( $field, $key, $args, $value ) {
		if ( 'profile_photo' === $args['id'] ) {

			if ( $args['required'] ) {
				$args['class'][] = 'validate-required';
				$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
			} else {
				$required = '';
			}

			if ( is_string( $args['label_class'] ) ) {
				$args['label_class'] = array( $args['label_class'] );
			}

			if ( is_null( $value ) ) {
				$value = $args['default'];
			}

			// Custom attribute handling.
			$custom_attributes         = array();
			$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

			if ( $args['maxlength'] ) {
				$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
			}

			if ( ! empty( $args['autocomplete'] ) ) {
				$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
			}

			if ( true === $args['autofocus'] ) {
				$args['custom_attributes']['autofocus'] = 'autofocus';
			}

			if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
				foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}

			if ( ! empty( $args['validate'] ) ) {
				foreach ( $args['validate'] as $validate ) {
					$args['class'][] = 'validate-' . $validate;
				}
			}

			$field_html = '';
			$field           = '';
			$label_id        = $args['id'];
			$sort            = $args['priority'] ? $args['priority'] : '';
			$field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';
			$args['class'][] = 'validate-required';
			$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';

			$field .= '<input accept="image/*" type="file" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"  value="" ' . implode( ' ', $custom_attributes ) . ' />';

			if ( '' !== $value && $value !== $args['default'] ) {
				$field .= '<input type="text" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '_id" id="' . esc_attr( $args['id'] ) . '_id" placeholder="' . esc_attr( $args['placeholder'] ) . '_id"  value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';
			}

			$field .= '<input type="hidden" name="MAX_FILE_SIZE" value="500000" />';

			if ( $args['label'] && 'checkbox' !== $args['type'] ) {
				$field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
			}

			$field_html .= $field;

			if ( $args['description'] ) {
				$field_html .= '<span class="description">' . esc_html( $args['description'] ) . '</span>';
			}

			$container_class = esc_attr( implode( ' ', $args['class'] ) );
			$container_id    = esc_attr( $args['id'] ) . '_field';
			$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
		}
		return $field;
	}


	function action_woocommerce_after_edit_account_form() {
		echo do_shortcode( '[avatar_upload /]' );
	}

}
