<?php
/**
 * Iframe CSS generator for Beds24 admin fields.
 *
 * Generates a CSS string targeting Beds24's booking page DOM (Layout 6 with
 * Offer Select module). The output is pasted by the operator into Beds24's
 * "Insert in HTML <HEAD> bottom" admin field per the property setup workflow
 * in skills/beds24-property-rollout/references/property-setup.md.
 *
 * Selectors in the template are verbatim from docs/reference/CSS-base.css
 * (predecessor project, battle-tested against Beds24's Layout 6 DOM).
 * Do not rename them — they must match Beds24's rendered output exactly.
 *
 * See docs/styling-contract.md Decision 5 and §"Iframe CSS generation".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return default token values for iframe CSS generation.
 *
 * Public token keys match the styling contract roles in docs/styling-contract.md.
 * Keys prefixed with _ are internal defaults not exposed as public contract tokens.
 * Font size defaults use px rather than rem: rem values inside a cross-origin
 * iframe depend on Beds24's root font size, which the plugin cannot control.
 *
 * @return array<string, string>
 */
function beds24_iframe_css_defaults(): array {
	return [
		// --- Public tokens (docs/styling-contract.md §"Design tokens consumed") ---
		'primary'             => '#2563eb',
		'primary-text'        => '#ffffff',
		'accent'              => '#f59e0b',
		'surface'             => '#ffffff',
		'surface-text'        => '#1f2937',
		'surface-muted'       => '#6b7280',
		'border'              => '#e5e7eb',
		'font-family-body'    => 'system-ui, -apple-system, sans-serif',
		'font-family-heading' => 'system-ui, -apple-system, sans-serif',
		'font-size-base'      => '16px',
		'font-size-small'     => '13px',
		'font-weight-body'    => '400',
		'font-weight-heading' => '600',
		'space-sm'            => '8px',
		'space-md'            => '16px',
		'border-radius'       => '8px',
		'border-radius-small' => '6px',
		'shadow-card'         => '0 1px 3px rgba(0,0,0,0.1)',
		// Color tokens (contract roles not previously in defaults)
		'success'             => '#10b981',
		'unavailable'         => '#9ca3af',
		'error'               => '#dc2626',
		'error-bg'            => '#fef2f2',
		'error-border'        => '#fecaca',
		// Typography tokens (contract roles not previously in defaults)
		// font-size-large: 1.25rem → px; rem inside a cross-origin iframe depends
		// on Beds24's root font size (uncontrolled), so px is the safe unit.
		'font-size-large'     => '20px',
		'line-height-body'    => '1.5',
		'line-height-heading' => '1.2',
		// Spacing tokens (contract roles not previously in defaults)
		// Rem values converted to px for the same iframe-predictability reason.
		'space-xs'            => '4px',   // 0.25rem
		'space-lg'            => '24px',  // 1.5rem
		'space-xl'            => '32px',  // 2rem
		// Layout tokens (contract roles not previously in defaults)
		'shadow-floating'     => '0 -2px 8px rgba(0,0,0,0.1)',
		// --- Internal defaults (not public contract tokens) ---
		// _page-bg: iframe body background; no public token distinguishes
		// page bg from card surface bg.
		'_page-bg'            => '#f7fafc',
	];
}

/**
 * Determine whether a CSS font-family value requires a Google Fonts @import rule.
 *
 * Returns false for system font stacks and well-known platform-bundled fonts —
 * these are pre-installed on the browser's OS and need no external load.
 * Returns true for named web fonts (Google Fonts, custom web fonts) where
 * the browser will not have the font pre-installed.
 *
 * Only the first token in the font-family stack (the primary, non-fallback
 * font) is examined. Fallback fonts like "sans-serif" or "Arial" do not
 * trigger an import when they appear after a primary named font.
 *
 * @param string $font_family CSS font-family value (may be a comma-separated stack).
 * @return bool True if an @import is needed; false for system/platform fonts.
 */
