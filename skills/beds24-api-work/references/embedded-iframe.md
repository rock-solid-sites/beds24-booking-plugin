# Embedded Iframe — Beds24 Booking Page Integration

**Source:** https://wiki.beds24.com/index.php/Embedded_Iframe
**Captured:** 2026-05-07

---

This page is about the menu (SETTINGS) BOOKING ENGINE > BOOKING WIDGETS > iFrame GENERATOR and explains how to embed the booking page into a web site.

> Using iFrames are convenient but may have a sub optimal usability experience. Consider opening the booking page in a new tab instead of embedding it in an iFrame.

## How to reliably transfer dates and other data from a booking widget to an embedded iFrame

In recent times browser security and privacy concerns have led to more blocking of third party cookies which can also block the ability to pass data from the widget to the iFrame.

The recommended solution passes the widget data directly to the page holding the iFrame and relies on a special script on the page to pass the parameters to the iFrame. The iFrame then loads the booking page with the data from the widget.

The principle: open the URL containing the iFrame directly with the booking page parameters added to your URL after a `?`. The script on the page passes these URL parameters through to the iFrame.

### Valid parameters for passthrough

The official Beds24 iframe-passthrough script only handles these parameters:

```
checkin, checkout, numnight, numadult, numchild, ownerid, propid, roomid,
referer, lang, group, nogroup, category1, category2, category3, category4, customParameter
```

**Note for plugin implementation:** The `sr1-{roomId}` and `naa1-1-{roomId}` multi-room parameters observed in live booking URLs are **not in this list**. They are not documented in official Beds24 wiki pages. They appear to be internal parameters used by Beds24's own multi-room booking flow, possibly specific to `booking3.php`. See Known Unknowns in `docs/architecture.md`.

### Step-by-step setup

**Step 1: Customize the widget**

Go to (SETTINGS) BOOKING ENGINE > BOOKING WIDGETS

Set the value of the "formAction" parameter to be the full URL of the page containing the iFrame.
Make sure the "Redirect URL" field is blank.
SAVE.

**Step 2: Install the widget on your website**

Click on "Get Code". Copy the code and paste into the HTML of your website.

**Step 3: Customize the iFrame**

Go to (SETTINGS) BOOKING ENGINE > BOOKING WIDGETS > EMBEDDED iFrame and set:
- "Opening Checkin Date" = Default
- "Length of Stay" = Default

**Step 4: Generate and modify the iFrame snippet**

Click on "Get Code". Copy the code.
Change the element `src` to be called `data-src` and leave the URL the same. There should be no `src` in the iFrame; the script will add it.

**Step 5: Install the iFrame and script on your website**

Paste the modified code into the HTML of your website where you want to show the iFrame.
Add the following script directly after the `</iFrame>` end tag:

Example iframe:
```html
<iFrame data-src="https://beds24.com/booking2.php?propid=12345&referer=iFrame" width="800" height="2000" style="max-width:100%;border:none;overflow:auto;"><p><a href="https://beds24.com/booking2.php?propid=12345&referer=iFrame" title="Book Now">Book Now</a></p></iFrame>
```

Script:
```html
<script>
var addUrlParamsToIframeSrcs = function() {
  const validParameters = ["checkin", "checkout", "numnight", "numadult", "numchild",
    "ownerid", "propid", "roomid", "referer", "lang", "group", "nogroup",
    "category1", "category2", "category3", "category4", "customParameter"];
  const currentUrl = new URL(window.location.href);
  let parametersString = "";
  validParameters.forEach(parameter => {
    const parameterValue = currentUrl.searchParams.get(parameter);
    if (parameterValue !== null) {
      parametersString += "&" + parameter + "=" + parameterValue;
    }
  });
  const iframes = document.getElementsByTagName("iframe");
  for (let iframe of iframes) {
    let iframeSrc = iframe.getAttribute("data-src");
    if (iframeSrc === null) { continue; }
    if (!iframeSrc.includes("?")) { iframeSrc += "?"; }
    iframeSrc += parametersString;
    iframe.setAttribute("src", iframeSrc);
  }
};
addUrlParamsToIframeSrcs();
</script>
```

## Booking Page for Individual Rooms

You can create a booking page for an individual room by changing the property id in the URL to a room id.

```
https://www.beds24.com/booking.php?roomid=3561
```

## Troubleshooting

### Available dates show as not available

Most likely you have no prices for the guest selection. Use the "Price Checker" to check.

Updates made in the CALENDAR transfer immediately. For changes to settings that affect availability (e.g., number of units), the update can take up to 24 hours.

### Scrollbar

Two possible reasons:
1. The iFrame has not enough height for the content — raise the `height` parameter.
2. The container on your website which holds the iFrame does not have enough height.

### Dates and/or numbers of guests do not transport from widget

This happens when you specify the dates and/or number of guests in the URL. Remove the `numadult` and `advancedays` parameters from the URL of the iFrame.

Also make sure that the widget has the same guest selection options as the booking page.

**If you allow guests to book multiple rooms on your booking page the number of guests will never transport from the widget.**

### Scroll iFrame to the top

Add `onload="window.parent.parent.scrollTo(0,0)"` to the iFrame.

### Display problems on mobile

iFrames on mobile devices are problematic. Make sure you have the following code in the `<head>`:
```html
<meta name="viewport" content="width=device-width, initial-scale=1">
```

For mobile, consider setting up a special mobile page that opens the booking page in a new tab instead of an iFrame.
