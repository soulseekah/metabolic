<?php declare( strict_types=1 );

namespace metabolic;

/**
 * A metabolic tracer.
 *
 * This class can be extended and injected into Metabolic
 * by passing it into Metabolic::getInstance() when using it
 * for the first time.
 */
class Tracer {
	private array $traces = [];

	private function trace( string $action, array $data ) {
		$traces[] = [
			'time' => microtime( true ),
			'backtrace' => wp_get_debug_backtrace(),
			'action' => $action,
			'data' => $data,
		];
	}
}
