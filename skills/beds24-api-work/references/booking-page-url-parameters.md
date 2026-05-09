# Booking Page URL Parameters (Category: Developers)

**Source:** https://wiki.beds24.com/index.php/Category:Developers
**Captured:** 2026-05-07

---

This page is about the menu (SETTINGS) > BOOKING ENGINE > PROPERTY BOOKING PAGE > DEVELOPERS

The booking page can be embedded in a page using an Iframe or opened with a link or form. It can be fully customised.

## Booking Page Parameters

The behavior of the booking page can be controlled with the following parameters passed in the URL or form. URL Parameters are always added in the format `name=value`. To separate them, the first parameter must have a `?` before it and all following parameters must have an `&` before them.

For example if you want to change the default for the numbers of night selector to 7 nights you change the link from:

```
https://beds24.com/booking2.php?propid=2047
```

to:

```
https://beds24.com/booking2.php?propid=2047&numnight=7
```

### Example: Make a booking page for selected rooms only

Use the propid to open the page and add `&hideroom=22543` to hide a room. You can hide multiple rooms by comma separating them.

```
https://beds24.com/booking2.php?propid=2048&hideroom=3589,3588
```

### Example: Link to the booking page with dates pre-populated

```
https://beds24.com/booking2.php?propid=13437&checkin=2017-8-24&numnight=3
```

- `checkin=2017-8-24` — the date the guest wants to arrive
- `&numnight=3` — the number of nights the guest wants to book

### Example: Link to a booking form with dates and room pre-populated

```
https://beds24.com/booking2.php?checkin=2017-8-24&numnight=3&numadult=2&numchild=0&br1-32919=Book&roomid=32919
```

- `checkin=2017-8-24` — the date the guest wants to arrive
- `&numnight=3` — the number of nights the guest wants to book
- `&numadult=2` — number of adults
- `&numchild=0` — number of children
- `&br1-32919=Book` — exchange 32919 with the room id the guest wants to book (br1 = offer 1, br2 = offer 2, etc.)
- `&roomid=32919` — exchange 32919 with the room id the guest wants to book

### Example: Booking form for a preselected room and dates

```
https://beds24.com/booking2.php?propid=13434&checkin_hide=2016-11-24&numnight=2&numadult=1&br1-32906=Book
```

You need to define:

1. `propid`
2. One of `checkin` or `checkin_hide` (different date formats)
3. One of `checkout`, `checkout_hide` or `numnight`
4. `numadult` and/or `numchild`
5. `br1-32906=Book` where `br1` is offer 1, `br2` is offer 2 etc. and `32906` is the room to book.

### Full Parameter Table

