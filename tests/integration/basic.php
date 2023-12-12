<?php

class Test_WordPress_Integration extends MB_UnitTestCase {
	public function test_wrappers_loaded() {
		$this->assertTrue(
			function_exists( 'metabolic\metabolic' ),
			'wrapper functions have not been loaded'
		);
	}
}