function beds24_font_needs_import( string $font_family ): bool {
	static $system_fonts = [
		// CSS generic families (always resolved by the browser without loading).
		'sans-serif', 'serif', 'monospace', 'cursive', 'fantasy',
		'math', 'emoji', 'fangsong',
		// System keyword families (CSS Level 4).
		'system-ui', '-apple-system', 'BlinkMacSystemFont',
		'ui-serif', 'ui-sans-serif', 'ui-monospace', 'ui-rounded',
		// Common platform-bundled fonts that need no @import.
		'Arial', 'Helvetica', 'Helvetica Neue', 'Georgia', 'Verdana', 'Tahoma',
		'Trebuchet MS', 'Times New Roman', 'Times', 'Courier New', 'Courier',
		'Impact', 'Comic Sans MS', 'Comic Sans', 'Palatino', 'Garamond',
		'Bookman', 'Arial Black', 'Arial Narrow',
		'Segoe UI', 'Segoe UI Emoji', 'Segoe UI Symbol',
		'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Console',
		'Ubuntu', 'Cantarell', 'DejaVu Sans', 'Liberation Sans',
	];

	$font_family = trim( $font_family );
	if ( '' === $font_family ) {
		return false;
	}

	// Extract the first font in the stack (before the first comma).
	$parts = explode( ',', $font_family );
	$first = trim( $parts[0], " \t\n\r\0\x0B\"'" );

	if ( '' === $first ) {
		return false;
	}

	foreach ( $system_fonts as $system ) {
		if ( 0 === strcasecmp( $first, $system ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Generate @import rules for web fonts referenced by the resolved token set.
 *
 * For each font-family token (body, heading) that references a non-system font,
 * generates an appropriate @import rule:
 *
 *   - If $font_sources provides a 'google' type entry for the role, that URL
 *     is used directly (the URL comes from the theme's fontFace src array).
 *   - If $font_sources marks the role as 'local', no @import is generated
 *     (@font-face for self-hosted theme fonts is out of scope for V1).
 *   - If no source is known (empty $font_sources or 'none' type), the font
 *     name is used to construct a Google Fonts URL. This is the path for
 *     fonts entered in admin settings and for theme.json fonts without fontFace
 *     Google Fonts URLs.
 *
 * Duplicate imports (body and heading resolve to the same font name) are
 * deduplicated so the output contains each @import at most once.
 *
 * @param array<string, string> $tokens      Resolved token values (after merging defaults + overrides).
 * @param array<string, array>  $font_sources Source info from beds24_read_theme_font_sources().
 * @return string @import rules (with trailing newlines) or empty string.
 */
function beds24_generate_font_imports( array $tokens, array $font_sources = [] ): string {
	$font_roles = [
		'font-family-body'    => $tokens['font-family-body']    ?? '',
		'font-family-heading' => $tokens['font-family-heading'] ?? '',
	];

	$imports = [];

	foreach ( $font_roles as $role => $font_family ) {
		if ( ! beds24_font_needs_import( $font_family ) ) {
			continue;
		}

		$source = $font_sources[ $role ] ?? null;

		if ( null !== $source && 'google' === $source['type'] && ! empty( $source['url'] ) ) {
			// Use the Google Fonts CSS URL from the theme's fontFace data.
			$imports[] = "@import url('" . esc_url_raw( $source['url'] ) . "');";
			continue;
		}

		if ( null !== $source && 'local' === $source['type'] ) {
			// Self-hosted font — @font-face is out of scope for V1.
			// Fall through to family-name heuristic: if the theme uses a
			// local copy of a Google Font, generate the import from the name.
			// This ensures the iframe can render the font even without resolving
			// local file paths across origin boundaries.
		}

		// No Google Fonts URL known: derive the @import URL from the family name.
		// This handles admin-settings entries (plain family name, no URL) and
		// theme.json fonts with only local file sources.
		$parts          = explode( ',', $font_family );
		$family         = trim( $parts[0], " \t\n\r\0\x0B\"'" );

		if ( '' === $family ) {
			continue;
		}

		// URL-encode the family name: spaces → '+' per Google Fonts v2 API convention.
		$family_encoded = rawurlencode( $family );
		$imports[]      = "@import url('https://fonts.googleapis.com/css2?family={$family_encoded}:wght@400;600&display=swap');";
	}

	$imports = array_unique( $imports );

	if ( empty( $imports ) ) {
		return '';
	}

	return implode( "\n", $imports ) . "\n\n";
}

/**
 * Generate a complete CSS string for Beds24's iframe admin field.
 *
 * Calls beds24_iframe_css_defaults() for baseline values, then merges
 * the supplied $tokens array on top. Each token value is sanitized to
 * strip characters that would break a CSS declaration block.
 *
 * When the resolved font-family tokens reference web fonts, @import rules
 * are prepended automatically. For Google Fonts: if $font_sources provides
 * a URL, it is used; otherwise the font name is used to construct the URL.
 * System font stacks (system-ui, -apple-system, sans-serif, etc.) produce
 * no @import. See beds24_generate_font_imports() for full logic.
 *
 * The output is a self-contained CSS string suitable for direct paste into
 * Beds24's "Insert in HTML <HEAD> bottom" field.
 *
 * @param array<string, string> $tokens      Token overrides. Keys are role names
 *                                            from beds24_iframe_css_defaults().
 *                                            Missing keys use defaults.
 * @param array<string, array>  $font_sources Font source info from
 *                                            beds24_read_theme_font_sources().
 *                                            Empty array when no theme.json data.
 * @return string Complete CSS string, with @import rules prepended when needed.
 */
function beds24_generate_iframe_css( array $tokens = [], array $font_sources = [] ): string {
	$t = array_merge( beds24_iframe_css_defaults(), $tokens );

	// Strip characters that would escape a CSS value or terminate a
	// declaration: braces, semicolons, and ASCII control characters.
	foreach ( $t as $key => $value ) {
		$t[ $key ] = preg_replace( '/[{};\x00-\x1f]/', '', (string) $value );
	}

	// Prepend @import rules for any web fonts the resolved tokens reference.
	$font_import = beds24_generate_font_imports( $t, $font_sources );

	// Build the :root variable block. CSS custom property names follow the
	// --b24-* namespace used in the predecessor stylesheet so the static CSS
	// rules below can reference them via var() without change.
	$root  = ":root {\n";
	$root .= "  --b24-color-primary:       {$t['primary']};\n";
	$root .= "  --b24-color-btn-text:      {$t['primary-text']};\n";
	$root .= "  --b24-color-accent:        {$t['accent']};\n";
	$root .= "  --b24-color-bg-white:      {$t['surface']};\n";
	$root .= "  --b24-color-text:          {$t['surface-text']};\n";
	$root .= "  --b24-color-text-light:    {$t['surface-muted']};\n";
	$root .= "  --b24-color-border:        {$t['border']};\n";
	$root .= "  --b24-font-body:           {$t['font-family-body']};\n";
	$root .= "  --b24-font-heading:        {$t['font-family-heading']};\n";
	$root .= "  --b24-font-size-sm:        {$t['font-size-small']};\n";
	$root .= "  --b24-font-size-lg:        {$t['font-size-base']};\n";
	$root .= "  --b24-font-weight-body:    {$t['font-weight-body']};\n";
	$root .= "  --b24-font-weight-heading: {$t['font-weight-heading']};\n";
	$root .= "  --b24-space-sm:            {$t['space-sm']};\n";
	$root .= "  --b24-space-md:            {$t['space-md']};\n";
	$root .= "  --b24-radius-sm:           {$t['border-radius-small']};\n";
	$root .= "  --b24-radius-md:           {$t['border-radius']};\n";
	$root .= "  --b24-shadow-sm:           {$t['shadow-card']};\n";
	$root .= "  --b24-color-success:       {$t['success']};\n";
	$root .= "  --b24-color-unavailable:   {$t['unavailable']};\n";
	$root .= "  --b24-color-error:         {$t['error']};\n";
	$root .= "  --b24-color-error-bg:      {$t['error-bg']};\n";
	$root .= "  --b24-color-error-border:  {$t['error-border']};\n";
	$root .= "  --b24-font-size-xl:        {$t['font-size-large']};\n";
	$root .= "  --b24-line-height-body:    {$t['line-height-body']};\n";
	$root .= "  --b24-line-height-heading: {$t['line-height-heading']};\n";
	$root .= "  --b24-space-xs:            {$t['space-xs']};\n";
	$root .= "  --b24-space-lg:            {$t['space-lg']};\n";
	$root .= "  --b24-space-xl:            {$t['space-xl']};\n";
	$root .= "  --b24-shadow-float:        {$t['shadow-floating']};\n";
	$root .= "  --b24-color-bg:            {$t['_page-bg']};\n";
	$root .= "}\n\n";

	// Static CSS rules targeting Beds24's booking flow DOM.
	// !important declarations are required: Beds24's Bootstrap base styles
	// have high specificity and must be overridden.
	//
	// Room-list selectors (.at_roomnametext, .b24room, .b24panel-room,
	// .tnh-room-tags, .b24-room-slider, grid layout) were removed in
	// Session 24. The current plugin architecture hands off to Beds24's
	// iframe for transactions (guest form, payment) — not for room
	// discovery. The offer-area and body selectors below apply during
	// the booking flow and are retained.
	$static = <<<'CSSBLOCK'
/* ----------------------------------------------------------------
   Beds24 Booking Page Styles
   Generated by Beds24 Booking Plugin — do not edit manually.
   Regenerate: WordPress admin > Beds24 Booking > Property Setup.
   Targets: Layout 6 with Offer Select module only.
   Font loading note: if using a custom web font, add @import rules
   above this block in the Beds24 "Insert in HTML <HEAD> bottom" field.
   ---------------------------------------------------------------- */

/* Structural resets */
.b24fullcontainer-rooms{border:none!important;outline:none!important;padding:16px!important}
.b24fullcontainer-rooms .container{width:100%!important;max-width:100%!important;padding:0!important}

/* Body */
.colorbody{font-family:var(--b24-font-body)!important;color:var(--b24-color-text)!important;line-height:var(--b24-line-height-body)!important;background:var(--b24-color-bg)!important}

/* Offer area */
.b24-offer-pricetable{display:none!important}
.b24-offer-select{width:100%!important;max-width:100%!important;padding:0!important;border-top:none!important}
.b24-offer-select .multiroomshow{display:flex!important;align-items:center!important;gap:8px!important;flex-wrap:nowrap!important;width:100%!important}
.b24-offer-select .b24-multipricebox{display:flex!important;align-items:baseline!important;gap:8px!important;flex-wrap:nowrap!important;flex:1!important}
.b24-offer-select .form-inline{display:flex!important;align-items:center!important;gap:var(--b24-space-sm)!important;flex-shrink:0!important}
[id^="from-"],.b24-multipricebox [id^="from-"],.b24-multipricebox .at_offerfromdiv{display:inline-block!important;white-space:nowrap!important;flex-shrink:1!important;min-width:0!important;flex-grow:1!important}
.tnh-price-pernight-main{font-size:var(--b24-font-size-sm)!important;white-space:nowrap!important;font-weight:500!important;color:var(--b24-color-text-light)!important}
.tnh-total-price{font-size:15px!important;font-weight:700!important;color:var(--b24-color-text)!important;white-space:nowrap!important;flex-shrink:0!important;order:1!important}
.tnh-book-btn{margin-top:0!important;float:none!important;flex-shrink:0!important;margin-left:auto!important;order:2!important}
.b24-offer-select .b24-multipricebox .tnh-offer-row{display:contents!important}
.b24-multipricebox.hidden,.b24-offer-select .b24-multipricebox.hidden{display:none!important}

/* Hide per-occupancy extra priceboxes.
   Beds24 removes .hidden from these on qty > 0 (Per Occupancy Pricing rooms).
   Without this rule they become flex siblings of the main box and halve its
   width, causing the Book button to wrap on mobile.
   See docs/reference/CSS-base.css for full diagnostic notes. */
[id^="selectors1-"] .b24-multipricebox:not(.pull-right){display:none!important}

/* Generic Beds24 element hides */
.fakelink{display:none!important}
select[id^="naa"]{display:none!important}
.at_offername{display:none!important}
.offer hr{display:none!important}
[id^="price-"][class*="b24-roomprice"]{display:none!important}

/* Quantity and offer selects */
select[id^="sr1-"],select[id^="naa"]{border:1.5px solid var(--b24-color-border)!important;border-radius:var(--b24-radius-sm)!important;padding:4px 8px!important;font-family:var(--b24-font-body)!important;font-size:var(--b24-font-size-sm)!important;color:var(--b24-color-text)!important;background:var(--b24-color-bg-white)!important}

/* selectors1 flex fix: live Beds24 page wraps the qty select in an extra div
   that must participate in the flexbox offer row. */
[id^="selectors1-"]{display:flex!important;flex:1!important;min-width:0!important}
[id^="selectors1-"].hidden{display:none!important}

/* Mobile (<=767px) */
@media(max-width:767px){
  .b24-offer-select .b24-multipricebox{flex-wrap:wrap!important;gap:8px!important}
  .b24-offer-select .b24-multipricebox [id^="from-"],
  .b24-offer-select .b24-multipricebox .at_offerfromdiv{display:block!important;width:100%!important;order:-1!important;margin-bottom:4px!important;flex-grow:0!important;text-align:left!important}
  .tnh-price-pernight-main{font-size:12px!important}
  .tnh-total-price{font-size:14px!important;margin-left:auto!important}
  .tnh-book-btn{margin-left:0!important}
  .b24fullcontainer-rooms{padding:16px 0!important}
}
CSSBLOCK;

	// Rules targeting Beds24's booking form pages (guest details, payment,
	// confirmation). Selectors verified against the live Beds24 DOM via
	// Session 23 Pass 2 browser inspection unless marked UNVERIFIED.
	$extended  = "\n/* === Booking form: error states === */\n";
	$extended .= "/* .booktexterror — verified: error message text on guest details page */\n";
	$extended .= ".booktexterror{color:var(--b24-color-error)!important}\n";
	$extended .= "/* .booktexterrordiv — verified: wrapper div around error text */\n";
	$extended .= ".booktexterrordiv{border-color:var(--b24-color-error-border)!important;background:var(--b24-color-error-bg)!important}\n";
	$extended .= "\n/* === Booking form: section spacing === */\n";
	$extended .= "/* .b24-guestdetails / .b24-bookingdetails — verified on guest details page */\n";
	$extended .= ".b24-guestdetails{margin-bottom:var(--b24-space-xl)!important}\n";
	$extended .= ".b24-bookingdetails{margin-bottom:var(--b24-space-lg)!important}\n";
	$extended .= "\n/* === Unavailable room state — UNVERIFIED (could not trigger in Session 23) === */\n";
	$extended .= ".at_offerunavailable,.b24-room-unavailable{color:var(--b24-color-unavailable)!important}\n";

	return $font_import . $root . $static . $extended;
}
