<?php

class Test_WordPress_Integration extends MB_UnitTestCase {
	public function test_wrappers_loaded(): void {
		$this->assertTrue(
			function_exists( 'metabolic\metabolize' ),
			'wrapper functions have not been loaded'
		);
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
				$this->assertFalse(
					has_filter( $filter, [ $this->metabolic, '_queue' ] ),
					"Metabolic::_queue is hooked into $filter"
				);
			}
		}
	}
}
