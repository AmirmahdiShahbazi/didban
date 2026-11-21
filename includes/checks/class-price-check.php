<?php
/**
 * Missing price detection module.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects variations without a configured regular or sale price.
 */
final class Didban_Price_Check {

	/**
	 * Internal issue identifier.
	 */
	public const ISSUE_TYPE = 'missing_price';

	/**
	 * Inspect a variation for missing price.
	 *
	 * @param WC_Product_Variation|array<string, mixed> $variation        Variation object or normalized array.
	 * @param bool                                      $include_disabled Whether to check non-published variations.
	 * @return array<string, mixed>|null Standardized issue array or null if valid.
	 */
	public function check( WC_Product_Variation|array $variation, bool $include_disabled = false ): ?array {
		$data = $this->normalize( $variation );

		if ( ! $include_disabled && 'publish' !== $data['status'] ) {
			return null;
		}

		$regular_price = trim( (string) $data['regular_price'] );
		$sale_price    = trim( (string) $data['sale_price'] );
		$active_price  = trim( (string) $data['price'] );

		// A zero price ('0', '0.00') is valid; only empty strings are missing.
		if ( '' !== $regular_price || '' !== $sale_price || '' !== $active_price ) {
			return null;
		}

		return array(
			'product_id'   => $data['parent_id'],
			'variation_id' => $data['variation_id'],
			'issue_type'   => self::ISSUE_TYPE,
			'message'      => 'Variation has no regular or sale price configured.',
			'detected_at'  => current_time( 'mysql', true ),
		);
	}

	/**
	 * Normalize variation input.
	 *
	 * @param WC_Product_Variation|array<string, mixed> $variation Variation input.
	 * @return array<string, mixed>
	 */
	private function normalize( WC_Product_Variation|array $variation ): array {
		if ( $variation instanceof WC_Product_Variation ) {
			return array(
				'variation_id'  => $variation->get_id(),
				'parent_id'     => $variation->get_parent_id(),
				'status'        => $variation->get_status(),
				'regular_price' => $variation->get_regular_price( 'edit' ),
				'sale_price'    => $variation->get_sale_price( 'edit' ),
				'price'         => $variation->get_price( 'edit' ),
			);
		}

		return array(
			'variation_id'  => (int) ( $variation['variation_id'] ?? 0 ),
			'parent_id'     => (int) ( $variation['parent_id'] ?? 0 ),
			'status'        => (string) ( $variation['status'] ?? 'publish' ),
			'regular_price' => $variation['regular_price'] ?? '',
			'sale_price'    => $variation['sale_price'] ?? '',
			'price'         => $variation['price'] ?? '',
		);
	}
}