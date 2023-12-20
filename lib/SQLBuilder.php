<?php declare( strict_types=1 );

namespace metabolic;

/**
 * A SQL builder for massive operations on metatables.
 *
 * This class can be extended and injected into Metabolic
 * by passing it into Metabolic::getInstance() when using it
 * for the first time.
 */
class SQLBuilder {
	private array $queries = [];

	/**
	 * Remove all queued queries on this builder.
	 */
	public function flush(): bool {
		$this->queries = [];
		return true;
	}

	public function add( string $operation ): bool {
	}

	/**
	 * Optimize and execute all the queries in a transaction.
	 *
	 * @param metabolic\Tracer $tracer The tracer for debugging and metrics.
	 */
	public function execute( Tracer $tracer = null ): bool {
	}
}
