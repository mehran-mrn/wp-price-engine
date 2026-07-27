<?php
/**
 * Pure, side-effect-free parsers for public Persian rate pages.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class HTML_Rate_Parser {
	/**
	 * Parse all rate rows embedded in the TGJU home page.
	 *
	 * @param string $html Source HTML.
	 * @return array<string, array{value: float, name: string}>
	 */
	public static function tgju( string $html ): array {
		$parsed = self::document( $html );
		if ( ! $parsed ) {
			return array();
		}

		list( , $xpath ) = $parsed;
		$nodes            = $xpath->query( '//*[@data-market-row and @data-price]' );
		$rates            = array();

		if ( ! $nodes ) {
			return $rates;
		}

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof \DOMElement ) {
				continue;
			}

			$key   = strtolower( trim( $node->getAttribute( 'data-market-row' ) ) );
			$value = self::number( $node->getAttribute( 'data-price' ) );
			if ( '' === $key || null === $value || $value <= 0 ) {
				continue;
			}

			$title_node = $xpath->query(
				'.//*[self::th or self::h2 or self::h3 or contains(concat(" ", normalize-space(@class), " "), " summary-widget-title ")][1]',
				$node
			);
			$name       = $title_node && $title_node->length ? self::clean_text( $title_node->item( 0 )->textContent ) : $key;

			if ( ! isset( $rates[ $key ] ) || $name !== $key ) {
				$rates[ $key ] = array(
					'value' => $value,
					'name'  => $name ?: $key,
				);
			}
		}

		return $rates;
	}

	/**
	 * Parse the primary "last price" card on a MelliGold detail page.
	 *
	 * @param string $html Source HTML.
	 * @return float|null
	 */
	public static function melligold( string $html ): ?float {
		$text = self::visible_text( $html );
		if ( ! $text ) {
			return null;
		}

		if ( preg_match( '/آخرین\s*قیمت\s*([0-9۰-۹٠-٩][0-9۰-۹٠-٩٬،,.\s]*)\s*(?:تومان|ریال)/u', $text, $match ) ) {
			return self::number( $match[1] );
		}

		if ( preg_match( '/به\s*رقم\s*([0-9۰-۹٠-٩][0-9۰-۹٠-٩٬،,.\s]*)\s*(?:تومان|ریال)\s*رسید/u', $text, $match ) ) {
			return self::number( $match[1] );
		}

		return null;
	}

	/**
	 * Parse the one-gram 18K Rial value from Milli's public widget.
	 *
	 * @param string $html Source HTML.
	 * @return float|null
	 */
	public static function milli( string $html ): ?float {
		$text = self::visible_text( $html );
		if ( ! $text ) {
			return null;
		}

		if ( preg_match( '/قیمت\s*۱\s*گرم\s*طلای\s*۱۸\s*عیار\s*میلی\s*([0-9۰-۹٠-٩][0-9۰-۹٠-٩٬،,.\s]*)\s*ریال/u', $text, $match ) ) {
			$value = self::number( $match[1] );
			if ( null !== $value && $value > 0 ) {
				return $value;
			}
		}

		/*
		 * Milli's server-side card can render zero while its public Next.js
		 * payload already contains price18. The widget multiplies this value
		 * by 1,000 before labeling it as Rials.
		 */
		if ( preg_match( '/\\\\?"price18\\\\?"\s*:\s*([0-9]+(?:\.[0-9]+)?)/', $html, $match ) ) {
			$value = self::number( $match[1] );
			if ( null !== $value && $value > 0 ) {
				return $value * 1000;
			}
		}

		return null;
	}

	/**
	 * Convert Persian, Arabic or Latin formatted numbers to float.
	 *
	 * @param string $raw Formatted number.
	 * @return float|null
	 */
	public static function number( string $raw ): ?float {
		$normalized = strtr(
			$raw,
			array(
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
				'٠' => '0',
				'١' => '1',
				'٢' => '2',
				'٣' => '3',
				'٤' => '4',
				'٥' => '5',
				'٦' => '6',
				'٧' => '7',
				'٨' => '8',
				'٩' => '9',
			)
		);
		$normalized = preg_replace( '/[٬،,\s\x{200c}\x{200e}\x{200f}]/u', '', $normalized ) ?? '';
		$normalized = preg_replace( '/[^0-9.\-]/', '', $normalized ) ?? '';

		if ( '' === $normalized || ! is_numeric( $normalized ) ) {
			return null;
		}

		return (float) $normalized;
	}

	/**
	 * Return visible document text without script/style payloads.
	 *
	 * @param string $html Source HTML.
	 * @return string
	 */
	private static function visible_text( string $html ): string {
		$parsed = self::document( $html );
		if ( ! $parsed ) {
			return '';
		}

		list( $document, $xpath ) = $parsed;
		$hidden                    = $xpath->query( '//script|//style|//noscript|//svg' );
		if ( $hidden ) {
			foreach ( iterator_to_array( $hidden ) as $node ) {
				$node->parentNode?->removeChild( $node );
			}
		}

		return self::clean_text( $document->textContent );
	}

	/**
	 * Load HTML without network access or external entities.
	 *
	 * @param string $html Source HTML.
	 * @return array{0: \DOMDocument, 1: \DOMXPath}|null
	 */
	private static function document( string $html ): ?array {
		if ( '' === trim( $html ) || ! class_exists( \DOMDocument::class ) ) {
			return null;
		}

		$document = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$loaded   = $document->loadHTML(
			'<?xml encoding="utf-8" ?>' . $html,
			LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return $loaded ? array( $document, new \DOMXPath( $document ) ) : null;
	}

	private static function clean_text( string $text ): string {
		return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ?? '' );
	}
}
