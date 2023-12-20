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
	private array $operations = [
		'insert' => [],
		'upsert' => [],
		'update' => [],
		'delete' => [],
	];

	/**
	 * Remove all queued queries on this builder.
	 */
	public function flush(): bool {
		$this->operations = [
			'insert' => [],
			'upsert' => [],
			'update' => [],
			'delete' => [],
		];
		return true;
	}

	/**
	 * Push an operation.
	 */
	public function push( string $operation, string $table, array $args ): bool {
		if ( ! in_array( $operation, [ 'insert', 'upsert', 'update', 'delete' ] ) ) {
			throw new \Exception( "SQLBuilder::add invalid operation $operation" );
		}

		$this->operations[ $operation ][ $table ] ?? $this->operations[ $operation ][ $table ] = [];
		$this->operations[ $operation ][ $table ][] = $args;

		return true;
	}

	public function get_table( string $type ): string {
		global $wpdb;

		// TODO: add exception
		// if ( ! in_array( $type, [ 'post', 'term', 'comment', 'user' ], true ) ) {
		// }

		$type .= 'meta';
		return $wpdb->$type;
	}

	public function get_object_id_column( string $type ): string {
		global $wpdb;

		// TODO: add exception
		// if ( ! in_array( $type, [ 'post', 'term', 'comment', 'user' ], true ) ) {
		// }

		return $type . '_id';
	}

	/**
	 * Optimize and execute all the queries in a transaction.
	 *
	 * @param metabolic\Tracer $tracer The tracer for debugging and metrics.
	 */
	public function execute( Tracer $tracer = null ): void {
		global $wpdb;

		/**
		 * For now we'll separate by operation and table.
		 */
		foreach ( $this->operations as $operation => $tables ) {
			switch ( $operation ) {
				case 'insert':
					foreach ( $tables as $table => $args ) {
						$columns = array_keys( $args[0] ); // Take the first columns
						$placeholders = implode( ', ', array_fill( 0, count( $columns ), '%s' ) );
						$values = [];

						foreach ( $args as $data ) {
							$values[] = $wpdb->prepare( "($placeholders)", ...array_values( $data ) );
						}

						$columns = implode( ', ', $columns );
						$values = implode( ', ', $values );

						// Columns do not need escaping, values are prepared.
						$query = "INSERT INTO $table ($columns) VALUES $values";

						$wpdb->query( $query );
					}
					break;
			}
		}
	}
}
