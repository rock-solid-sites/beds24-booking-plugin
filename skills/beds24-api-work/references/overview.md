# Category: API V2

**Source:** https://wiki.beds24.com/index.php/Category:API_V2
**Captured:** 2026-05-07

---

This page explains the capabilities of API V2 and explains how to use it.

## Contents

1. Capabilities
2. Authentication
   - 2.1 Invite codes and refresh tokens
3. Invite codes
   - 3.1 Create a code from a link given to you by a third party
   - 3.2 Create a code to give to a third party
   - 3.3 Create a link to give to users (as a third party)
4. Scopes
   - 4.1 bookings
   - 4.2 bookings-personal
   - 4.3 bookings-financial
   - 4.4 inventory
   - 4.5 properties
   - 4.6 accounts
   - 4.7 Linked Properties
   - 4.8 Subcategory scopes
5. POST requests
   - 5.1 Creating/modifying multiple items
   - 5.2 Subitems
   - 5.3 Responses
6. Endpoints
   - 6.1 Bookings
   - 6.2 Inventory
   - 6.3 Properties
   - 6.4 Accounts
   - 6.5 Webhooks
   - 6.6 Channels - settings
   - 6.7 Channels - Airbnb
   - 6.8 Channels - Booking.com
   - 6.9 Channels - Stripe
7. Webhooks
8. Best Practices
9. Examples
10. Changelog
11. FAQ

## Capabilities

API V2 can allow third parties to access your account, properties, bookings and inventory.

More information can be found here beds24.com/api/v2

API V1 is depreciated, we do not recommend using it for new projects. Information about API V1 can be found here https://wiki.beds24.com/index.php/Category:API

## Authentication

There are two kinds of tokens, long life tokens which have read only access, and refresh tokens which generate tokens that can read and make changes.

To use most API endpoints you will need to include a token header in the format `"token: {token}"`

### Invite codes and refresh tokens

**Step 1: Get an invite code or long life token**

Invite codes and long life tokens can be generated here: Invite Codes

For more information about invite codes, see here.

This step is the only one that must be done manually, all other steps can be performed and automated programmatically.

**Step 2: If using an invite code, get a refresh token**

Skip this step if using a long life token.

You can use the invite code generated in step one with `GET /authentication/setup`.

This will return a token and a refresh token.

Tokens generated from refresh tokens expire after 24 hours.

**Step 3: Use the token to authenticate calls**

The token (either a long life token or a token generated using a refresh token) can be included as a header to authenticate calls to other API endpoints.

**Step 4: Use the refresh token to generate new tokens**

As tokens expire after 24 hours, you will need to use the refresh token with `GET /authentication/token` to get new tokens when old ones expire.

Refresh tokens do not expire so long as they have been used within the past 30 days.

## Invite codes

To grant a third party access to your account, you will need to provide them with an invite code.

### Create a code from a link given to you by a third party

If you are given such a link, most fields will be filled out for you.

You simply need to decide if the third party should be able to access your linked properties.

Click the Generate invite code button and give the code to the third party.

### Create a code to give to a third party

**Step 1: Select what the third party can access**

Click the generate invite code button and select the scopes of the invite code. Scopes determine what the third party will be able to access, and what they can do with it. See the scopes section for more information.

**Step 2: Linked properties**

Decide if you want the third party to be able to access your linked properties, or just the properties in your account.

**Step 3: IP Whitelisting**

If you add an IP address here, only that IP address will be able to access your account using the invite code.

Multiple IP addresses can be added if separated by commas, e.g.
```
192.168.0.1, 127.0.0.1, 2001:0db8:85a3:0000:0000:8a2e:0370:7334
```

**Step 4: Create the invite code**

Click the Generate invite code button. You will now see the code listed in the table. Copy this code and give it to the third party.

### Create a link to give to users (as a third party)

If you are a third party who requires invite codes from users, you can give users a link that will prefill the form with the scopes you require selected.

To do this, select the scopes you require then click the button in the bottom left corner to see your customized link.

You can also prefill a whitelisted IP address in the same way.

## Scopes

Each category of API endpoint (except /authentication) requires a corresponding scope to access.

### bookings

The bookings scope provides access to basic information for:
- `GET /bookings`
- `POST /bookings`

### bookings-personal

The bookings-personal scope provides access to personal information (in addition to the basic information granted by the bookings scope) for:
- `GET /bookings`
- `POST /bookings`
- `GET /bookings/messages`
- `POST /bookings/messages`
- `PATCH /bookings/messages`

### bookings-financial

The bookings-financial scope provides access to financial information (in addition to the basic information granted by the bookings scope) for:
- `GET /bookings`
- `POST /bookings`

### inventory

