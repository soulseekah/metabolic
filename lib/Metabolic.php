<?php declare( strict_types=1 );

namespace metabolic;

/**
 * The main Metablic singleton.
 *
 * Is responsible to keep state, queue up meta changes,
 * and commit them.
 */
final class Metabolic {
	/**
	 * The singleton instance.
	 *
	 * @var metabolic\Metabolic
	 */
	private static ?Metabolic $instance = null;

	/**
	 * Whether deferring is in progress or not.
	 *
	 * @var bool
	 */
	private bool $deferring = false;

	/**
	 * Debugging in progress.
	 */
	private bool $debug = false;

	/**
	 * Debug tracing.
	 *
	 * @see metabolic\Metabolic::inspect()
	 * @see metabolic\Metabolic::debug()
	 *
	 * @var array
	 */
	private array $traces = [];

	/**
	 * Whether proper shutdown procedures have been performed or not.
	 *
	 * @var bool
	 */
	private bool $shutdown_completed = false;

	private function __construct() {
		add_action( 'shutdown', [ $this, '_shutdown' ] );

		if ( ! defined( 'DOING_METABOLIC_TESTS' ) ) {
			// This is the catch-all that should never happen in theory.
			register_shutdown_function( [ $this, '_shutdown_fallback' ] );
		}
	}

	/**
	 * The internal way to get a Metabolic instance.
	 *
	 * We only allow one class to handle metabolic optimizations.
	 * Having more than one will yield duplicate queries, conflicts,
	 * and futher destructive behavior.
	 */
	public static function getInstance(): Metabolic {
		return self::$instance ??= new self();
	}

	/**
	 * Start queuing up meta calls.
	 *
	 * @param string|array $type The type of meta/option deferral to set. Default 'all'
	 *                           Can be a mixture of 'post', 'user', 'comment',
	 *                           'taxonomy', 'term', 'option' or 'all'.
	 * @param bool $autocommit   Whether to throw an exception on attempted intermittent
	 *                           reads with rollback, or to autocommit before a read. A
	 *                           warning will be generated noting that metabolism has stopped.
	 */
	public function defer( string|array $type = 'all', bool $autocommit = false ): bool {
		if ( $this->deferring ) {
			if ( $this->debug ) {
				foreach ( $this->inspect()['traces'] as $previous_trace ) {
					if ( ! $previous_trace['committed'] ) {
						$trace = 'Uncommitted deferral in progress in ' . $previous_trace['defer_backtrace'];
					}
				}
			} else {
				$trace = 'Turn on debugging with Metabolic::debug() for debugging information.';
			}

			throw new \Exception( "Metabolism already in progress. $trace" );
		}

		if ( $this->debug ) {
			$this->traces[] = [
				'backtrace' => wp_debug_backtrace_summary(),
				'committed' => false,
			];
		}

		return $this->deferring = true;
	}

	/**
	 * Commit all queued meta calls.
	 */
	public function commit(): bool {
	}

	/**
	 * Discard all queued meta calls, reset state.
	 */
	public function flush(): void {
	}

	/**
	 * @internal
	 */
	public function _reset(): void {
		if ( ! defined( 'DOING_METABOLIC_TESTS' ) ) {
			throw new \Exception( 'metabolic\Metabolic::_reset is only available during tests.' );
		}

		$this->traces = [];
		$this->shutdown_completed = false;
	}

	/**
	 * Runs on shutdown WordPress hook.
	 *
	 * @internal
	 */
	public function _shutdown(): void {
		$this->shutdown_completed = true;
	}

	/**
	 * Runs on register_shutdown_function in case the
	 * WordPress action didn't run for some reason.
	 */
	public function _shutdown_fallback(): void {
		if ( $this->shutdown_completed ) {
			return;
		}

		$trace = [
			"WordPress shutdown hook not called.",
		];

		throw new \Exception( implode( $trace ) );
	}

	/**
	 * Inspect queue internals.
	 */
	public function inspect(): array {
	}

	/**
	 * Enable debugging and metrics.
	 */
	public function debug( bool $debug = true ): void {
	}

	public function __serialize(): array {
	}

	public function __debugInfo(): array {
	}

	/**
	 * Single-tonne.
	 */
	private function __clone(): void {
		throw new \Exception( 'Metabolic cannot be cloned. For you own sake.' );
	}

	public function __wakeup(): void {
		throw new \Exception( 'Metabolic is always awake, because sleep is the cousin of death.' );
	}

	public function __unserialize( array $data ): void {
		throw new \Exception( 'Metabolic cannot be serialized/unserialized.' );
	}
}
