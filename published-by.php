<?php
/**
 * Plugin Name: Published By
 * Version:     1.0.1
 * Plugin URI:  http://coffee2code.com/wp-plugins/published-by/
 * Author:      Scott Reilly
 * Author URI:  http://coffee2code.com/
 * Text Domain: published-by
 * Domain Path: /lang/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Description: Track which user actually published a post, separate from who created the post. Display that info as a column in admin post listings.
 *
 * Compatible with WordPress 3.6 through 3.8+.
 *
 * =>> Read the accompanying readme.txt file for instructions and documentation.
 * =>> Also, visit the plugin's homepage for additional information and updates.
 * =>> Or visit: https://wordpress.org/plugins/published-by/
 *
 * @package Published_By
 * @author  Scott Reilly
 * @version 1.0.1
 */

/*
TODO:
	* Add template tags that parallel author-related template tags, such as:
	  get_the_publisher, the_publisher, get_publisher_id, get_publisher_link
	* Provisions to disable/enable per post_type
*/

/*
	Copyright (c) 2014 by Scott Reilly (aka coffee2code)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'c2c_PublishedBy' ) ) :

class c2c_PublishedBy {

	private static $meta_key      = 'c2c-published-by';
	private static $field         = 'published_by';
	private static $post_statuses = array();

	/**
	 * Returns version of the plugin.
	 *
	 * @since 1.0
	 */
	public static function version() {
		return '1.0.1';
	}

	/**
	 * Hooks actions and filters.
	 *
	 * @since 1.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'do_init' ) );
	}

	/**
	 * Performs initializations on the 'init' action.
	 *
	 * @since 1.0
	 */
	public static function do_init() {
		self::$post_statuses = apply_filters( 'c2c_published_by_post_status', array( 'private', 'publish' ) );

		// Load textdomain
		load_plugin_textdomain( 'published-by', false, basename( dirname( __FILE__ ) ) );

		// Register hooks
		add_filter( 'manage_posts_columns',        array( __CLASS__, 'add_post_column' )               );
		add_action( 'manage_posts_custom_column',  array( __CLASS__, 'handle_column_data' ),     10, 2 );
		add_filter( 'manage_pages_columns',        array( __CLASS__, 'add_post_column' )               );
		add_action( 'manage_pages_custom_column',  array( __CLASS__, 'handle_column_data' ),     10, 2 );

		add_action( 'load-edit.php',               array( __CLASS__, 'add_admin_css' )                 );
		add_action( 'transition_post_status',      array( __CLASS__, 'transition_post_status' ), 10, 3 );
		add_filter( 'is_protected_meta',           array( __CLASS__, 'hide_meta' ),              10, 2 );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'show_publisher' )                );
	}

	/**
	 * Determines if the Published By column should be shown.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	private static function include_column() {
		return ( ! isset( $_GET['post_status'] ) || in_array( $_GET['post_status'], self::$post_statuses ) );
	}

	/**
	 * Adds hook to outputs CSS for the display of the Published By column if
	 * on the appropriate admin page.
	 *
	 * @since 1.0
	 */
	public static function add_admin_css() {
		if ( ! self::include_column() ) {
			return;
		}

		add_action( 'admin_head', array( __CLASS__, 'admin_css' ) );
	}

	/**
	 * Outputs CSS for the display of the Published By column.
	 *
	 * @since 1.0
	 */
	public static function admin_css() {
		echo "<style type='text/css'>.fixed .column-" . self::$field . " { width: 10%; }</style>\n";
	}

	/**
	 * Displays the publisher of the post in the publish metabox.
	 *
	 * @since 1.0
	 */
	public static function show_publisher() {
		global $post;

		if ( ! in_array( $post->post_status, self::$post_statuses ) ) {
			return;
		}

		$publisher_id = self::get_publisher_id( $post->ID );

		if ( ! $publisher_id ) {
			return;
		}

		$publisher = get_userdata( $publisher_id );

		echo '<div class="misc-pub-section curtime misc-pub-curtime">';
		echo sprintf( __( 'Published by: %s', 'published-by' ), sanitize_text_field( $publisher->display_name ) );
		echo '</div>';
	}

	/**
	 * Adds a column to show who published the post/page.
	 *
	 * @since 1.0
	 *
	 * @param  array $posts_columns Array of post column titles.
	 * @return array The $posts_columns array with the 'published by' column's title added.
	 */
	public static function add_post_column( $posts_columns ) {
		if ( self::include_column() ) {
			$posts_columns[ self::$field ] = __( 'Published By', 'published-by' );
		}

		return $posts_columns;
	}

	/**
	 * Outputs the user who published the post for each post listed in the post
	 * listing table in the admin.
	 *
	 * @since 1.0
	 *
	 * @param string $column_name The name of the column.
	 * @param int    $post_id     The id of the post being displayed.
	 */
	public static function handle_column_data( $column_name, $post_id ) {
		if ( ! self::include_column() ) {
			return;
		}

		if ( self::$field === $column_name ) {
			$publisher_id = self::get_publisher_id( $post_id );
			if ( $publisher_id ) {
				$publisher = get_userdata( $publisher_id );
				echo sanitize_text_field( $publisher->display_name );
			}
		}
	}

	/**
	 * Hides the meta key from the custom field dropdown.
	 *
	 * @since 1.0
	 *
	 * @param  bool   $protected Is the meta key protected?
	 * @param  string $meta_key  The meta key
	 * @return bool
	 */
	public static function hide_meta( $protected, $meta_key ) {
		if ( self::$meta_key == $meta_key ) {
			return true;
		}

		return $protected;
	}

	/**
	 * Records the publisher of the post.
	 *
	 * @since 1.0
	 *
	 * @param string  $new_status New post status
	 * @param string  $old_status Old post status
	 */
	public static function transition_post_status( $new_status, $old_status, $post ) {
		// Only concerned with posts changing post status
		if ( $new_status == $old_status ) {
			return;
		}

		// Only concerned with posts being published
		if ( 'publish' !== $new_status ) {
			return;
		}

		// Can only save publishing user ID if one can be obtained
		if ( $current_user_id = get_current_user_id() ) {
			update_post_meta( $post->ID, self::$meta_key, $current_user_id );
		}
	}

	/**
	 * Returns the ID of the user who published the post.
	 *
	 * @since 1.0
	 *
	 * @param  int $post_id The id of the post being displayed.
	 * @return int The ID of the user who published the post.
	 */
	public static function get_publisher_id( $post_id ) {
		$publisher_id = 0;
		$post         = get_post( $post_id );

		if ( $post && in_array( get_post_status( $post_id ), self::$post_statuses ) ) {

			do {

				// Use publisher id saved in custom field by the plugin.
				if ( $publisher_id = get_post_meta( $post_id, self::$meta_key, true ) ) {
					break;
				}

				// At this point, we're guessing who published the post.

				// Allow disabling of the various checks to guess who published the post.
				if ( apply_filters( 'c2c_published_by_skip_guessing', false, $post_id ) ) {
					break;
				}

				// Use the user WP saved as the latest editor
				// NOTE: get_the_modified_author() does not currently accept a
				// $post argument and therefore cannot be used directly as hoped.
				if ( $publisher_id = get_post_meta( $post_id, '_edit_last', true ) ) {
					break;
				}

				// Guess who it is based on latest revision.
				$post_revisions  = wp_get_post_revisions( $post_id );
				$latest_revision = array_shift( $post_revisions );
				if ( $latest_revision ) {
					$rev = wp_get_post_revision( $latest_revision );
					$publisher_id = $rev->post_author;
					break;
				}

				// If no publisher at this point, then assume post author
				$publisher_id = $post->post_author;
				break;

			} while ( 0 );

		}

		return $publisher_id;
	}

} // end c2c_PublishedBy

c2c_PublishedBy::init();

endif; // end if !class_exists()
