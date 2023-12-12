<?php declare( strict_types=1 );

namespace metabolic;

/**
 * The main Metablic singleton.
 *
 * Is responsible to keep state, queue up meta changes,
 * and commit them.
 */
final class Metabolic {
	private static ?Metabolic $instance = null;

	private function __construct() {
		add_action( 'shutdown', [ $this, '_shutdown' ] );
	}

	/**
	 * The internal way to get a Metabolic instance.
	 *
	 * We only allow one class to handle metabolic optimizations.
	 * Having more than 
	 */
	public static function getInstance(): Metabolic {
		return self::$instance ??= new self();
	}

	/**
	 * Start queuing up meta calls.
	 */
	public function queue( array $args ): bool {
	}

	/**
	 * Commit all queued meta calls.
	 */
	public function commit() {
	}

	/**
	 * Discard all queued meta calls.
	 */
	public function flush() {
	}

	/**
	 * Inspect queue internals.
	 */
	public function inspect(): array {
	}

	/**
	 * Runs on shutdown hook.
	 */
	private function _shutdown() {
	}
}