The inventory scope provides access to:
- `GET /inventory/offers`
- `GET /inventory/availability`
- `GET /inventory/calendar`
- `POST /inventory/calendar`

### properties

The properties scope provides access to:
- `GET /properties`
- `POST /properties`

### accounts

The account scope provides access to:
- `GET /accounts`
- `POST /accounts`

### Linked Properties

Tokens do not provide access to linked properties and their bookings by default.

If you wish to access properties that are not in your account you must tick the "Allow linked properties" checkbox when selecting the scopes for the token.

Note: Properties must be linked under Account Management > Manage Account > Manage Property. Other methods of linking properties are not supported in API V2.

### Subcategory scopes

Some categories have additional scopes that allow access to personal or financial information. For example, the "bookings" scope would grant access to a booking's basic information such as the check-in and checkout dates. To access personal information such as the name of a guest, the "bookings-personal" scope would be required. Similarly, to access the invoice of a bookings, the "bookings-financial" scope would be required.

Each scope must also have an accompanying method. For example `"read:bookings"` would grant read access to bookings, but in order to create a new booking `"write:bookings"` would be required.

The "all" method may be used as a shortcut to grant access to all methods. For example `"all:bookings"` would allow for the reading, updating, creating and deleting of bookings.

## POST requests

### Creating/modifying multiple items

All POST endpoints accept an array of items (an item may be a booking, message, property etc).

It is possible to create and modify multiple different items in one request this way.

All existing "POSTable" items will have an "id" field to uniquely identify it.

- To create a new item, just do not include an id.
- To modify an existing item, include its id.

### Subitems

Some items can contain subitems. For example, a booking may contain an "invoice item" and an "info item".

Subitems generally work in the same way as their parent items.

Deleting a subitem requires the WRITE scope method, deleting a subitem's parent item requires the DELETE scope method.

To add a new subitem to an item, include the subitem without an id.

Example: add a new info item to a booking:
```json
[
  {
    "id": "{bookingId}",
    "infoItems": [
      {
        "code": "this will create",
        "text": "a new info item"
      }
    ]
  }
]
```

To update an existing subitem, include the subitem's id.

Example: update a booking's info item:
```json
[
  {
    "id": "{bookingId}",
    "infoItems": [
      {
        "id": "{infoItemId}",
        "text": "this info item will have its text changed"
      }
    ]
  }
]
```

To delete a subitem, include only the subitem's id.

Example: delete a booking's info item:
```json
[
  {
    "id": "{bookingId}",
    "infoItems": [
      {
        "id": "{infoItemId}"
      }
    ]
  }
]
```

### Responses

All POST requests will have a standard response.

#### Structure

Responses will be an array containing a number of response items equal to the number of items in the request.

Each response item will contain a "success" boolean field. Success will be false if there were any errors in processing the item. Success being false does not necessarily mean that nothing has changed.

E.g. if a valid booking with an invalid info item is posted, the booking will be created but the info item will not. Success will be false in this case because there was an error.

A response item may also contain one or more of the following:

- **New** - contains information about newly created items and subitems.
- **Modified** - contains information about modified items and subitems.
- **Errors** - Contains information about fatal issues with an item or subitem in the request.
- **Warnings** - Contains information about non-fatal issues with an item in the request.
- **Info** - Contains general information about what has happened to the request.

#### Order of response items

The order of the items in the response will correspond to the order that items were sent in the request.

Example: POST two messages for different bookings:
```json
[
  {
    "bookingId": 1111111,
    "message": "a message"
  },
  {
    "bookingId": 2222222,
    "message": "a different message"
  }
]
```

Example: response order for the above request:
```json
[
  {
    "success": true,
    "info": [{"message": "information about the message for booking 1111111"}]
  },
  {
    "success": false,
    "errors": [{"message": "an error about the message for booking 2222222"}]
  }
]
```

## Endpoints

### Bookings

#### GET /bookings

Get bookings matching specified criteria.

#### POST /bookings

Create or update bookings.

##### How to Add/remove bookings from a group booking

You can add or remove a booking from a group booking by using the parameter "masterId" without having to use the action "makeGroup"

- To add a booking to a group booking, set `"masterId": 1234567`
- To remove a booking from a group booking, set `"masterId": null`

##### Using the stripeToken Field to Enable Stripe Payment Actions

For direct bookings where payment is processed via Stripe, the stripeToken field must be set. This token is typically generated when collecting card details securely through Stripe, ensuring that sensitive information is not handled or stored by Beds24.

#### DELETE /bookings

Delete bookings by id.

#### GET /bookings/messages

Get messages for a booking.

#### POST /bookings/messages

Send messages or mark them as read. This endpoint only works for messages for an OTA booking, not bookings made directly from the booking page.

#### PATCH /bookings/messages

Make changes in all messages in a selection.

#### GET /bookings/invoices

