<?php

class Test_SQL_Class extends MB_UnitTestCase {
	public function test_adds(): void {
		$builder = new metabolic\SQLBuilder();

		$builder->flush();
	}
}
