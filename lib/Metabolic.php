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
	 * Whether autocommitting on error should be done.
	 *
	 * Otherwise an exception will be thrown.
	 *
	 * @var bool
	 */
	private bool $autocommit = true;

	/**
	 * The meta types that are to be deferred.
	 *
	 * @var array
	 */
	private array $types = [];

	/**
	 * The main metabolic queue.
	 *
	 * @var array
	 */
	private array $queue = [];

	/**
	 * The metabolic SQL builder.
	 *
	 * @var metabolic\SQLBuilder
	 */
	private ?SQLBuilder $builder = null;

	/**
	 * The metabolic tracer (debug, metrics).
	 *
	 * @var metabolic\SQLBuilder
	 */
	private ?Tracer $tracer = null;

	/**
	 * Debugging in progress.
	 */
	private bool $debug = false;

	/**
	 * Whether proper shutdown procedures have been performed or not.
	 *
	 * @var bool
	 */
	private bool $shutdown_completed = false;

	private function __construct( SQLBuilder $builder, Tracer $tracer ) {
		add_action( 'shutdown', [ $this, '_shutdown' ], PHP_INT_MAX );

		if ( ! defined( 'DOING_METABOLIC_TESTS' ) ) {
			// This is the catch-all that should never happen in theory.
			register_shutdown_function( [ $this, '_shutdown_fallback' ] );
		}

		$this->builder = $builder;
		$this->tracer = $tracer;

		if ( $this->debug ) {
			$this->tracer->trace( '__construct' );
		}
	}

	/**
	 * The internal way to get a Metabolic instance.
	 *
	 * We only allow one class to handle metabolic optimizations.
	 * Having more than one will yield duplicate queries, conflicts,
	 * and futher destructive behavior.
	 */
	public static function getInstance( SQLBuilder $builder = null, Tracer $tracer = null ): Metabolic {
		if ( is_null( $builder ) ) {
			$builder = new SQLBuilder();
		}

		if ( is_null( $tracer ) ) {
			$tracer = new Tracer();
		}

		return self::$instance ??= new self( $builder, $tracer );
	}

	/**
	 * Start queuing up meta calls.
	 *
	 * @param string|array $type The type of meta deferral to set. Default 'all'
	 *                           Can be a mixture of 'post', 'user', 'comment', 'term' or 'all'.
	 * @param bool $autocommit   Whether to throw an exception on attempted intermittent
	 *                           reads with rollback, or to autocommit before a read. A
	 *                           warning will be generated noting that metabolism has stopped.
	 */
	public function defer( string|array $type = 'all', bool $autocommit = false ): bool {
		if ( $this->deferring ) {
			if ( $this->debug ) {
				foreach ( $this->tracer->getTraces() as $previous_trace ) {
					if ( ! $previous_trace['committed'] ) {
						$trace = 'Uncommitted deferral in progress in ' . $previous_trace['defer_backtrace'];
					}
				}
			} else {
				$trace = 'Turn on debugging with Metabolic::debug() for debugging information.';
			}

			throw new \Exception( "Metabolism already in progress. $trace" );
		}

		if ( ! is_array( $type ) ) {
			$types = [ $type ];
		}

		foreach ( $types as $type ) {
			if ( ! in_array( $type, [ 'all', 'post', 'comment', 'term', 'user' ], true ) ) {
				throw new \Exception( "Unknown deferral type: $type" );
			}
		}

		if ( in_array( 'all', $types ) ) {
			$types = [ 'post', 'comment', 'term', 'user' ];
		}

		if ( $this->debug ) {
			$this->tracer->trace( 'defer', [
				'types' => $types,
				'autocommit' => $autocommit,
				'committed' => false,
			] );
		}

		$this->_add_filters();

		$this->autocommit = $autocommit;

		$this->types = $types;

		return $this->deferring = true;
	}

	/**
	 * Commit all queued meta calls.
	 */
	public function commit(): bool {
		if ( ! $this->deferring ) {
			throw new \Exception( 'Metabolic::commit not deferring.' );
		}
	}

	/**
	 * Discard all queued meta calls, reset queue state.
	 */
	public function flush(): void {
		$this->builder->flush();
		$this->queue = [];
	}

	/**
	 * Add all the necessary short-circuit actions for
	 * updates, adds and deletes and gets.
	 *
	 * @internal
	 */
	private function _add_filters(): void {
		add_filter( 'update_post_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		add_filter( 'update_term_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		add_filter( 'update_user_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		add_filter( 'update_comment_metadata', [ $this, '_queue' ], PHP_INT_MAX );

		add_filter( 'add_post_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		add_filter( 'add_term_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		add_filter( 'add_user_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		add_filter( 'add_comment_metadata', [ $this, '_queue' ], PHP_INT_MAX );

		add_filter( 'delete_post_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		add_filter( 'delete_term_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		add_filter( 'delete_user_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		add_filter( 'delete_comment_metadata', [ $this, '_queue' ], PHP_INT_MAX );

		add_filter( 'get_post_metadata', [ $this, '_interrupt' ], PHP_INT_MAX );
		add_filter( 'get_term_metadata', [ $this, '_interrupt' ], PHP_INT_MAX );
		add_filter( 'get_user_metadata', [ $this, '_interrupt' ], PHP_INT_MAX );
		add_filter( 'get_comment_metadata', [ $this, '_interrupt' ], PHP_INT_MAX );

		if ( $this->debug ) {
			$this->tracer->trace( '_add_filters' );
		}
	}

	private function _remove_filters(): void {
		remove_filter( 'update_post_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		remove_filter( 'update_term_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		remove_filter( 'update_user_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		remove_filter( 'update_comment_metadata', [ $this, '_queue' ], PHP_INT_MAX );

		remove_filter( 'add_post_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		remove_filter( 'add_term_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		remove_filter( 'add_user_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		remove_filter( 'add_comment_metadata', [ $this, '_queue' ], PHP_INT_MAX );

		remove_filter( 'delete_post_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		remove_filter( 'delete_term_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		remove_filter( 'delete_user_metadata', [ $this, '_queue' ], PHP_INT_MAX );
		remove_filter( 'delete_comment_metadata', [ $this, '_queue' ], PHP_INT_MAX );

		remove_filter( 'get_post_metadata', [ $this, '_interrupt' ], PHP_INT_MAX );
		remove_filter( 'get_term_metadata', [ $this, '_interrupt' ], PHP_INT_MAX );
		remove_filter( 'get_user_metadata', [ $this, '_interrupt' ], PHP_INT_MAX );
		remove_filter( 'get_comment_metadata', [ $this, '_interrupt' ], PHP_INT_MAX );
	}

	/**
	 * @internal
	 */
	public function _reset(): void {
		if ( ! defined( 'DOING_METABOLIC_TESTS' ) ) {
			throw new \Exception( 'metabolic\Metabolic::_reset is only available during tests. Sorry.' );
		}

		$this->_remove_filters();
		$this->flush();
		$this->deferring = false;
		$this->tracer->reset();
		$this->shutdown_completed = false;
	}

	/**
	 * Queue up metadata changes from WordPress hooks.
	 *
	 * @internal
	 */
	public function _queue(): mixed {
		if ( ! $current_filter = current_filter() ) {
			// TODO: $autocommit and return false?
			throw new \Exception( 'Metabolic::_queue called with no filter.' );
		}

		if ( ! preg_match( '#^(add|update|delete)_(post|comment|term|user)_metadata$#', $current_filter, $matches ) ) {
			throw new \Exception( "MetabolicMetabolic::_queue called with invalid filter: $current_filter" );
		}

		if ( ! $this->deferring ) {
			throw new \Exception( 'Metabolic::_queue not deferring.' );
		}

		list( $_, $action, $type ) = $matches;

		if ( empty( $args = func_get_args() ) ) {
			throw new \Exception( "Metabolic::_queue called with empty arguments for $current_filter" );
		}

		if ( count( $args ) !== 5 ) {
			throw new \Exception( "Metabolic::_queue called with unexpected number of arguments for $current_filter" );
		}

		// add: $check, $object_id, $meta_key, $meta_value, $unique
		// update: $check, $object_id, $meta_key, $meta_value, $prev_value
		// delete: $check, $object_id, $meta_key, $meta_value, $delete_all
		list( $check, $object_id, $meta_key, $meta_value, $fifth_arg ) = $args;

		if ( $check !== null ) {
			throw new \Exception( "Metabolic::_queue called with failing short-circuit check for $current_filter" );
		}

		$fifth_arg_key = [
			'add' => 'unique',
			'update' => 'prev_value',
			'delete' => 'delete_all',
		][ $action ];

		if ( $this->debug ) {
			$this->tracer->trace( '_queue', [
				'action' => $action,
				'type' => $type,
				'key' => $meta_key,
			] );
		}

		$this->queue []= [
			'action' => $action,
			'type' => $type,
			'object_id' => $object_id,
			'meta_key' => $meta_key,
			'meta_value' => $meta_value,
			$fifth_arg_key => $fifth_arg,
		];

		return true;
	}

	/**
	 * get_$type_metadata hook has been called.
	 *
	 * @internal
	 */
	public function _interrupt(): mixed {
		if ( ! $current_filter = current_filter() ) {
			throw new \Exception( 'Metabolic::_interrupt called with no filter.' );
		}

		if ( ! preg_match( '#^get_(post|comment|term|user)_metadata$#', $current_filter, $matches ) ) {
			throw new \Exception( "MetabolicMetabolic::_interrupt called with invalid filter: $current_filter" );
		}

		if ( ! $this->deferring ) {
			throw new \Exception( 'Metabolic::_interrupt not deferring.' );
		}

		list( $_, $type ) = $matches;

		if ( empty( $args = func_get_args() ) ) {
			throw new \Exception( "Metabolic::_interrupt called with empty arguments for $current_filter" );
		}

		if ( count( $args ) !== 5 ) {
			throw new \Exception( "Metabolic::_interrupt called with unexpected number of arguments for $current_filter" );
		}

		// get: $check, $object_id, $meta_key, $single, $meta_type
		list( $check, $object_id, $meta_key, $single, $meta_type ) = $args;

		if ( $meta_type !== $type ) {
			throw new \Exception( "Metabolic::_interrupt called with mismatched \$meta_type ($meta_type != $type)" );
		}

		if ( $check !== null ) {
			throw new \Exception( "Metabolic::_interrupt called with failing short-circuit check for $current_filter" );
		}

		// TODO: implement
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
		if ( ! $this->debug ) {
			throw new \Exception( 'Metabolic inspection is only possible in debug mode.' );
		}

		return [
			'queue' => $this->queue,
		];
	}

	/**
	 * Enable debugging and metrics.
	 */
	public function debug( bool $debug = true ): void {
		$this->debug = $debug;
	}

	public function __serialize(): array {
		throw new \Exception( 'Metabolic cannot be serialized. For you own sake.' );
	}

	public function __debugInfo(): array {
		return [
			'__' => 'Struggling with an issue in Metabolic? Check out https://github.com/soulseekah/metabolic/issues and hit that star button',
		];
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
