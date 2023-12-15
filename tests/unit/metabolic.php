<?php
class Test_Metabolic_Class extends MB_UnitTestCase {
	public function test_final() {
		$class = new ReflectionClass( 'metabolic\Metabolic' );
		$this->assertTrue( $class->isFinal(), 'Metabolic has to be final' );
	}

	public function test_singleton() {
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
}
