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
			has_action( 'shutdown', [ metabolic\Metabolic::getInstance(), '_shutdown' ] ) === 10,
			'Metabolic::_shutdown not hooked into WordPress shutdown hook'
		);
	}
}
