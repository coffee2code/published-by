<?php

class Published_By_Test extends WP_UnitTestCase {

	protected static $meta_key = 'c2c-published-by';

	function tearDown() {
		parent::tearDown();
		$this->unset_current_user();
	}



	/**
	 * HELPER FUNCTIONS
	 */



	private function create_user( $set_as_current = true ) {
		$user_id = $this->factory->user->create();
		if ( $set_as_current ) {
			wp_set_current_user( $user_id );
		}
		return $user_id;
	}

	// helper function, unsets current user globally. Taken from post.php test.
	private function unset_current_user() {
		global $current_user, $user_ID;

		$current_user = $user_ID = null;
    }

	private function set_published_by( $post_id, $user_id = '' ) {
		add_post_meta( $post_id, self::$meta_key, $user_id );
	}



	/**
	 * FUNCTIONS FOR HOOKING ACTIONS/FILTERS
	 */

	public function query_for_posts( $text ) {
		$q = new WP_Query( array( 'post_type' => 'post' ) );
		$GLOBALS['custom_query'] = $q;
		return $text;
	}

	public function filter_on_special_meta( $wpquery ) {
		$wpquery->query_vars['meta_query'][] = array(
			'key'     => 'special',
			'value'   => '1',
			'compare' => '='
		);
	}



	/**
	 * TESTS
	 */



	function test_plugin_version() {
		$this->assertEquals( '1.0.2', c2c_PublishedBy::version() );
	}

	function test_class_is_available() {
		$this->assertTrue( class_exists( 'c2c_PublishedBy' ) );
	}

	function test_meta_key_not_created_for_post_saved_as_draft() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		$post = get_post( $post_id );
		wp_update_post( $post );

		$this->assertEmpty( get_post_meta( $post_id, self::$meta_key, true ) );
	}

	function test_meta_key_not_created_for_post_saved_as_pending() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		$post = get_post( $post_id );
		$post->post_status = 'pending';
		wp_update_post( $post );

		$this->assertEmpty( get_post_meta( $post_id, self::$meta_key, true ) );
	}

	function test_meta_key_created_for_published_post() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		wp_publish_post( $post_id );

		$this->assertEquals( $user_id, c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEquals( $user_id, get_post_meta( $post_id, self::$meta_key, true ) );
	}

	function test_meta_key_updated_for_republished_post() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user1_id  = $this->create_user( false );

		$this->set_published_by( $post_id, $user1_id );

		$this->assertEmpty(  c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEquals( $user1_id, get_post_meta( $post_id, self::$meta_key, true ) );

		$user2_id = $this->create_user();

		wp_publish_post( $post_id );

		$this->assertEquals( $user2_id, c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEquals( $user2_id, get_post_meta( $post_id, self::$meta_key, true ) );
	}

	function test_meta_used_as_publisher_when_present() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		wp_publish_post( $post_id );

		$this->assertEquals( $user_id, c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEquals( $user_id, get_post_meta( $post_id, self::$meta_key, true ) );
	}

	function test_author_of_latest_revision_used_as_publisher_when_meta_not_present() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_author' => $author_id ) );
		$user_id   = $this->create_user();
		wp_save_post_revision( $post_id );

		$this->assertEquals( $user_id, c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEmpty(  get_post_meta( $post_id, self::$meta_key, true ) );
	}

	function test_author_of_post_used_as_publisher_when_meta_or_revisions_not_present() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_author' => $author_id ) );

		$this->assertEquals( $author_id, c2c_PublishedBy::get_publisher_id( $post_id ) );
	}

	function test_nothing_returned_if_post_is_not_published() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		// Set the custom field, as if it had been set on a previous publish
		$this->set_published_by( $post_id, $user_id );

		$this->assertEmpty(  c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEquals( $user_id, get_post_meta( $post_id, self::$meta_key, true ) );
	}

	function test_editing_published_post_does_not_change_publisher() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id1  = $this->create_user();

		wp_publish_post( $post_id );

		$this->assertEquals( $user_id1, c2c_PublishedBy::get_publisher_id( $post_id ) );

		$user_id2  = $this->create_user();
		$post      = get_post( $post_id );
		$post->post_title = $post->post_title . ' changed';
		wp_update_post( $post );

		$this->assertEquals( $user_id1, c2c_PublishedBy::get_publisher_id( $post_id ) );
	}

}
