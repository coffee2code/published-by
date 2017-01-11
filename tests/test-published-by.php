<?php

defined( 'ABSPATH' ) or die();

class Published_By_Test extends WP_UnitTestCase {

	protected static $meta_key = 'c2c-published-by';

	/**
	 * Test REST Server
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	public function setUp() {
		parent::setUp();

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server;
		do_action( 'rest_api_init' );
	}

	public function tearDown() {
		parent::tearDown();
		$this->unset_current_user();
	}


	//
	//
	// HELPER FUNCTIONS
	//
	//


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


	//
	//
	// FUNCTIONS FOR HOOKING ACTIONS/FILTERS
	//
	//


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


	//
	//
	// TESTS
	//
	//


	public function test_plugin_version() {
		$this->assertEquals( '1.1', c2c_PublishedBy::version() );
	}

	public function test_class_is_available() {
		$this->assertTrue( class_exists( 'c2c_PublishedBy' ) );
	}

	public function test_meta_key_not_created_for_post_saved_as_draft() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		$post = get_post( $post_id );
		wp_update_post( $post );

		$this->assertEmpty( get_post_meta( $post_id, self::$meta_key, true ) );
	}

	public function test_meta_key_not_created_for_post_saved_as_pending() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		$post = get_post( $post_id );
		$post->post_status = 'pending';
		wp_update_post( $post );

		$this->assertEmpty( get_post_meta( $post_id, self::$meta_key, true ) );
	}

	public function test_meta_key_created_for_published_post() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		wp_publish_post( $post_id );

		$this->assertEquals( $user_id, c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEquals( $user_id, get_post_meta( $post_id, self::$meta_key, true ) );
	}

	public function test_meta_key_updated_for_republished_post() {
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

	public function test_meta_used_as_publisher_when_present() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		wp_publish_post( $post_id );

		$this->assertEquals( $user_id, c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEquals( $user_id, get_post_meta( $post_id, self::$meta_key, true ) );
	}

	public function test_author_of_latest_revision_used_as_publisher_when_meta_not_present() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_author' => $author_id ) );
		$user_id   = $this->create_user();
		wp_save_post_revision( $post_id );

		$this->assertEquals( $user_id, c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEmpty(  get_post_meta( $post_id, self::$meta_key, true ) );
	}

	public function test_author_of_post_used_as_publisher_when_meta_or_revisions_not_present() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_author' => $author_id ) );

		$this->assertEquals( $author_id, c2c_PublishedBy::get_publisher_id( $post_id ) );
	}

	public function test_nothing_returned_if_post_is_not_published() {
		$author_id = $this->create_user( false );
		$post_id   = $this->factory->post->create( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$user_id   = $this->create_user();

		// Set the custom field, as if it had been set on a previous publish
		$this->set_published_by( $post_id, $user_id );

		$this->assertEmpty(  c2c_PublishedBy::get_publisher_id( $post_id ) );
		$this->assertEquals( $user_id, get_post_meta( $post_id, self::$meta_key, true ) );
	}

	public function test_editing_published_post_does_not_change_publisher() {
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


	/*
	 * c2c_PublishedBy::get_user_url()
	 */


	public function test_get_user_url() {
		$this->assertEquals( self_admin_url( 'user-edit.php?user_id=2' ), c2c_PublishedBy::get_user_url( 2 ) );
		$this->assertEquals( self_admin_url( 'user-edit.php?user_id=3' ), c2c_PublishedBy::get_user_url( '3' ) );
	}

	public function test_get_user_url_with_invalid_user_id() {
		$this->assertEmpty( c2c_PublishedBy::get_user_url( 0 ) );
		$this->assertEmpty( c2c_PublishedBy::get_user_url( 'hello' ) );
	}


	/*
	 * REST API
	 */


	public function test_meta_is_registered() {
		$this->assertTrue( registered_meta_key_exists( 'post', self::$meta_key ) );
	}

	public function test_rest_post_request_includes_meta() {
		$author_id = $this->create_user( false );
		$post_id = $this->factory->post->create( array( 'post_status' => 'publish', 'post_author' => $author_id ) );
		add_post_meta( $post_id, self::$meta_key, $author_id );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'meta', $data );

		$meta = (array) $data['meta'];
		$this->assertArrayHasKey( self::$meta_key, $meta );
		$this->assertEquals( $author_id, $meta[ self::$meta_key ] );
	}

}
