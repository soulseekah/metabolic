<?php declare( strict_types=1 );

namespace metabolic;

function metabolic( bool $activate = true ) {
	$metabolic = Metabolic::getInstance();

	try {
		return $metabolic->metabolic( $activate );
	} catch ( \Exception $e ) {
		// TODO: Log an error
		return false;
	}
}

function queue_meta_updates( array $args ): bool {
	$metabolic = Metabolic::getInstance();

	try {
		return $metabolic->queue( $args );
	} catch ( \Exception $e ) {
		// TODO: Log an error
		return false;
	}
}

function commit_meta_updates(): bool {
	$metabolic = Metabolic::getInstance();

	try {
		return $metabolic->commmit();
	} catch ( \Exception $e ) {
		// TODO: Log an error
		return false;
	}
}

function flush_meta_updates(): bool {
	$metabolic = Metabolic::getInstance();

	try {
		return $metabolic->flush();
	} catch ( \Exception $e ) {
		// TODO: Log an error
		return false;
	}
}
