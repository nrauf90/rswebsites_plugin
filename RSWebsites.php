<?php

/**
 * Plugin Name:       RS Websites
 * Plugin URI:        https://github.com/nrauf90/rswebsites_plugin
 * Description:       Using this plugin user can store there websites, after store website plugin will pull source code of provided website and on the backend admin and editors can see those website. Admin can only see website source code
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Muhammad Noman Rauf
 * Author URI:        https://github.com/nrauf90
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */
class RSWebsites {
	/**
	 * A reference to an instance of this class.
	 */
	private static $instance;

	/**
	 * The array of templates that this plugin tracks.
	 */
	protected $templates;


	/**
	 * Returns an instance of this class.
	 * @return RSWebsites
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new RSWebsites();
		}

		return self::$instance;

	}

	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */

	private function __construct() {

		add_action( "init", array( $this, "register_websites_post_type" ), 0 ); // register websites post type
		add_action( 'admin_menu', array(
			$this,
			'register_websites_menu'
		) );
		add_filter( "add_meta_boxes", array(
			$this,
			"alter_meta_boxes_websites"
		) ); // add custom meta and remove publish and update button for websites
		add_filter( "post_row_actions", array(
			$this,
			"modify_list_row_actions"
		), 10, 2 ); // remove extra row actions (quick edit and view) for websites
		add_action( "init", array(
			$this,
			"fix_capability_create"
		), 100 ); // remove create new website capability for all users
		add_filter( "theme_page_templates", array(
			$this,
			"register_website_template"
		), 10, 3 ); // register custom template for form
		add_filter( "template_include", array(
			$this,
			"select_website_template"
		), 99 ); // apply custom template when user select website template
		add_action( "wp_enqueue_scripts", array(
			$this,
			"enqueue_script"
		) ); // enqueue custom plugin script and style in theme
		add_action( "wp_ajax_add_new_website", array(
			$this,
			"add_new_website"
		) ); // create action to get form data and store into database
		add_action( "wp_ajax_nopriv_add_new_website", array( $this, "add_new_website" ) );

	}

	/**
	 * function register custom post type websites
	 * @return void
	 */
	public function register_websites_post_type() {

		$labels = array(
			"name"                  => _x( "Websites", "Post Type General Name", "rs_websites" ),
			"singular_name"         => _x( "Website", "Post Type Singular Name", "rs_websites" ),
			"menu_name"             => __( "Websites", "rs_websites" ),
			"name_admin_bar"        => __( "Websties", "rs_websites" ),
			"archives"              => __( "Item Archives", "rs_websites" ),
			"attributes"            => __( "Item Attributes", "rs_websites" ),
			"parent_item_colon"     => __( "Parent Item:", "rs_websites" ),
			"all_items"             => __( "All Websites", "rs_websites" ),
			"add_new_item"          => __( "Add New", "rs_websites" ),
			"add_new"               => __( "Add New", "rs_websites" ),
			"new_item"              => __( "Add New", "rs_websites" ),
			"edit_item"             => __( "Edit", "rs_websites" ),
			"update_item"           => __( "Update", "rs_websites" ),
			"view_item"             => __( "View Item", "rs_websites" ),
			"view_items"            => __( "View Items", "rs_websites" ),
			"search_items"          => __( "Search Item", "rs_websites" ),
			"not_found"             => __( "Not found", "rs_websites" ),
			"not_found_in_trash"    => __( "Not found in Trash", "rs_websites" ),
			"featured_image"        => __( "", "rs_websites" ),
			"set_featured_image"    => __( "", "rs_websites" ),
			"remove_featured_image" => __( "", "rs_websites" ),
			"use_featured_image"    => __( "", "rs_websites" ),
			"insert_into_item"      => __( "", "rs_websites" ),
			"uploaded_to_this_item" => __( "Uploaded to this item", "rs_websites" ),
			"items_list"            => __( "Items list", "rs_websites" ),
			"items_list_navigation" => __( "Items list navigation", "rs_websites" ),
			"filter_items_list"     => __( "Filter items list", "rs_websites" ),
		);

		$args = array(
			"label"               => __( "Website", "rs_websites" ),
			"description"         => __( "Websites save by users", "rs_websites" ),
			"labels"              => $labels,
			"supports"            => array( "title" ),
			"hierarchical"        => false,
			"public"              => false,
			"show_ui"             => true,
			"show_in_menu"        => false,
			"menu_position"       => 5,
			"show_in_admin_bar"   => false,
			"show_in_nav_menus"   => false,
			"can_export"          => true,
			"has_archive"         => true,
			"exclude_from_search" => false,
			"publicly_queryable"  => true,
			"capability_type"     => "post",
		);
		register_post_type( "websites", $args );

	}

