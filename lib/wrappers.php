<?php declare( strict_types=1 );

namespace metabolic;

if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	// Collect useful debugging information.
	Metabolic::getInstance()->debug( true );
}

function metabolize( bool $activate = true ) {
	$metabolic = Metabolic::getInstance();

	try {
		return $metabolic->metabolize( $activate );
	} catch ( \Exception $e ) {
		// TODO: Log an error
		return false;
	}
}

function defer_meta_updates( array $args ): bool {
	$metabolic = Metabolic::getInstance();

	try {
		return $metabolic->defer( $args );
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
