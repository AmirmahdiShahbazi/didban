<?php
/**
 * Missing variation image detection module.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects variations without their own explicitly assigned image.
 */
final class Didban_Image_Check {

	/**
	 * Internal issue identifier.
	 */
	public const ISSUE_TYPE = 'missing_image';

	/**
	 * Inspect a variation for a missing variation-specific image.
	 *
	 * @param WC_Product_Variation|array<string, mixed>     $variation        Variation object or normalized array.
	 * @param WC_Product_Variable|array<string, mixed>|null $parent_product   Optional parent product to detect fallback image.
	 * @param bool                                          $include_disabled Whether to check non-published variations.
	 * @return array<string, mixed>|null Standardized issue array or null if valid.
	 */
	public function check(
		WC_Product_Variation|array $variation,
		WC_Product_Variable|array|null $parent_product = null,
		bool $include_disabled = false
	): ?array {
		$data = $this->normalize_variation( $variation );

		if ( ! $include_disabled && 'publish' !== $data['status'] ) {
			return null;
		}

		if ( $data['image_id'] > 0 ) {
			return null;
		}

		$parent_image_id = $this->resolve_parent_image_id( $parent_product );

		$message = $parent_image_id > 0
			? 'Variation has no assigned image (falls back to parent product image).'
			: 'Variation has no assigned image and parent product also has no image.';

		return array(
			'product_id'   => $data['parent_id'],
			'variation_id' => $data['variation_id'],
			'issue_type'   => self::ISSUE_TYPE,
			'message'      => $message,
			'detected_at'  => current_time( 'mysql', true ),
		);
	}

	/**
	 * Normalize variation input using 'edit' context to avoid WooCommerce parent fallback.
	 *
	 * @param WC_Product_Variation|array<string, mixed> $variation Variation input.
	 * @return array<string, mixed>
	 */
	private function normalize_variation( WC_Product_Variation|array $variation ): array {
		if ( $variation instanceof WC_Product_Variation ) {
			return array(
				'variation_id' => $variation->get_id(),
				'parent_id'    => $variation->get_parent_id(),
				'status'       => $variation->get_status(),
				'image_id'     => (int) $variation->get_image_id( 'edit' ),
			);
		}

		return array(
			'variation_id' => (int) ( $variation['variation_id'] ?? 0 ),
			'parent_id'    => (int) ( $variation['parent_id'] ?? 0 ),
			'status'       => (string) ( $variation['status'] ?? 'publish' ),
			'image_id'     => (int) ( $variation['image_id'] ?? 0 ),
		);
	}

	/**
	 * Resolve parent product's image ID if provided.
	 *
	 * @param WC_Product_Variable|array<string, mixed>|null $parent_product Parent product.
	 */
	private function resolve_parent_image_id( WC_Product_Variable|array|null $parent_product ): int {
		if ( $parent_product instanceof WC_Product_Variable ) {
			return (int) $parent_product->get_image_id( 'edit' );
		}

		if ( is_array( $parent_product ) ) {
			return (int) ( $parent_product['image_id'] ?? 0 );
		}

		return 0;
	}
}