| Parameter | Value | Description |
|---|---|---|
| `advancedays` | number of days | Sets the initial date to this many days in advance of today. Set to `-1` to open at the first date with availability. |
| `checkin` | check-in full date | The page will open at this check-in date (format: `YYYY-M-DD`, e.g. `2017-8-24`) |
| `checkin_hide` | check-in full date | Alternative check-in date parameter (format: `YYYY-MM-DD`, e.g. `2016-11-24`). One of `checkin` or `checkin_hide` is sufficient. |
| `cssfile` | encoded url | External CSS file for inclusion in booking page. Must be available via HTTPS and URL-encoded. |
| `cur` | AUD, CAD, EUR, GBP, NZD, USD, BGN, BRL, CHF, CNY, CZK, DKK, EEK, HKD, HRK, HUF, IDR, INR, JPY, KRW, MXN, MYR, NOK, PHP, PLN, RON, RUB, SEK, SGD, THB, TRY, VND, ZAR | Opens the page showing the currency converted. (responsive booking page only) |
| `fdate_date` | check-in date of month | The page will open at this check-in date; must be used with either `fdate_monthyear` or `fdate_month` and `fdate_year`. Format: `DD` |
| `fdate_monthyear` | check-in month and year | Must be used with `fdate_date`. Format: `MM-YYYY` |
| `fdate_month` | check-in month | Must be used with `fdate_date` and `fdate_year`. Format: `MM` |
| `fdate_year` | check-in year | Must be used with `fdate_date` and `fdate_month`. Format: `YYYY` |
| `group` | keyword | Show only properties that have this group keyword |
| `hidefooter` | yes, no | Do not show the property information at the bottom of the page |
| `hideheader` | yes, no | Do not show the property information at the top of the page. Do not use with "Full Width Slider". |
| `hideoffer` | 1,2,3,4 | Do not show this offer on the booking page; multiple ids can be comma-separated |
| `hideprop` | property id | Do not show this property on the booking page; multiple ids can be comma-separated |
| `hideroom` | room id | Do not show this room on the booking page; multiple ids can be comma-separated |
| `invoicee` | integer | Charges and payments will be assigned to this invoicee ID |
| `lang` | en, ar, bg, ca, hr, cs, da, de, el, es, et, fi, fr, he, hu, hy, id, is, it, ja, ko, lt, lv, mn, my, nl, no, pl, pt, ro, ru, sk, sl, sr, sv, th, tr, vi, zh, zt | Sets the default language |
| `layout` | 1, 2, 3, 4, 5, 6 | Opens the responsive version of the booking page in this layout |
| `maxprop` | number of properties | Show a maximum of this many properties based on sort order |
| `maxroom` | number of rooms | Only show a maximum of this many rooms based on sort order |
| `mobile=1` | — | Display the booking page in mobile view |
| `mobile=0` | — | Display the booking page in desktop view |
| `multiroom` | 0, 1 | Opens the page with multi-room booking selected or unselected (2013 new style booking page only) |
| `numnight` | number of nights | Initial value for the number of nights selector |
| `numadult` | number of adults | Initial value for the number of adults selector |
| `numchild` | number of children | Initial value for the number of children selector |
| `numdisplayed` | number of nights with prices displayed | Only applies to price table pages |
| `nogroup` | keyword | Exclude properties that have this group keyword |
| `ownerid` | id number of owner | The page will open showing all properties and rooms for this owner |
| `propid` | id number of property | The page will open showing this property |
| `redirect` | encoded url | Redirect to this url after booking. Must start with `http://` or `https://` and be URL-encoded. |
| `referer` | text | This text will be recorded with any bookings originating from this widget (for source tracking) |
| `roomid` | id number of room | The page will open showing this room |
| `toproom` | room id | Always shown on the top of the bookingpage; multiple room IDs separated by comma (responsive booking page only) |
| `voucher` | voucher code | Pre-populates the discount voucher code |
| `width` | page width in px | Useful for embedding the booking page in an Iframe of fixed size |

### Room booking parameter format

`br{offer}-{roomId}=Book` — book a specific offer for a specific room.

- `br1-{roomId}=Book` — book offer 1 (the first/default offer)
- `br2-{roomId}=Book` — book offer 2, etc.

## Notes for Plugin Implementation

### checkin vs checkin_hide

The documentation confirms both `checkin` and `checkin_hide` are valid. The docs show:
- `checkin=2017-8-24` (YYYY-M-DD format, single-digit months/days allowed)
- `checkin_hide=2016-11-24` (YYYY-MM-DD format)

Per the architecture doc, both `checkin=We+6+May+2026` and `checkin_hide=2026-5-6` were observed in a live multi-room booking URL. The documented formats above are the authoritative inputs. Try `checkin_hide` alone first (YYYY-MM-DD format); use both if needed.

### Multi-room parameters: sr1- and naa1- (undocumented here)

The architecture doc notes these parameters from a live multi-room booking URL observation:
- `sr1-{roomId}=N` — units requested for room
- `naa1-1-{roomId}=N` — numAdults for room (bed count for dorms)

These parameters are **not listed in the documentation table above**. The documented single-room format uses `br1-{roomId}=Book`. The `sr1-`/`naa1-` parameters may be the multi-room equivalent used by `booking3.php` (the page used in the architecture doc's URL scheme vs. `booking2.php` documented here). Verify empirically at implementation — see Known Unknowns in `docs/architecture.md`.

### External CSS file

The `&cssfile=` parameter accepts a URL-encoded HTTPS URL. This is used by the plugin to apply booking-page styling overrides.

## Other Developer Features

### Custom CSS

Can be added via (SETTINGS) BOOKING ENGINE > PROPERTY BOOKING PAGE > DEVELOPERS > "Custom CSS"

### External CSS file

Add `&cssfile={url-encoded-https-url}` to the booking page URL.

### Skip to Checkout Page from Beds24 Plugin Widget

Add `customParameter:'br1-xxxx=book'` to the widget (replace `xxxx` with the room ID).

### Suppress Google Translate

Add the following in the "Head" section:

```html
<meta name="google" content="notranslate">
<html translate="no">
```