Get invoices for bookings.

### Inventory

These endpoints will only work if a price is set for the property or room. To set up a Daily Price, follow the instructions here: Daily Prices Guide. For Fixed Prices, you can find the setup guide here: Fixed Prices Guide.

#### GET /inventory/rooms/offers

Get offer based on specified criteria.

#### GET /inventory/rooms/availability

Get the availability status of dates.

#### GET /inventory/rooms/calendar

Gets per day values from the calendar.

#### POST /inventory/rooms/calendar

Modify per day calendar values.

### Properties

#### GET /properties

Get properties matching specified criteria.

##### Prices

To get price setup rules, include the `"includePriceRules"` parameter in `GET /properties` like this:

```
/api/v2/properties?includePriceRules=true
```

A room can have up to 16 prices.

In the control panel, these can be set under Prices -> Daily Price Rules.

In the API these can be accessed through `GET` and `POST /inventory/calendar`:

```json
{
  "data": [
    {
      "calendar": [
        {
          "price1": 100,
          "price2": 300,
          "price3": 200
        }
      ]
    }
  ]
}
```

#### POST /properties

Create or modify properties.

Note: To update room-level settings, you must include the property id in your request. If it is missing, the system may return an error or incorrectly report success without applying any changes.

##### Price rules

It's currently not possible to create new price rules, however, you can modify the price rules with this request.

Example: Modify the name of the price rule:
```json
{
  "id": "propertyid",
  "roomTypes": [
    {
      "id": "roomid",
      "priceRules": [
        {
          "priceruleid": 1,
          "name": "-INSERT NAME HERE-"
        }
      ]
    }
  ]
}
```

#### DELETE /properties

Delete properties by id.

#### DELETE /properties/rooms

Delete rooms of properties by id.

### Accounts

#### GET /accounts

Get accounts and sub-accounts.

#### POST /accounts

Create or modify accounts.

### Webhooks

#### POST Webhooks - bookings

The webhook payload sent to your URL from the booking webhook found here: Settings -> Properties -> Access -> Booking Webhook

### Channels - settings

#### GET /channels/settings

Get channel specific settings.

#### POST /channels/settings

Modify channel settings.

### Channels - Airbnb

#### GET /channels/airbnb/users

Get all Airbnb user ids connected to an account.

#### GET /channels/airbnb/listings

Get all Airbnb listings for a specified Airbnb user id.

#### POST /channels/airbnb

Perform actions at Airbnb.

### Channels - Booking.com

#### POST /channels/booking

Perform actions at Booking.com.

#### GET /channels/booking/reviews

Get reviews from Booking.com.

### Channels - Stripe

You can collect the card directly with Stripe so you do not have any PCI DSS obligations.

The procedure:

1. Make the booking via API and get the new Booking ID.
2. Make a call to API V2 `POST /channels/stripe` with the booking ID and any charges you want to collect.
3. `POST /channels/stripe` will return the session data required to instantly create a Stripe payment checkout.
4. After the booker enters their card, it is automatically connected to the booking ID and can be charged by API or manually from the control panel.

When initializing Stripe in your App, use this pk_live key and the stripe_account value (acct_) from the session response:
```js
var stripe = Stripe('pk_live_zWSW2ykzZoq4mYcKg9c8jmHS', { stripeAccount: 'acct_stripe_acccont-value-from-response' });
```

#### POST /channels/stripe

Perform actions at Stripe.

#### GET /channels/stripe/paymentMethods

Get payment methods for a booking from Stripe.

#### GET /channels/stripe/charges

Get charges for a booking from Stripe.

## Webhooks

### Booking webhooks

To access booking webhooks for API V2 please contact support. They can then be enabled under Settings > Properties > Access > Booking webhooks.

### Other webhooks

Information about other webhooks (including non API V2 webhooks) can be found here: Webhooks

## Best Practices

### Token Management

Tokens last 24 hours. This means that you do not need to retrieve a new token for each request. Getting a new token costs credits so it is best to use an existing one when possible.

### Retrieving information at high frequencies

If you need to get data such as new messages or bookings when they come in you do not need to perform frequent GET requests. Instead, you can use webhooks to be notified as soon as a new message/booking etc arrives.

### Sending information at high frequencies

If you need to send large amounts of information such as messages at high frequencies it is best to send them grouped in bulk POST requests instead of sending one request per message. For example, you could send one POST request every 30 seconds containing all messages sent in the past 30 seconds.

### Sending or getting information in one call

If you need to retrieve information about multiple different things, such as information about several properties, you can retrieve that in one call by specifying multiple IDs in the one request instead of performing one GET request per property.

## Examples

### Authentication

#### Refreshing a token

