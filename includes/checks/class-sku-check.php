<?php
/**
 * Missing SKU detection module.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects variations without an SKU (treating whitespace-only values as empty).
 */
final class Didban_SKU_Check {

	/**
	 * Internal issue identifier.
	 */
	public const ISSUE_TYPE = 'missing_sku';

	/**
	 * Inspect a variation for missing SKU.
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

		$sku = trim( (string) $data['sku'] );

		if ( '' !== $sku ) {
			return null;
		}

		return array(
			'product_id'   => $data['parent_id'],
			'variation_id' => $data['variation_id'],
			'issue_type'   => self::ISSUE_TYPE,
			'message'      => 'Variation has no SKU.',
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
				'variation_id' => $variation->get_id(),
				'parent_id'    => $variation->get_parent_id(),
				'status'       => $variation->get_status(),
				'sku'          => $variation->get_sku( 'edit' ),
			);
		}

		return array(
			'variation_id' => (int) ( $variation['variation_id'] ?? 0 ),
			'parent_id'    => (int) ( $variation['parent_id'] ?? 0 ),
			'status'       => (string) ( $variation['status'] ?? 'publish' ),
			'sku'          => (string) ( $variation['sku'] ?? '' ),
		);
	}
}