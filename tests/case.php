<?php

class MB_UnitTestCase extends WP_UnitTestCase {
	private ?metabolic\Metabolic $_metabolic = null;

	public function setUp(): void {
		parent::setUp();

		$this->_metabolic = metabolic\Metabolic::getInstance();
	}

	public function tearDown(): void {
		$this->_metabolic->_reset();

		parent::tearDown();
	}

	public function mock_current_filter( string $filter ): void {
		global $wp_current_filter;
		$wp_current_filter = [ $filter ];
	}

	public function __get( string $name ): mixed {
		if ( $name !== 'metabolic' ) {
			return parent->$name;
		}

		if ( is_null( $this->_metabolic ) ) {
			$this->_metabolic = metabolic\Metabolic::getInstance();
		}

		return $this->_metabolic;
	}
}
