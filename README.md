# Beds24 Booking Plugin

A WordPress plugin that renders a Beds24 booking experience with full
design control. Uses the Beds24 v2 API for live availability and
pricing, with hand-off to Beds24's iframe for the booking form and
payment processing.

**Status:** Pre-release. Active development.

## Why this exists

Beds24's default booking page is functional but difficult to
customize and difficult for non-technical operators to manage. This
plugin moves room content management to WordPress (descriptions,
photos, amenities) while keeping payment processing in Beds24's
battle-tested infrastructure.

## What it does

- Renders a search form (date pickers only) and results page in
  WordPress
- Pulls live availability and pricing from the Beds24 v2 API per
  search
- Displays room cards with photos, descriptions, and amenities
  managed in the WordPress admin
- Composes multi-room booking carts that hand off to Beds24 in a
  single transaction
- Serves photos through WordPress media library (LiteSpeed and
  CDN-optimized)

## What it does not do

- Process payments (Beds24 handles this)
- Store credit card data (no card data ever touches WordPress)
- Replace Beds24 admin for booking management
- Send confirmation emails (Beds24's Auto Actions handle this)
- Sync bookings out of Beds24

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- Beds24 account with v2 API access
- A Beds24 property configured with the minimum layout (Layout 6
  with Offer Select only)

## Installation

Currently installable only from GitHub releases (pre-release):

1. Download the latest release ZIP from the Releases page
2. WordPress admin → Plugins → Add New → Upload Plugin
3. Activate

## Configuration

After activation, configure under WordPress admin → Settings →
Beds24 Booking. Detailed setup is in `docs/setup.md` (when
established).

## Status

This plugin is in active development. Production use is not yet
supported. The roadmap and current state are documented in
`docs/architecture.md` and the session handoffs in `docs/`.

## Contributing

Issues and pull requests welcome. See `CONTRIBUTING.md` (when
established) for guidelines.

## License

GPL-2.0-or-later. See `LICENSE`.
