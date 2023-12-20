<?php

class Test_Tracer_Class extends MB_UnitTestCase {
	public function test_trace_and_get_and_reset_simple(): void {
		$tracer = new metabolic\Tracer();

		$this->assertEmpty( $trace = $tracer->getTraces() );

		$tracer->trace( 'test', [ 'hello' => 'world' ] );

		$trace = $tracer->getTraces();

		$this->assertCount( 1, $trace, 'Not traces present' );

		$this->assertEquals( [ 'hello' => 'world' ], $trace[0]['data'], 'Data not present in trace' );
		$this->assertEquals( 'test', $trace[0]['action'], 'Action not present in trace' );
		$this->assertNotEmpty( $trace[0]['time'], 'Time not present in trace' );
		$this->assertNotEmpty( $trace[0]['backtrace'], 'Backtrace not present in trace' );

		$tracer->reset();

		$this->assertEmpty( $trace = $tracer->getTraces() );
	}
}
