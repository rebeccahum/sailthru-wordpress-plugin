<?php

class Sailthru_Horizon {

	protected $admin_views = array();

	// Represents the nonce value used to save the post media
	private $nonce = 'wp_sailthru_nonce';


	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'sailthru_init' ) );

		// Register admin styles and scripts
		// Documentation says: admin_print_styles should not be used to enqueue styles or scripts on the admin pages. Use admin_enqueue_scripts instead.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

		// Register Horizon Javascripts
		add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_scripts' ) );

		// Register the menu
		add_action( 'admin_menu', array( $this, 'sailthru_menu' ) );

		// Register the Horizon meta tags
		add_action( 'wp_head', array( $this, 'sailthru_horizon_meta_tags' ) );

		// Setup the meta box hooks
		add_action( 'add_meta_boxes', array( $this, 'sailthru_post_metabox' ) );
		add_action( 'save_post', array( $this, 'save_custom_meta_data' ) );

		// Check for updates and make changes as needed
		add_action( 'plugins_loaded', array( $this, 'sailthru_update_check' ) );

	} // end constructor

	/**
	 * Fired when the plugin is activated.
	 *
	 * @param boolean $network_wide True if WPMU superadmin
	 *    uses "Network Activate" action, false if WPMU is
	 *    disabled or plugin is activated on an individual blog
	 */
	public static function activate( $network_wide ) {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// signal that it's ok to override WordPress's built-in email functions
		if ( false === get_option( 'sailthru_override_wp_mail' ) ) {
			add_option( 'sailthru_override_wp_mail', 1 );
		} // end if

		if ( true === get_option( 'sailthru_setup_complete' ) || true === get_option( 'sailthru_api_validated' ) ) {
			update_option( 'sailthru_api_validated', true );
			update_option( 'sailthru_setup_complete', true );
		} else {
			update_option( 'sailthru_api_validated', false );
			update_option( 'sailthru_setup_complete', false );
		}

	} // end activate

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param boolean $network_wide True if WPMU superadmin
	 *    uses "Network Activate" action, false if WPMU is
	 *    disabled or plugin is activated on an individual blog
	 */
	public static function deactivate( $network_wide ) {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// stop overriding WordPress's built in email functions
		if ( false !== get_option( 'sailthru_override_wp_mail' ) ) {
			delete_option( 'sailthru_override_wp_mail' );
		}

		// we don't know if the API keys, etc, will still be
		// good, so kill the flag that said we knew.
		if ( false !== get_option( 'sailthru_setup_complete' ) ) {
			delete_option( 'sailthru_setup_complete' );
		}

		// remove all setup information including API key info
		if ( false !== get_option( 'sailthru_setup_options' ) ) {
			delete_option( 'sailthru_setup_options' );
		}

		// remove concierge settings
		if ( false !== get_option( 'sailthru_concierge_options' ) ) {
			delete_option( 'sailthru_concierge_options' );
		}

		// remove scout options
		if ( false !== get_option( 'sailthru_scout_options' ) ) {
			delete_option( 'sailthru_scout_options' );
		}

		// remove custom fields options
		if ( false !== get_option( 'sailthru_forms_options' ) ) {
			delete_option( 'sailthru_forms_options' );
		}

		if ( false !== get_option( 'sailthru_customfields_order_widget' ) ) {
			delete_option( 'sailthru_customfields_order_widget' );
		}

		if ( false !== get_option( 'sailthru_customfields_order' ) ) {
			delete_option( 'sailthru_customfields_order' );
		}

		if ( false !== get_option( 'sailthru_forms_key' ) ) {
			delete_option( 'sailthru_forms_key' );
		}

		// remove integrations options
		if ( false !== get_option( 'sailthru_integrations_options' ) ) {
			delete_option( 'sailthru_integrations_options' );
		}

		if ( false !== get_option( 'sailthru_setup_complete' ) ) {
			delete_option( 'sailthru_setup_complete' );
		}

		if ( false !== get_option( 'sailthru_api_validated' ) ) {
			delete_option( 'sailthru_api_validated' );
		}

		//remove plugin version number
		if ( false !== get_option( 'sailthru_plugin_version' ) ) {
			delete_option( 'sailthru_plugin_version' );
		}

	} // end deactivate

	/**
	 * Fired when the plugin is uninstalled.
	 *
	 * @param boolean $network_wide True if WPMU superadmin
	 *    uses "Network Activate" action, false if WPMU is
	 *    disabled or plugin is activated on an individual blog
	 */
	public static function uninstall( $network_wide ) {
		// nothing to see here.
	} // end uninstall


	/**
	 * Fired after plugins are loaded.
	 * Checks versions to see if changes
	 * need to be made to the database
	 */
	public static function sailthru_update_check() {

		$sailthru_plugin_version = get_option( 'sailthru_plugin_version' );

		// changes to <3.0.5
		// delete custom subscribe widget fields from the database,
		// don't just hide them.
		if ( $sailthru_plugin_version <= '3.0.5' || $sailthru_plugin_version === false ) {

			$customfields = get_option( 'sailthru_forms_options' );
			$key          = get_option( 'sailthru_forms_key' );

			$updatedcustomfields = array();

			if ( isset( $customfields ) && ! empty( $customfields ) ) {

				for ( $i = 0; $i <= $key; $i++ ) {
					if ( isset( $customfields[ $i ]['sailthru_customfield_name'] )
						and ! empty( $customfields[ $i ]['sailthru_customfield_name'] ) ) {

						// save non-empty custom fields to the database
						$updatedcustomfields[ $i ] = $customfields[ $i ];

					} else {

						// don't save empty custom fields to the database
						// we're pruning them.
					}
				}

				update_option( 'sailthru_forms_options', $updatedcustomfields );

			} // end if $customfields isset
		}

	}




	/**
	 * Loads the plugin text domain for translation
	 */
	public function sailthru_init() {

		$domain = 'sailthru-for-wordpress-locale';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, SAILTHRU_PLUGIN_PATH . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Add a thumbnail size for concierge
		add_theme_support( 'post-thumbnails' );
		add_image_size( 'concierge-thumb', 50, 50 );

	} // end plugin_textdomain



	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */
	public function register_admin_scripts( $hook ) {

		/* loads the admin js and css for editing/creating posts to make sure this
			is only loaded in context rather than everywhere in the admin screens
		*/

		$screens = array( 'post-new.php', 'post.php', 'edit.php', 'admin.php' );

		if ( in_array( $hook, $screens, true ) ) {

			if ( isset( $_GET['action'] ) ) {
				if ( $_GET['action'] === 'edit' ) {
					// datepicker for the meta box on post pages
					wp_enqueue_style( 'jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
					// our own magic
					wp_enqueue_script( 'sailthru-for-wordpress-admin-script', SAILTHRU_PLUGIN_URL . 'js/admin.js', array( 'jquery' ) );
				}
			}
		}
		// wp_enqueue_script( 'sailthru-subscribe-widget-admin-jquery-script', SAILTHRU_PLUGIN_URL . 'js/widget.subscribe.admin.js', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );

		// abandon if we're not on our own pages.
		if ( ! stristr( $hook, 'sailthru' ) &&
			! stristr( $hook, 'horizon' ) &&
			! stristr( $hook, 'scout' ) &&
			! stristr( $hook, 'concierge' ) &&
			! stristr( $hook, 'custom_fields_configuration_page' ) &&
			! stristr( $hook, 'settings_configuration_page' ) ) {
			return;
		}

		// loads the admin js and css for the configuration pages
		wp_enqueue_script( 'sailthru-for-wordpress-admin-script', SAILTHRU_PLUGIN_URL . 'js/admin.js', array( 'jquery' ) );
		wp_enqueue_style( 'sailthru-for-wordpress-admin-styles', SAILTHRU_PLUGIN_URL . 'css/admin.css' );

	} // end register_admin_scripts



	/**
	 * Registers and enqueues Horizon for every page, but only if setup has been completed.
	 */
	public function register_plugin_scripts() {

		// Check first, otherwise js could throw errors.
		if ( get_option( 'sailthru_setup_complete' ) ) {

			// we're not going to pass the enitre set of options
			// through to be seen in the html source so just stick it in this var
			$options = get_option( 'sailthru_setup_options' );

			if ( isset( $options['sailthru_js_type'] ) && 'none' === $options['sailthru_js_type'] || empty( $options['sailthru_js_type'] ) ) {
				return;
			}

			// check what JS version is being used.

			if ( isset( $options['sailthru_js_type'] ) && 'personalize_js' === $options['sailthru_js_type'] || 'personalize_js_custom' === $options['sailthru_js_type'] ) {


				$customer_id = $options['sailthru_customer_id'];

				if ( $customer_id ) {

					// have to wrap the options in an inner array to preserve booleans.
					$params = array(
						'options' => array(
							'customerId' => $options['sailthru_customer_id'],
						),
					);


					if ( 'personalize_js_custom' === $options['sailthru_js_type'] ) {

						$params['isCustom'] = true;

						// override defaults
						if ( 'false' === $options['sailthru_js_auto_track_pageview'] ) {
							$params['options']['autoTrackPageview'] = (bool) false;
						}

						if ( 'false' === $options['sailthru_ignore_personalize_stored_tags'] ) {
							$params['options']['useStoredTags'] = (bool) false;
						}

						if ( 'true' === $options['sailthru_js_exclude_content'] ) {
							$params['options']['excludeContent'] = (bool) true;
						}
					}

					if ( ! is_404() && ! is_preview() && apply_filters( 'sailthru_add_spm_js', true ) ) {
						wp_enqueue_script( 'personalize_js', '//ak.sail-horizon.com/spm/spm.v1.min.js' );
						wp_register_script( 'tag', plugin_dir_url( __DIR__ ) . 'js/tag.js', array('jquery') );
						wp_localize_script( 'tag', 'tag', $params );
						wp_enqueue_script( 'tag' );
					}
				}
			} else {
				$params = array(
					'options' => array(
						'horizon_domain' => $options['sailthru_horizon_domain'],
					),
				);

				$concierge = get_option( 'sailthru_concierge_options' );

				if ( $concierge['sailthru_concierge_is_on'] ) {
					$params['concierge']['enabled']      = (bool) true;
					$params['concierge']['from']         = isset( $concierge['sailthru_concierge_from'] ) ? $concierge['sailthru_concierge_from'] : 'bottom';
					$params['concierge']['delay']        = isset( $concierge['sailthru_concierge_delay'] ) ? $concierge['sailthru_concierge_delay'] : '500';
					$params['concierge']['offsetBottom'] = isset( $concierge['sailthru_concierge_offsetBottom'] ) ? $concierge['sailthru_concierge_offsetBottom'] : '20';

					// threshold.
					if ( ! isset( $concierge['sailthru_concierge_threshold'] ) ) {
						$params['concierge']['threshold'] = 'threshold: 500,';
					} else {
						$params['concierge']['threshold'] = strlen( $concierge['sailthru_concierge_threshold'] ) ? 'threshhold: ' . intval( $concierge['sailthru_concierge_threshold'] ) . ',' : 'threshold: 500,';
					}

					if ( isset( $concierge['sailthru_concierge_filter'] ) ) {
						//remove whitespace around the commas
						$tags_filtered                 = preg_replace( '/\s*([\,])\s*/', '$1', $concierge['sailthru_concierge_filter'] );
						$params['concierge']['filter'] = strlen( $concierge['sailthru_concierge_filter'] ) > 0 ? "{tags: '" . esc_js( $tags_filtered ) . "'}" : '';
					}

					// cssPath.
					if ( ! isset( $concierge['sailthru_concierge_cssPath'] ) ) {
						$params['concierge']['cssPath'] = 'https://ak.sail-horizon.com/horizon/recommendation.css';
					} else {
						$params['concierge']['cssPath'] = strlen( $concierge['sailthru_concierge_cssPath'] ) > 0 ? $concierge['sailthru_concierge_cssPath'] : 'https://ak.sail-horizon.com/horizon/recommendation.css';
					}
				}

				if ( ! is_404() && ! is_preview() && apply_filters( 'sailthru_add_horizon_js', true ) ) {
					wp_enqueue_script( 'horizon_js', '//ak.sail-horizon.com/horizon/v1.js' );
					wp_register_script( 'tag', plugin_dir_url( __DIR__ ) . 'js/horizon.js' );
					wp_localize_script( 'tag', 'tag', $params );
					wp_enqueue_script( 'tag' );
				}
				// Horizon parameters.
			}
		} // end if sailthru setup is complete

	} // end register_plugin_scripts



	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/
	/**
	 * Add a top-level Sailthru menu and its submenus.
	 */
	function sailthru_menu() {

		$options = get_option( 'sailthru_setup_options' );

		$sailthru_menu                       = add_menu_page(
			'Sailthru',           // The value used to populate the browser's title bar when the menu page is active
			__( 'Sailthru', 'sailthru-for-wordpress' ),   // The text of the menu in the administrator's sidebar
			'manage_options',         // What roles are able to access the menu
			'sailthru_configuration_page',      // The ID used to bind submenu items to this menu
			array( $this, 'load_sailthru_admin_display' ),  // The callback function used to render this menu
			SAILTHRU_PLUGIN_URL . 'img/sailthru-menu-icon.png'  // The icon to represent the menu item
		);
		$this->admin_views[ $sailthru_menu ] = 'sailthru_configuration_page';

		$redundant_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Settings', 'sailthru-for-wordpress' ),
			__( 'Settings', 'sailthru-for-wordpress' ),
			'manage_options',
			'sailthru_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $redundant_menu ] = 'sailthru_configuration_page';

		if ( sailthru_verify_setup() ) {

			if ( isset( $options['sailthru_js_type'] ) && 'horizon_js' === $options['sailthru_js_type'] ) {

				$concierge_menu                       = add_submenu_page(
					'sailthru_configuration_page',       // The ID of the top-level menu page to which this submenu item belongs
					__( 'Concierge Options', 'sailthru-for-wordpress' ), // The value used to populate the browser's title bar when the menu page is active
					__( 'Concierge Options', 'sailthru-for-wordpress' ), // The label of this submenu item displayed in the menu
					'manage_options',          // What roles are able to access this submenu item
					'concierge_configuration_page',       // The ID used to represent this submenu item
					array( $this, 'load_sailthru_admin_display' )   // The callback function used to render the options for this submenu item
				);
				$this->admin_views[ $concierge_menu ] = 'concierge_configuration_page';

				$scout_menu                       = add_submenu_page(
					'sailthru_configuration_page',
					__( 'Scout Options', 'sailthru-for-wordpress' ),
					__( 'Scout Options', 'sailthru-for-wordpress' ),
					'manage_options',
					'scout_configuration_page',
					array( $this, 'load_sailthru_admin_display' )
				);
				$this->admin_views[ $scout_menu ] = 'scout_configuration_page';

			}

			$scout_menu                       = add_submenu_page(
				'sailthru_configuration_page',
				__( 'List Signup Options', 'sailthru-for-wordpress' ),
				__( 'List Signup Options', 'sailthru-for-wordpress' ),
				'manage_options',
				'custom_fields_configuration_page',
				array( $this, 'load_sailthru_admin_display' )
			);
			$this->admin_views[ $scout_menu ] = 'customforms_configuration_page';

			$forms_menu                       = add_submenu_page(
				'customforms_configuration_page',
				__( 'Custom Forms', 'sailthru-for-wordpress' ),
				__( 'Custom Forms', 'sailthru-for-wordpress' ),
				'manage_options',
				'customforms_configuration_page',
				array( $this, 'load_sailthru_admin_display' )
			);
			$this->admin_views[ $forms_menu ] = 'customforms_configuration_page';
		}
	} // end sailthru_menu

	/**
	 * Renders a simple page to display for the theme menu defined above.
	 */
	function load_sailthru_admin_display() {

		$active_tab = empty( $this->views[ current_filter() ] ) ? '' : $this->views[ current_filter() ];
		// display html
		include SAILTHRU_PLUGIN_PATH . 'views/admin.php';

	} // end sailthru_admin_display

	/**
	 * Renders Horizon specific meta tags in the <head></head>
	 */
	function sailthru_horizon_meta_tags() {


		// only do this on pages and posts
		if ( ! is_single() && ! is_page() ) {
			return;
		}

		// filter to disable all output
		if ( false === apply_filters( 'sailthru_horizon_meta_tags_enable', true ) ) {
			return;
		}

		global $post;

		$post_object = get_post();

		$horizon_tags = array();

		// date
		$post_date                     = get_the_date( 'Y-m-d H:i:s' );
		$horizon_tags['sailthru.date'] = esc_attr( $post_date );

		// title
		$post_title                     = get_the_title();
		$horizon_tags['sailthru.title'] = esc_attr( $post_title );

		// tags in the order of priority
		// first sailthru tags
		$post_tags = get_post_meta( $post_object->ID, 'sailthru_meta_tags', true );

		// WordPress tags
		if ( empty( $post_tags ) ) {
			$post_tags = get_the_tags();
			if ( $post_tags ) {
				$post_tags = esc_attr( implode( ', ', wp_list_pluck( $post_tags, 'name' ) ) );
			}
		}

		// WordPress categories
		if ( empty( $post_tags ) ) {
			$post_categories = get_the_category( $post_object->ID );
			foreach ( $post_categories as $post_category ) {
				$post_tags .= $post_category->name . ', ';
			}
			$post_tags = substr( $post_tags, 0, -2 );
		}

		if ( ! empty( $post_tags ) ) {
			$horizon_tags['sailthru.tags'] = $post_tags;
		}

		// author << works on display name. best option?
		$post_author = get_the_author();
		if ( ! empty( $post_author ) ) {
			$horizon_tags['sailthru.author'] = $post_author;
		}

		// description
		$post_description = get_the_excerpt();
		if ( empty( $post_description ) ) {
			$excerpt_length = 250;
			// get the entire post and then strip it down to just sentences.
			$text             = $post_object->post_content;
			$text             = apply_filters( 'the_content', $text );
			$text             = str_replace( ']]>', ']]>', $text );
			$text             = strip_shortcodes( $text );
			$text             = wp_strip_all_tags( $text );
			$text             = substr( $text, 0, $excerpt_length );
			$post_description = $this->reverse_strrchr( $text, '.', 1 );
		}
		$horizon_tags['sailthru.description'] = esc_html( $post_description );

		// image & thumbnail
		if ( has_post_thumbnail( $post_object->ID ) ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'concierge-thumb' );

			$post_image                           = $image[0];
			$horizon_tags['sailthru.image.full']  = esc_attr( $post_image );
			$post_thumbnail                       = $thumb[0];
			$horizon_tags['sailthru.image.thumb'] = $post_thumbnail;
		}

		// expiration date
		$post_expiration = get_post_meta( $post_object->ID, 'sailthru_post_expiration', true );

		if ( ! empty( $post_expiration ) ) {
			$horizon_tags['sailthru.expire_date'] = esc_attr( $post_expiration );
		}

		$horizon_tags = apply_filters( 'sailthru_horizon_meta_tags', $horizon_tags, $post_object );

		echo "\n\n<!-- BEGIN Sailthru Horizon Meta Information -->\n";
		foreach ( (array) $horizon_tags as $tag_name => $tag_content ) {
			if ( empty( $tag_content ) ) {
				continue; // Don't ever output empty tags
			}
			$meta_tag    = sprintf( '<meta name="%s" content="%s" />', esc_attr( $tag_name ), esc_attr( $tag_content ) );
			echo wp_kses( apply_filters( 'sailthru_horizon_meta_tags_output', $meta_tag ), array( 'meta' => array( 'name' => array(), 'content' => array() ) ) );
			echo  "\n";
		}
		echo "<!-- END Sailthru Horizon Meta Information -->\n\n";


	} // sailthru_horizon_meta_tags


	/*-------------------------------------------
	 * Utility Functions
	 *------------------------------------------*/

	/*
	 * Returns the portion of haystack which goes until the last occurrence of needle
	 * Credit: http://www.wprecipes.com/wordpress-improved-the_excerpt-function
	 */
	function reverse_strrchr( $haystack, $needle, $trail ) {
		return strrpos( $haystack, $needle ) ? substr( $haystack, 0, strrpos( $haystack, $needle ) + $trail ) : false;
	}


	/*--------------------------------------------*
	 * Hooks
	 *--------------------------------------------*/

	/**
	 * Introduces the meta box for expiring content,
	 * and a meta box for Sailthru tags.
	 */
	public function sailthru_post_metabox() {

		add_meta_box(
			'sailthru-post-data',
			__( 'Sailthru Post Data', 'sailthru' ),
			array( $this, 'post_metabox_display' ),
			array ('post', 'page'),
			'side',
			'high'
		);

	} // sailthru_post_metabox

	/**
	 * Adds the input box for the post meta data.
	 *
	 * @param object  $post The post to which this information is going to be saved.
	 */
	public function post_metabox_display( $post ) {

		$sailthru_post_expiration = get_post_meta( $post->ID, 'sailthru_post_expiration', true );
		$sailthru_meta_tags       = get_post_meta( $post->ID, 'sailthru_meta_tags', true );

		wp_nonce_field( plugin_basename( __FILE__ ), $this->nonce );

		// post expiration
		echo '<p><strong>Sailthru Post Expiration</strong></p>';
		echo  '<input id="sailthru_post_expiration" type="text" placeholder="YYYY-MM-DD" name="sailthru_post_expiration" value="' . esc_attr( $sailthru_post_expiration ) . '" size="25" class="datepicker" />';
		echo  '<p class="description">';
		echo  'Flash sales, events and some news stories should not be recommended after a certain date and time. Use this Sailthru-specific meta tag to prevent Horizon from suggesting the content at the given point in time. <a href="http://docs.sailthru.com/documentation/products/horizon-data-collection/horizon-meta-tags" target="_blank">More information can be found here</a>.';
		echo  '</p><!-- /.description -->';

		// post meta tags
		echo  '<p>&nbsp;</p>';
		echo  '<p><strong>Sailthru Meta Tags</strong></p>';
		echo  '<input id="sailthru_meta_tags" type="text" name="sailthru_meta_tags" value="' . esc_attr( $sailthru_meta_tags ) . '" size="25"  />';
		echo  '<p class="description">';
		echo  'Tags are used to measure user interests and later to send them content customized to their tastes.';
		echo  '</p><!-- /.description -->';
		echo  '<p class="howto">Separate tags with commas</p>';

	} // end post_media

	/**
	 * Determines whether or not the current user has the ability to save meta data associated with this post.
	 *
	 * @param int     $post_id The ID of the post being save
	 * @param bool    Whether or not the user has the ability to save this post.
	 */
	public function save_custom_meta_data( $post_id ) {

		// First, make sure the user can save the post
		if ( $this->user_can_save( $post_id, $this->nonce ) ) {

			// Did the user set an expiry date, or are they clearing an old one?
			if ( ! empty( $_POST['sailthru_post_expiration'] ) && isset( $_POST['sailthru_post_expiration'] )
				|| get_post_meta( $post_id, 'sailthru_post_expiration', true ) ) {

				$expiry_time = strtotime( sanitize_text_field( $_POST['sailthru_post_expiration'] ) );

				if ( $expiry_time ) {
					$expiry_date = date( 'Y-m-d', $expiry_time );

					// Save the date. hehe.
					update_post_meta( $post_id, 'sailthru_post_expiration', esc_attr( $expiry_date ) );
				} else {
					update_post_meta( $post_id, 'sailthru_post_expiration', null );
				}
			} // end if

			// Did the user set some meta tags, or are they clearing out old tags?
			if ( ! empty( $_POST['sailthru_meta_tags'] ) && isset( $_POST['sailthru_meta_tags'] )
				|| get_post_meta( $post_id, 'sailthru_meta_tags', true ) ) {

				//remove trailing comma
				$meta_tags = rtrim( sanitize_text_field( $_POST['sailthru_meta_tags'] ), ',' );
				update_post_meta( $post_id, 'sailthru_meta_tags', esc_attr( $meta_tags ) );

			}
		} // end if

	} // end save_custom_meta_data

	/*--------------------------------------------*
	 * Helper Functions
	 *--------------------------------------------*/

	/**
	 * FROM: https://github.com/tommcfarlin/WordPress-Upload-Meta-Box
	 * Determines whether or not the current user has the ability to save meta data associated with this post.
	 *
	 * @param int     $post_id The ID of the post being save
	 * @param bool    Whether or not the user has the ability to save this post.
	 */
	function user_can_save( $post_id, $nonce ) {

		$is_autosave    = wp_is_post_autosave( $post_id );
		$is_revision    = wp_is_post_revision( $post_id );
		$is_valid_nonce = ( isset( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], plugin_basename( __FILE__ ) ) );

		// Return true if the user is able to save; otherwise, false.
		return ! ( $is_autosave || $is_revision ) && $is_valid_nonce;

	} // end user_can_save


} // end class
