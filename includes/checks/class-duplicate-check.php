<?php
/**
 * Duplicate attribute combinations detection module.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects variations within the same parent product that share identical attribute combinations.
 */
final class Didban_Duplicate_Check {

	/**
	 * Internal issue identifier.
	 */
	public const ISSUE_TYPE = 'duplicate_combination';

	/**
	 * Inspect a single parent product's variations for duplicate attribute combinations.
	 *
	 * @param int                                                   $product_id Parent variable product ID.
	 * @param array<int, WC_Product_Variation|array<string, mixed>> $variations Variations belonging to this product.
	 * @return array<int, array<string, mixed>> List of standardized issue arrays.
	 */
	public function check( int $product_id, array $variations ): array {
		$groups = array();

		foreach ( $variations as $variation ) {
			$data = $this->normalize( $variation );

			// Disabled variations are not treated as duplicate.
			if ( 'publish' !== $data['status'] ) {
				continue;
			}

			if ( empty( $data['attributes'] ) ) {
				continue;
			}

			$signature = $this->build_signature( $data['attributes'] );
			$groups[ $signature ][] = $data['variation_id'];
		}

		$issues      = array();
		$detected_at = current_time( 'mysql', true );

		foreach ( $groups as $variation_ids ) {
			if ( count( $variation_ids ) < 2 ) {
				continue;
			}

			$id_list = implode( ', ', $variation_ids );

			foreach ( $variation_ids as $variation_id ) {
				$issues[] = array(
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
					'issue_type'   => self::ISSUE_TYPE,
					'message'      => sprintf( 'Duplicate attribute combination (involved variation IDs: %s).', $id_list ),
					'detected_at'  => $detected_at,
				);
			}
		}

		return $issues;
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
				'status'       => $variation->get_status(),
				'attributes'   => $variation->get_attributes(),
			);
		}

		return array(
			'variation_id' => (int) ( $variation['variation_id'] ?? 0 ),
			'status'       => (string) ( $variation['status'] ?? 'publish' ),
			'attributes'   => is_array( $variation['attributes'] ?? null ) ? $variation['attributes'] : array(),
		);
	}

	/**
	 * Build an order-independent, normalized signature for an attribute map.
	 *
	 * @param array<string, mixed> $attributes Variation attributes.
	 */
	private function build_signature( array $attributes ): string {
		$normalized = array();

		foreach ( $attributes as $name => $value ) {
			$clean_name = strtolower( trim( urldecode( (string) $name ) ) );
			if ( str_starts_with( $clean_name, 'attribute_' ) ) {
				$clean_name = substr( $clean_name, 10 );
			}

			$clean_value               = strtolower( trim( urldecode( (string) $value ) ) );
			$normalized[ $clean_name ] = $clean_value;
		}

		ksort( $normalized );

		return (string) wp_json_encode( $normalized );
	}
}