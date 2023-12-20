<?php

class Test_Metabolic_Class extends MB_UnitTestCase {
	public function test_final(): void {
		$class = new ReflectionClass( 'metabolic\Metabolic' );
		$this->assertTrue( $class->isFinal(), 'Metabolic has to be final' );
	}

	public function test_singleton(): void {
		$method = new ReflectionMethod( 'metabolic\Metabolic', '__construct' );
		$this->assertTrue( $method->isPrivate(), 'Metabolic has to be a singleton' );

		$metabolic = metabolic\Metabolic::getInstance();

		$this->assertSame(
			$metabolic, metabolic\Metabolic::getInstance(),
			'Metabolic is not functioning like a singleton'
		);

		$this->assertSame(
			$this->metabolic, metabolic\Metabolic::getInstance(),
			'Test Metabolic instance is not functioning like a singleton'
		);
	}

	public function test_invalid_queue_call_no_filter(): void {
		$this->expectExceptionMessage( 'Metabolic::_queue called with no filter.' );
		$this->metabolic->defer();
		$this->metabolic->_queue();
	}

	public function test_invalid_queue_call_invalid_filter(): void {
		$this->expectExceptionMessage( 'Metabolic::_queue called with invalid filter: invalid_test_filter' );
		$this->mock_current_filter( 'invalid_test_filter' );
		$this->metabolic->defer();
		$this->metabolic->_queue();
	}

	public function test_invalid_queue_call_invalid_args(): void {
		$this->expectExceptionMessage( 'Metabolic::_queue called with empty arguments for add_post_metadata' );
		$this->mock_current_filter( 'add_post_metadata' );
		$this->metabolic->defer();
		$this->metabolic->_queue();
	}

	public function test_invalid_queue_call_count_args(): void {
		$this->expectExceptionMessage( 'Metabolic::_queue called with unexpected number of arguments for delete_user_metadata' );
		$this->mock_current_filter( 'delete_user_metadata' );
		$this->metabolic->defer();
		$this->metabolic->_queue( null, 1, 'meta_key', 'meta_value' );
	}

	public function test_invalid_queue_call_non_null_check(): void {
		$this->expectExceptionMessage( 'Metabolic::_queue called with failing short-circuit check for update_term_metadata' );
		$this->mock_current_filter( 'update_term_metadata' );
		$this->metabolic->defer();
		$this->metabolic->_queue( true, 1, 'meta_key', 'meta_value', false );
	}

	public function test_invalid_queue_call_not_deferring(): void {
		$this->expectExceptionMessage( 'Metabolic::_queue not deferring.' );
		$this->mock_current_filter( 'update_term_metadata' );
		$this->metabolic->_queue( null, 1, 'meta_key', 'meta_value', false );
	}

	public function test_queue_simple(): void {
		$this->mock_current_filter( 'add_post_metadata' );
		$this->metabolic->defer();
		$result = $this->metabolic->_queue( null, 1, 'meta_key', 'meta_value', false );
		$this->assertTrue( $result, 'Metabolic::_queue did not return short-circtuit $check for filter' );

		$queue = $this->metabolic->inspect()['queue'];
		$this->assertCount( 1, $queue, 'Queue does not contain one entry' );
		$this->assertEquals( [
			'action' => 'add',
			'type' => 'post',
			'object_id' => 1,
			'meta_key' => 'meta_key',
			'meta_value' => 'meta_value',
			'unique' => false,
		], $queue[0], 'Queue has unexpected data structure and values' );
	}

	public function test_queue_random_data_args(): void {
		$this->metabolic->defer();

		foreach ( [ 'post', 'comment', 'term', 'user' ] as $type ) {
			foreach ( [ 'add', 'update', 'delete' ] as $action ) {
				$this->mock_current_filter( $filter = "{$action}_{$type}_metadata" );

				$object_id = rand( 1, PHP_INT_MAX );
				$meta_key = wp_generate_password( 12, false );
				$meta_value = wp_generate_password();
				$fifth_arg = rand( 0, 1 ) > 0;

				$this->assertTrue(
					$this->metabolic->_queue( null, $object_id, $meta_key, [ 'test' => $meta_value ], $fifth_arg ),
					"Could not run filter queue for $filter"
				);

				$fifth_arg_key = [
					'add' => 'unique',
					'update' => 'prev_value',
					'delete' => 'delete_all',
				][ $action ];

				$queue = end( $this->metabolic->inspect()['queue'] );
				$this->assertEquals( [
					'action' => $action,
					'type' => $type,
					'object_id' => $object_id,
					'meta_key' => $meta_key,
					'meta_value' => [ 'test' => $meta_value ],
					$fifth_arg_key => $fifth_arg,
				], $queue, "Unexpected queue structure and data with $filter" );
			}
		}
	}

	public function test_invalid_interrupt_call_no_filter(): void {
		$this->expectExceptionMessage( 'Metabolic::_interrupt called with no filter.' );
		$this->metabolic->defer();
		$this->metabolic->_interrupt();
	}

	public function test_invalid_interrupt_call_invalid_filter(): void {
		$this->expectExceptionMessage( 'Metabolic::_interrupt called with invalid filter: invalid_test_filter' );
		$this->mock_current_filter( 'invalid_test_filter' );
		$this->metabolic->defer();
		$this->metabolic->_interrupt();
	}

	public function test_invalid_interrupt_call_invalid_args(): void {
		$this->expectExceptionMessage( 'Metabolic::_interrupt called with empty arguments for get_comment_metadata' );
		$this->mock_current_filter( 'get_comment_metadata' );
		$this->metabolic->defer();
		$this->metabolic->_interrupt();
	}

	public function test_invalid_interrupt_call_count_args(): void {
		$this->expectExceptionMessage( 'Metabolic::_interrupt called with unexpected number of arguments for get_user_metadata' );
		$this->mock_current_filter( 'get_user_metadata' );
		$this->metabolic->defer();
		$this->metabolic->_interrupt( null, 1, 'meta_key', true );
	}

	public function test_invalid_interrupt_mismatch_type(): void {
		$this->expectExceptionMessage( 'Metabolic::_interrupt called with mismatched $meta_type (comment != user)' );
		$this->mock_current_filter( 'get_user_metadata' );
		$this->metabolic->defer();
		$this->metabolic->_interrupt( null, 1, 'meta_key', true, 'comment' );
	}

	public function test_invalid_interrupt_call_non_null_check(): void {
		$this->expectExceptionMessage( 'Metabolic::_interrupt called with failing short-circuit check for get_term_metadata' );
		$this->mock_current_filter( 'get_term_metadata' );
		$this->metabolic->defer();
		$this->metabolic->_interrupt( true, 1, 'meta_key', true, 'term' );
	}

	public function test_invalid_interrupt_call_not_deferring(): void {
		$this->expectExceptionMessage( 'Metabolic::_interrupt not deferring.' );
		$this->mock_current_filter( 'get_user_metadata' );
		$this->metabolic->_interrupt( null, 1, 'meta_key', true, 'user' );
	}

	public function test_commit_not_deferring() {
		$this->expectExceptionMessage( 'Metabolic::commit not deferring.' );
		$this->metabolic->commit();
	}

	public function test_defer_after_defer() {
		$this->expectExceptionMessage( 'Metabolism already in progress. Turn on debugging with Metabolic::debug() for debugging information.' );
		$this->metabolic->debug( false );
		$this->metabolic->defer();
		$this->metabolic->defer();
	}
}