Request:
```bash
curl -X 'GET' \
  'https://beds24.com/api/v2/authentication/token' \
  -H 'accept: application/json' \
  -H 'refreshToken: Ea6DftE50aYYRe/qAd9SkQaSmTF6kaLQxH6gtRxO1h10yVC64d4qIj4BGiQOU+y5'
```

Response:
```json
{
  "token": "wEoJHQIwRrLwHqTqAsn0/XjzaZkVk4E8sSDwbRN2HKDlulkt6n7aHQCcvdqfX+y5",
  "expiresIn": 3600
}
```

## Changelog

The API V2 changelog is available here.

## FAQ

### How do I access API V2?

Create an invite code under (SETTINGS) MARKETPLACE > API.

Exchange this invite code for a refresh token and token using the `GET /authentication/setup` endpoint.

Include the token in your requests to authenticate them.

You can try this out using our interactive UI here beds24.com/api/v2.

### What are scopes?

Scopes limit what your token can do.

For example, the `read/bookings` scope allows your token to retrieve bookings via the API, but if your token does not have the `write/bookings` scope then it cannot be used to create or modify bookings.

### Where are scopes set?

Scopes are set when you create an invite code.

Scopes cannot be changed later, you must create a new invite code with the scopes you want and exchange it for a new token.

### How long do invite codes/tokens last?

- Invite codes expire after 24 hours.
- Refresh tokens last forever so long as they are being used. Unused refresh tokens expire after 30 days.
- Long life tokens last forever so long as they are being used. Unused long life tokens expire after 90 days.
- Tokens generated from refresh tokens expire after 24 hours.

### How big are tokens?

Tokens will be between 152 and 172 characters long.

### What is the API credit limit?

The API credit limit restricts how much you can use the API in a 5 minute window, it's by default 100 credits per 5 minutes.

Each API request has a cost, this cost is calculated dynamically and depends on how complex the request is.

If you go over this limit you will not be able to make additional API calls until the 5 minute period is over.

You can increase this limit by contacting the support team through our ticketing system and it will cost 10€ per month to get the limit increased to 200 credits per 5 minutes.

### Where can I see my API credit limit?

Information about your credit limit is included in the following API response headers:

- `x-five-min-limit-remaining` - the number of credits you have left for this 5 minute period.
- `x-five-min-limit-resets-in` - the number of seconds until the current period resets.
- `x-request-cost` - the number of credits this request cost.

### Is the API limited per token or per account?

The API credit limits are at the account level.

This means that tokens under the same account share the same credit limit.

Different accounts, including sub accounts, have separate credit limits.

### How do I make a new property/room/booking etc?

Simply do not include an id in your POST request.

### Where can I see or set price rules?

Price rules can be found under `/properties`.

To retrieve them you must set the `includePriceRules` parameter to true.

### Where can I see or set offers?

Offer setup rules can be found under `/properties`. To retrieve them you must set the `includeOffers` parameter to true.

You can retrieve offers (i.e. calculated prices for specific dates) through the `/inventory/rooms/offers` endpoint.

### How can I see or set prices, min stay, availability etc for specific dates like in the UI calendar?

These per date values can be read and set through the `/inventory/rooms/calendar` endpoint.

### Where can I see examples for how to use the API?

Examples can be found here beds24.com/api/v2.

### Can I use API V2 to send pictures or webhooks?

Currently no, however these features are coming soon.

### How can I see if a date is available for check-in/out?

The `GET /inventory/rooms/availability` returns information about if dates are available or not. If a date is false, it means it is not available for check-in. However, if the previous date is available, it means the date is available for check-out.

For example, with the following, a booking cannot check-in on 2024-01-03 because that date is unavailable. However, because the previous date 2024-01-04 is available, it means 2024-01-03 is available for check-out.

```json
"availability": {
  "2024-01-01": true,
  "2024-01-02": true,
  "2024-01-03": false
}
```

### What is the maximum amount of data I can send in a POST request?

The API has a limit of approximately 1MB per POST payload.

In addition, there is a limit of 10000 top level JSON array items per POST request.

### What does "coming soon", "alpha" or "beta" mean?

- **Coming soon** - The endpoint has not been developed yet and is not usable. The schema is indicative of how the endpoint will eventually work but there may be changes in the final version.
- **Alpha** - The endpoint is usable but still being developed, not all features are implemented yet, breaking changes are unlikely but may occur if necessary.
- **Beta** - The endpoint is mostly finished and is being tested, most features are implemented, breaking changes are not planned.

## Pages in this category

- API V2.0 apisourceids
- API V2.0 changelog
- Guest Services: How to connect to Beds24 using API V2
- Marketplace for Integration Partners
- OTAs: How to connect to Beds24 using API V2
- PMSs: How to connect to Beds24 and use Airbnb via API V2
- PMSs: How to connect to Beds24 and use Booking.com via API V2
