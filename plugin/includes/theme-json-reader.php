<?php
/**
 * Theme.json token reader for the Beds24 Booking Plugin.
 *
 * Reads design tokens from the active WordPress theme's theme.json and maps
 * them to the plugin's token roles (docs/styling-contract.md §"Design tokens
 * consumed"). The output is merged on top of beds24_iframe_css_defaults() in
 * the iframe CSS generator so theme-derived values override the defaults.
 *
 * Uses wp_get_global_settings() — the WordPress-canonical API for reading
 * theme.json. This function handles parent/child theme inheritance and
 * user/theme layering automatically via the block editor's settings engine.
 *
 * When the active theme has no theme.json (classic themes, hybrid themes that
 * haven't adopted FSE), wp_get_global_settings() returns an empty or nearly
 * empty array. This is the expected V1 behavior for non-block themes: the
 * reader returns an empty array, and the generator falls back to its defaults.
 *
 * See docs/styling-contract.md Decision 1, Decision 3, and §"Design tokens
 * consumed" for the contract this reader implements.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read design tokens from the active theme's theme.json.
 *
 * Maps theme.json settings to plugin token roles per the styling contract.
 * Only roles that can be mapped are returned — missing keys fall back to
 * beds24_iframe_css_defaults() values in the generator.
 *
 * Mapping logic per token category:
 *
 * **Colors:** settings.color.palette is searched for palette entries whose
 * slug matches the plugin's role name ('primary', 'accent', 'surface', etc.).
 * Only exact slug matches are used. The 'color' value from the matching palette
 * entry is returned as-is.
 *
 * **Typography:** settings.typography.fontFamilies is searched for entries
 * whose slug matches 'body', 'heading', or 'display'. If 'body' is not found,
 * the first entry in fontFamilies is used as the fallback for all three roles.
 * Font family values may include CSS stack fallbacks (e.g., "Lexend, sans-serif")
 * — they are returned as-is; the generator emits them directly into CSS.
 *
 * **Spacing:** settings.spacing.spacingSizes (or .spacingScale presets) is
 * searched for entries whose slug matches 'xs', 'sm', 'md', 'lg', 'xl'.
 * WordPress spacing preset sizes are unitful strings (e.g., "0.5rem", "1rem").
 * The generator uses px for iframe CSS — spacing from theme.json may be rem.
 * That is acceptable: rem in the Beds24 iframe resolves against Beds24's root
 * font size (typically 16px), which is close enough for V1.
 *
 * **Border radius:** settings.custom.borderRadius is read if present. Some
 * themes (Twenty Twenty-Five) put their border-radius value there.
 *
 * @return array<string, string> Token values keyed by role name. Empty array
 *                               if the active theme has no theme.json or none
 *                               of the expected keys are present.
 */
