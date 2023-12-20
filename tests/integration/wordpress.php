<?php

class Test_WordPress_Integration extends MB_UnitTestCase {
	public function test_wrappers_loaded(): void {
		$this->assertTrue(
			function_exists( 'metabolic\metabolize' ),
			'wrapper functions have not been loaded'
		);
	}

	public function test_wrapper_functions_exist() {
		$wrapper_functions = [
			'metabolic\metabolize',
			'metabolic\defer_meta_updates',
			'metabolic\commit_meta_updates',
			'metabolic\flush_meta_updates',
		];

		foreach ( $wrapper_functions as $wrapper_function ) {
			$this->assertTrue(
				function_exists( $wrapper_function ),
				"wrapper function $wrapper_function does not exist"
			);
		}
	}

	public function test_shutdown_action_added(): void {
		$this->assertTrue(
			has_action( 'shutdown', [ $this->metabolic, '_shutdown' ] ) === PHP_INT_MAX,
			'Metabolic::_shutdown not hooked into WordPress shutdown hook'
		);
	}

	public function test_meta_hooks_added_when_deferring(): void {
		$types = [ 'post', 'comment', 'term', 'user' ];
		$actions = [ 'get', 'add', 'update', 'delete' ];

		foreach ( $types as $type ) {
			foreach ( $actions as $action ) {
				$filter = "{$action}_{$type}_metadata";
				$callable = $action === 'get' ? '_interrupt' : '_queue';
				$this->assertFalse(
					has_filter( $filter, [ $this->metabolic, $callable ] ),
					"Metabolic::_queue is hooked into $filter without a deferral"
				);
			}
		}

		$this->assertTrue( $this->metabolic->defer() );

		foreach ( $types as $type ) {
			foreach ( $actions as $action ) {
				$filter = "{$action}_{$type}_metadata";
				$callable = $action === 'get' ? '_interrupt' : '_queue';
				$this->assertTrue(
					has_filter( $filter, [ $this->metabolic, $callable ] ) === PHP_INT_MAX,
					"Metabolic::$callable is not hooked into $filter for some reason"
				);
			}
		}
	}

	public function test_add_meta_simplest(): void {
		global $wpdb;

		$post = $this->factory->post->create();
		$this->resetQueries();

		$this->assertTrue( metabolic\defer_meta_updates(), 'defer_meta_updates() returned false' );

		$this->assertTrue( add_post_meta( $post, 'simple', 'value' ), 'add_post_meta() failed' );

		$this->assertQueryCount( 0 );

		$this->assertNull(
			$wpdb->get_var( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'simple'" ),
			'add_post_meta() was not short-circuited'
		);
		$this->resetQueries();

		$this->assertTrue( metabolic\commit_meta_updates() );

		$this->assertQueryCount( 1 );

		$this->assertEquals(
			'value',
			$wpdb->get_var( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'simple'" ),
			'committed add_post_meta() did not work'
		);
	}
}
