# Guest Services: How to connect to Beds24 using API V2

**Source:** https://wiki.beds24.com/index.php/Guest_Services:_How_to_connect_to_Beds24_using_API_V2
**Captured:** 2026-05-07

---

This page explains how guest services can develop API connectivity with Beds24

## Use case

You are a Guest Service, such as a lock system, concierge, cleaner management or similar and want to retrieve booking information from Beds24 to use in your system.

Your developer develops the integration between your system and Beds24.

## The Basics

It is a good idea to get a basic understanding of how Beds24 works.

We recommend going through this introduction and creating a test property with prices and some bookings, along with any other data you intend to use.

## The API

Beds24 has two API versions, V1 and V2, we recommend using V2 for any new projects.

Documentation for API V2 is available here.

There is also interactive Swagger documentation. We recommend using Swagger to try the examples in this guide.

## API V2 Authentication

API V2 uses refresh tokens which generate access tokens, these tokens will be included as headers in your requests in this format: `"token: {token}"`

To obtain a refresh token, first go the API page in the control panel and click the generate invite code button. Select the scopes you require then click the generate invite code button. You will now see an invite code on this page, you can copy this to your clipboard and use the `GET /authentication/setup` endpoint to exchange your invite code for a refresh token.

**IMPORTANT: This is GET, not POST. The invite code is sent as a request header (`code:`), not in the request body.**

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

Try making an invite code and exchanging it for a refresh token yourself with our Swagger page.

## Retrieving booking data from Beds24

Booking data can be retrieved using the `GET /bookings` endpoint.

Try making a booking (either using the control panel or the API), taking note of the booking's ID, then GETing it using the API:

```
https://beds24.com/api/v2/bookings?id=1234567
```

If successful, you should be able to see information about your booking in the JSON response.

In Swagger, try clicking the "schema" under GET /bookings to see all the fields you can get using this endpoint.

By default not all booking data will be returned, to include additional information in the response you can include parameters. For example, to include all of a booking's invoice data in the response, add the parameter `?includeInvoiceItems=true` to the request URL:

```
https://beds24.com/api/v2/bookings?id=1234567&includeInvoiceItems=true
```

There are a lot of ways you can filter the bookings you wish to retrieve, for example, you can use the parameter `?status=new` to only get new bookings, or the parameter `?filter=arrivals` to get bookings arriving today. Be sure to check the GET /bookings section of Swagger to see a full list of these parameters.