function beds24_read_theme_tokens(): array {
	// wp_get_global_settings() is available since WordPress 5.9.
	// It requires no arguments; called with no args it returns all settings
	// merged from theme.json, user settings, and WordPress defaults.
	if ( ! function_exists( 'wp_get_global_settings' ) ) {
		// WordPress < 5.9 — no theme.json support; return empty.
		return [];
	}

	$settings = wp_get_global_settings();

	// If settings is empty or not an array, the active theme has no theme.json.
	if ( ! is_array( $settings ) || empty( $settings ) ) {
		return [];
	}

	$tokens = [];

	// -------------------------------------------------------------------
	// Color palette mapping
	// -------------------------------------------------------------------
	// Look for palette entries whose slug exactly matches each plugin role name.
	// Contract role slugs and their expected theme.json palette slugs:
	//   primary, primary-text, accent, surface, surface-text, surface-muted,
	//   border, success, unavailable, error, error-bg, error-border.
	// Theme.json palette entry shape: { slug, color, name }.

	$palette = $settings['color']['palette']['theme'] ?? [];
	if ( ! is_array( $palette ) || empty( $palette ) ) {
		// Some themes store palette at ['color']['palette'] directly (no 'theme'
		// sub-key). Try the flat path as a fallback.
		$palette = $settings['color']['palette'] ?? [];
		// If it's still a nested array with 'theme'/'default' keys rather than a
		// flat list of palette entries, normalize to empty.
		if ( ! is_array( $palette ) || ( isset( $palette['theme'] ) || isset( $palette['default'] ) ) ) {
			$palette = [];
		}
	}

	$color_roles = [
		'primary',
		'primary-text',
		'accent',
		'surface',
		'surface-text',
		'surface-muted',
		'border',
		'success',
		'unavailable',
		'error',
		'error-bg',
		'error-border',
	];

	if ( ! empty( $palette ) ) {
		// Build a slug→color lookup for O(1) access.
		$palette_by_slug = [];
		foreach ( $palette as $entry ) {
			if ( isset( $entry['slug'], $entry['color'] ) ) {
				$palette_by_slug[ $entry['slug'] ] = $entry['color'];
			}
		}

		foreach ( $color_roles as $role ) {
			if ( isset( $palette_by_slug[ $role ] ) ) {
				$tokens[ $role ] = $palette_by_slug[ $role ];
			}
		}
	}

	// -------------------------------------------------------------------
	// Typography mapping
	// -------------------------------------------------------------------
	// theme.json fontFamilies path: settings.typography.fontFamilies.theme
	// Entry shape: { slug, fontFamily, name }.
	// fontFamily may be a CSS stack string: "Lexend, sans-serif".

	$font_families_theme = $settings['typography']['fontFamilies']['theme'] ?? [];
	$font_families_flat  = $settings['typography']['fontFamilies'] ?? [];

	// Normalize: if no 'theme' sub-key, try the flat path.
	if ( empty( $font_families_theme ) && is_array( $font_families_flat ) ) {
		// Check it looks like a flat list of family entries.
		if ( isset( $font_families_flat[0] ) && isset( $font_families_flat[0]['slug'] ) ) {
			$font_families_theme = $font_families_flat;
		}
	}

	if ( ! empty( $font_families_theme ) && is_array( $font_families_theme ) ) {
		$families_by_slug = [];
		foreach ( $font_families_theme as $entry ) {
			if ( isset( $entry['slug'], $entry['fontFamily'] ) ) {
				$families_by_slug[ $entry['slug'] ] = $entry['fontFamily'];
			}
		}

		// Map role slugs directly.
		if ( isset( $families_by_slug['body'] ) ) {
			$tokens['font-family-body'] = $families_by_slug['body'];
		}
		if ( isset( $families_by_slug['heading'] ) ) {
			$tokens['font-family-heading'] = $families_by_slug['heading'];
		}
		if ( isset( $families_by_slug['display'] ) ) {
			// font-family-display is a contract token but not in
			// beds24_iframe_css_defaults() keys. The generator doesn't use it
			// directly; skip rather than injecting an unknown key.
			// (font-family-heading serves as the display fallback per contract.)
		}

		// If 'body' slug was not found but fontFamilies is non-empty,
		// use the first family as the fallback for body and heading.
		if ( ! isset( $tokens['font-family-body'] ) && ! empty( $families_by_slug ) ) {
			$first_family = reset( $families_by_slug );
			$tokens['font-family-body']    = $first_family;
			$tokens['font-family-heading'] = $first_family;
		}
	}

	// -------------------------------------------------------------------
	// Spacing mapping
	// -------------------------------------------------------------------
	// theme.json spacing path: settings.spacing.spacingSizes
	// Entry shape: { slug, size, name }.
	// Slugs this reader looks for: 'xs', 'sm', 'md', 'lg', 'xl'.
	// Only sets spacing tokens when slugs match exactly.

	$spacing_sizes = $settings['spacing']['spacingSizes']['theme'] ?? [];
	if ( empty( $spacing_sizes ) ) {
		// Flat path fallback.
		$spacing_flat = $settings['spacing']['spacingSizes'] ?? [];
		if ( is_array( $spacing_flat ) && isset( $spacing_flat[0] ) && isset( $spacing_flat[0]['slug'] ) ) {
			$spacing_sizes = $spacing_flat;
		}
	}

	$spacing_roles = [
		'xs' => 'space-xs',
		'sm' => 'space-sm',
		'md' => 'space-md',
		'lg' => 'space-lg',
		'xl' => 'space-xl',
	];

	if ( ! empty( $spacing_sizes ) && is_array( $spacing_sizes ) ) {
		$spacing_by_slug = [];
		foreach ( $spacing_sizes as $entry ) {
			if ( isset( $entry['slug'], $entry['size'] ) ) {
				$spacing_by_slug[ $entry['slug'] ] = $entry['size'];
			}
		}

		foreach ( $spacing_roles as $slug => $role ) {
			if ( isset( $spacing_by_slug[ $slug ] ) ) {
				$tokens[ $role ] = $spacing_by_slug[ $slug ];
			}
		}
	}

	// -------------------------------------------------------------------
	// Border radius mapping
	// -------------------------------------------------------------------
	// theme.json border radius is non-standard — no WP preset for it.
	// Twenty Twenty-Five stores it at settings.custom.borderRadius (a
	// plain string or array). We read a scalar value if present.

	$custom          = $settings['custom'] ?? [];
	$border_radius   = $custom['borderRadius'] ?? null;

	if ( is_string( $border_radius ) && '' !== $border_radius ) {
		$tokens['border-radius'] = $border_radius;
	} elseif ( is_array( $border_radius ) ) {
		// Some themes nest: { medium: "0.5rem", small: "0.25rem" }.
		// Map 'medium' → border-radius, 'small' → border-radius-small.
		if ( isset( $border_radius['medium'] ) && is_string( $border_radius['medium'] ) ) {
			$tokens['border-radius'] = $border_radius['medium'];
		}
		if ( isset( $border_radius['small'] ) && is_string( $border_radius['small'] ) ) {
			$tokens['border-radius-small'] = $border_radius['small'];
		}
	}

	return $tokens;
}