	/**
	 * function remove add and edit side bar actions
	 * @return void
	 */
	public function alter_meta_boxes_websites() {
		add_meta_box(
			'url',
			__( 'Website URL', 'sitepoint' ),
			array( $this, 'create_website_url_metabox' ),
			'websites'
		);
		if ( current_user_can('administrator') ) {
			add_meta_box(
				'code',
				__( 'Source Code', 'sitepoint' ),
				array( $this, 'create_website_code_metabox' ),
				'websites'
			);
		}

		remove_meta_box( "submitdiv", "websites", "side" );

	}

	/**
	 * Function remove post row actions  (quick edit and view) on list page.
	 *
	 * @param $action
	 * @param $post
	 *
	 * @return mixed
	 */
	public function modify_list_row_actions( $action, $post ) {
		if ( $post->post_type == "websites" ) {
			unset( $action["inline hide-if-no-js"] );
			unset( $action["view"] );
		}

		return $action;
	}

	/**
	 * function remove create post create capability for all users
	 * @return void
	 */
	public function fix_capability_create() {
		$post_types = get_post_types( array(), "objects" );
		foreach ( $post_types as $post_type ) {
			if ( $post_type->name === "websites" ) {
				$cap                          = "create_websites";
				$post_type->cap->create_posts = $cap;
				map_meta_cap( $cap, 1 );
			}
		}
	}

	/**
	 * function register new template for add new website template
	 *
	 * @param $page_templates
	 * @param $theme
	 * @param $post
	 *
	 * @return mixed
	 */
	public function register_website_template( $page_templates, $theme, $post ) {
		$page_templates["website-template"] = "Add Website Template";

		return $page_templates;
	}

	/**
	 * check if add website template is selected we will load our website template
	 *
	 * @param $template
	 *
	 * @return mixed|string
	 */
	public function select_website_template( $template ) {
		global $post;
		$page_tenplate_slug = get_page_template_slug( $post->ID );
		if ( $page_tenplate_slug === "website-template" ) {
			$template = plugin_dir_path( __FILE__ ) . "templates/" . $page_tenplate_slug . ".php";
		}

		return $template;
	}

	public function enqueue_script() {
		wp_enqueue_script( "website-script", plugin_dir_url( __FILE__ ) . "/js/script.js", array( "jquery" ), "", true );
		wp_enqueue_style( "website-style", plugin_dir_url( __FILE__ ) . "/css/style.css" );
	}

	public function add_new_website() {
		if ( ! wp_verify_nonce( $_REQUEST["_wpnonce"] ) ) {
			wp_send_json( array( "error" => true, "message" => "Invalid Request" ) );
		} else {
			$post    = array(
				"post_title"  => $_REQUEST["name"],
				"post_status" => "publish",
				"post_type"   => "websites"
			);
			$post_id = wp_insert_post( $post );
			update_post_meta( $post_id, "_website_url", $_REQUEST["url"] );
			$response = wp_remote_get( $_REQUEST["url"], array(
				'headers' => array(
					'Accept' => 'application/json',
				)
			) );
			if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
				$responseBody = json_decode( $response['body'] );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					update_post_meta( $post_id, "_website_code", "No source code found!" );
				}
			} else {
				update_post_meta( $post_id, "_website_code", $response['body'] );
			}
			wp_send_json( array( "success" => true, "message" => "Website added successfully" ) );
		}

	}

	function create_website_url_metabox( $post ) {
		$value = get_post_meta( $post->ID, '_website_url', true );
		echo '<input style="width:100%" id="url" type="text" name="_website_url" value="' . esc_attr( $value ) . '" readonly/>';
	}

	function create_website_code_metabox( $post ) {
		if ( current_user_can('administrator') ) {
			$value = get_post_meta( $post->ID, '_website_code', true );
			echo '<textarea style="width:100%" id="code" name="_website_code" row="30" readonly>' . esc_attr( $value ) . '</textarea>';
		}
	}

	/**
	 * Register a custom menu page.
	 */
	function register_websites_menu() {
		add_menu_page(
			__( 'Websites', 'rs_websites' ),
			'Websites',
			 'edit_others_posts',
			'edit.php?post_type=websites',
			'',
			'',
			6
		);
	}

}

add_action( "plugins_loaded", array( "RSWebsites", "get_instance" ) );
