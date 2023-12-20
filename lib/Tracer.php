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

	public function trace( string $action, array $data = [] ): void {
		$this->traces[] = [
			'time' => microtime( true ),
			'backtrace' => \wp_debug_backtrace_summary(),
			'action' => $action,
			'data' => $data,
		];
	}

	/**
	 * Retreive trace.
	 */
	public function getTraces(): array {
		return $this->traces;
	}

	/**
	 * Reset trace.
	 */
	public function reset(): void {
		$this->traces = [];
	}
}
