# OTAs: How to connect to Beds24 using API V2

**Source:** https://wiki.beds24.com/index.php/OTAs:_How_to_connect_to_Beds24_using_API_V2
**Captured:** 2026-05-07

---

This page explains how OTAs can develop API connectivity with Beds24

## Contents

1. Use case
2. The Basics
3. The API
4. API V2 Authentication
5. Retrieving property data from Beds24
6. Price and availability
   - 6.1 Calendar
   - 6.2 Availability
   - 6.3 Offers
7. Bookings

## Use case

You are an OTA, Booking Engine, or similar, and want to retrieve property, price and availability information from Beds24 to generate bookings which you then send back to Beds24.

Your developer develops the integration between your system and Beds24.

## The Basics

It is a good idea to get a basic understanding of how Beds24 works.

We recommend going through this introduction and creating a test property with prices and any data you intend to use.

## The API

Beds24 has two API versions, V1 and V2, we recommend using V2 for any new projects.

Documentation for API V2 is available here.

There is also interactive Swagger documentation. We recommend using Swagger to try the examples in this guide.

Beds24 API's have strict usage limits and you need to design your interface to stay within these limits. This may mean caching data which is not expected to change often at your side rather than making a call each time you need it and only making calls when necessary. The Beds24 API is not intended or suitable for applications making significant numbers of real time calls, for example in response to public user actions. Data required in real time for your public pages should be cached at your end.

## API V2 Authentication

API V2 uses refresh tokens which generate access tokens, these tokens will be included as headers in your requests in this format: "token: {token}"

To obtain a refresh token, first go the API page in the control panel and click the generate invite code button. Select the scopes you require then click the generate invite code button. You will now see an invite code on this page, you can copy this to your clipboard and use the GET /authentication/setup endpoint to exchange your invite code for a refresh token.

Request:

```bash
curl -X 'GET' \
  'https://beds24.com/api/v2/authentication/setup' \
  -H 'accept: application/json' \
  -H 'code: abc123'
```

Response:

```json
{
  "token": "qEK5L...",
  "expiresIn": 86400,
  "refreshToken": "iexTC..."
}
```

In the above example, "token" is a short term access token that you will use to authenticate other API calls, "expiresIn" is how many seconds the token will last, finally you can use "refreshToken" to generate more tokens in the future.

Try making an invite code and exchanging it for a refresh token yourself with our Swagger page

## Retrieving property data from Beds24

You can get properties from Beds24 using the GET /properties endpoint. This should be a once off operation to construct or map the property on your side.

Try GETing properties with this request:

```bash
curl -X 'GET' \
  'https://beds24.com/api/v2/properties' \
  -H 'accept: application/json' \
  -H 'token: 123...'
```

If successful, you should be able to see information about your properties in the JSON response.

In Swagger, try clicking the "schema" under POST /properties to see all the fields you can set using this endpoint.

By default not all property data will be returned, to include additional information in the response you can include parameters. For example, to include all of a property's room data in the response, add the parameter `?includeAllRooms=true` to the request URL:

```bash
curl -X 'GET' \
  'https://beds24.com/api/v2/properties?includeAllRooms=true' \
  -H 'accept: application/json' \
  -H 'token: 123...'
```

## Price and availability

### Calendar

`GET /inventory/rooms/calendar` can be used to retrieve per day price and availability information. Use this to retrieve room availability and pricing detail in bulk, for example one year at a time and cache it on your side. Generally performing this call about once per 6 hours is enough to keep the data in sync.

### Availability

`GET /inventory/rooms/availability` can be used to retrieve the availability status of dates. Use this to retrieve room availability, this can be used after a guest has selected dates and started a booking to check the dates are still available.

### Offers

`GET /inventory/rooms/offers` can be used to retrieve offers based on specified criteria. Use this to check availability for a specific condition when check-in and out dates and occupancy is known, for example in real time just before accepting a booking. This can be used to confirm all details and price for the booking are still current.

## Bookings

`POST /bookings` can be used to send bookings to Beds24. This will close availability for those dates at all other booking sources.
