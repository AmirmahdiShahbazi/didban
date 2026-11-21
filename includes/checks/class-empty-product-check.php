<?php
/**
 * Products without active variations detection module.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects variable products that have zero variations or zero published/active variations.
 */
final class Didban_Empty_Product_Check {

	/**
	 * Internal issue identifier.
	 */
	public const ISSUE_TYPE = 'without_active_variations';

	/**
	 * Inspect a variable product for missing or inactive variations.
	 *
	 * @param WC_Product_Variable|array<string, mixed> $product               Variable product or normalized array.
	 * @param bool                                     $include_non_published Whether to report draft/private products.
	 * @return array<string, mixed>|null Standardized issue array or null if valid.
	 */
	public function check( WC_Product_Variable|array $product, bool $include_non_published = false ): ?array {
		$data = $this->normalize( $product );

		if ( ! $include_non_published && 'publish' !== $data['status'] ) {
			return null;
		}

		$total_count  = $data['total_variations'];
		$active_count = $data['active_variations'];

		if ( $active_count > 0 ) {
			return null;
		}

		$message = 0 === $total_count
			? 'Variable product has 0 variations.'
			: sprintf( 'Variable product has 0 active variations (%d total disabled/unavailable).', $total_count );

		return array(
			'product_id'   => $data['product_id'],
			'variation_id' => 0,
			'issue_type'   => self::ISSUE_TYPE,
			'message'      => $message,
			'detected_at'  => current_time( 'mysql', true ),
		);
	}

	/**
	 * Normalize variable product input and count total vs. active variations.
	 *
	 * @param WC_Product_Variable|array<string, mixed> $product Product input.
	 * @return array<string, mixed>
	 */
	private function normalize( WC_Product_Variable|array $product ): array {
		if ( $product instanceof WC_Product_Variable ) {
			$child_ids    = $product->get_children();
			$active_count = 0;

			foreach ( $child_ids as $child_id ) {
				$variation = wc_get_product( (int) $child_id );
				if ( $variation instanceof WC_Product_Variation && 'publish' === $variation->get_status() ) {
					++$active_count;
				}
				unset( $variation );
			}

			return array(
				'product_id'        => $product->get_id(),
				'status'            => $product->get_status(),
				'total_variations'  => count( $child_ids ),
				'active_variations' => $active_count,
			);
		}

		$variations   = is_array( $product['variations'] ?? null ) ? $product['variations'] : array();
		$active_count = 0;

		foreach ( $variations as $variation ) {
			if ( ( $variation['status'] ?? 'publish' ) === 'publish' ) {
				++$active_count;
			}
		}

		return array(
			'product_id'        => (int) ( $product['product_id'] ?? 0 ),
			'status'            => (string) ( $product['status'] ?? 'publish' ),
			'total_variations'  => count( $variations ),
			'active_variations' => $active_count,
		);
	}
}