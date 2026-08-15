<?php
/**
 * Plugin Name:       Sapphire Mobile Lock
 * Description:       Locks the mobile/tablet layout of the Sapphire Capitals Elementor landing pages. Loads after Elementor's per-page CSS so it cannot be undone by Elementor regenerating its files, by an Astra update, or by an Elementor update.
 * Version:           1.0.0
 * Author:            Anirudha Talmale
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAPPHIRE_MOBILE_LOCK_VERSION', '1.0.0' );

/**
 * The five landing pages this plugin was built for.
 *
 * Add a page ID here to bring a new page under the same protection.
 * Leave the array empty to apply the stylesheet site-wide.
 */
function sapphire_mobile_lock_page_ids() {
	return apply_filters(
		'sapphire_mobile_lock_page_ids',
		array(
			5037, // swing-trading-stock-seasonality-strategy
			5276, // swing-trading-stock-price-action-strategy
			5453, // swing-trading-volume-spike-stock-trading-strategy
			5456,  // day-trading-intraday-seasonality-trading
			10840, // sapphire-capitals-analytic-suite
		)
	);
}

/**
 * Should the stylesheet load on the page currently being rendered?
 */
function sapphire_mobile_lock_should_enqueue() {
	$ids = sapphire_mobile_lock_page_ids();

	if ( empty( $ids ) ) {
		return true;
	}

	return is_singular() && in_array( (int) get_the_ID(), array_map( 'intval', $ids ), true );
}

/**
 * Enqueue at priority 999 so the stylesheet is printed AFTER
 * elementor-post-{id}.css. Same specificity would otherwise lose the cascade.
 */
function sapphire_mobile_lock_enqueue() {
	if ( ! sapphire_mobile_lock_should_enqueue() ) {
		return;
	}

	wp_enqueue_style(
		'sapphire-mobile-lock',
		plugins_url( 'assets/sapphire-mobile-lock.css', __FILE__ ),
		array(),
		SAPPHIRE_MOBILE_LOCK_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'sapphire_mobile_lock_enqueue', 999 );
