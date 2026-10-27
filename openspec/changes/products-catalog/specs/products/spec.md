# products Specification

**Change**: `products-catalog` (new capability)
**Test group**: `tests` (PHPUnit, database `snak_test`)

## Purpose

Staff-only catalog of physical products: record, integer money, CRUD by deactivation, photo handling, DataTables JSON listing, and the separation between manual pause (`is_active`) and availability (`stock > 0`).

## Requirements

### Requirement: Integer Money

Prices MUST be stored, validated, and computed as integer centavos (BOB). Product money code MUST NOT use floats, `floatval`, or float casts. Display formatting MUST derive from the integer and show two decimals.

#### Scenario: Centavos round-trip

- GIVEN `price_cents = 1250`
- WHEN the product is created and read back
- THEN `price_cents` is the integer `1250`
- AND the display value is `12.50`

#### Scenario: Small amounts format correctly

- GIVEN cents `100` and `5`
- WHEN each is formatted for display
- THEN the results are `1.00` and `0.05`

### Requirement: Create Validation

`POST /products` MUST require a trimmed name of 1–120 characters, an integer `price_cents > 0`, and an optional integer `stock >= 0` defaulting to `0`; `description` is optional and at most 500 characters. Any failure MUST be reported in Spanish through `Products.*` language keys and MUST NOT persist a product.

#### Scenario: Valid create

- GIVEN name `Agua 500ml`, price `350`, no stock
- WHEN a cashier submits the form
- THEN a product is stored with `stock = 0` and `is_active = 1`

#### Scenario: Name required

- GIVEN a blank or whitespace-only name
- WHEN the form is submitted
- THEN validation fails with the Spanish required-name message
- AND no row is written

#### Scenario: Name length boundary

- GIVEN names of 120 and 121 characters
- WHEN each is submitted
- THEN 120 is accepted and 121 is rejected with the Spanish length message

#### Scenario: Price rejected

- GIVEN `price_cents` of `0`, `-1`, `12.5`, or `"12,50"`
- WHEN the form is submitted
- THEN each is rejected with the Spanish price message
- AND no row is written

#### Scenario: Stock rejected

- GIVEN stock of `-1`, `2.5`, or `"3 unidades"`
- WHEN the form is submitted
- THEN each is rejected with the Spanish stock message
- AND no row is written

### Requirement: Update Validation

`POST /products/{id}` MUST apply the same field rules as create. A failed update MUST leave every stored column unchanged. Stock MUST NOT be editable through update: a submitted value different from the stored stock MUST be rejected with a message pointing to the audited increase flow, while an unchanged value is accepted.

#### Scenario: Valid update

- GIVEN an existing product
- WHEN name, price, and description are updated
- THEN the new values are stored
- AND `stock` and `is_active` are unchanged

#### Scenario: Invalid update is atomic

- GIVEN an existing product
- WHEN any submitted field is invalid
- THEN validation fails with Spanish messages
- AND every stored column keeps its previous value

#### Scenario: Direct stock edit rejected

- GIVEN a product with `stock = 5`
- WHEN update submits `stock = 9`
- THEN it is rejected with the Spanish audited-increase message
- AND `stock` remains `5`

### Requirement: Name Uniqueness

Product names MUST be unique case-insensitively after trimming and collapsing internal whitespace, including against deactivated products. On update, the product's own name MUST be ignored; a case-only change is allowed. A duplicate MUST be rejected in Spanish and write nothing.

#### Scenario: Duplicate on create

- GIVEN an existing product named `Galleta`
- WHEN ` galleta ` is submitted
- THEN it is rejected with the Spanish duplicate-name message
- AND only one row exists

#### Scenario: Own name on update

- GIVEN a product named `Galleta`
- WHEN it is updated to `GALLETA`
- THEN the update succeeds with the new casing

#### Scenario: Deactivated name reserved

- GIVEN a deactivated product named `Galleta`
- WHEN a new `Galleta` is submitted
- THEN it is rejected as a duplicate

### Requirement: Deactivation Instead of Deletion

There MUST be no hard delete. `POST /products/{id}/deactivate` MUST set `is_active = 0` and `POST /products/{id}/activate` MUST set it back to `1`. Both MUST keep the product row and its stock movements, MUST be idempotent, and MUST NOT create a stock movement.

#### Scenario: Deactivate preserves history

- GIVEN an active product with stock movements
- WHEN a cashier deactivates it
- THEN `is_active` is `0`
- AND the product and all movements still exist

#### Scenario: Reactivation

- GIVEN an inactive product
- WHEN a cashier activates it
- THEN `is_active` is `1` and no movement is added

#### Scenario: No delete route

- GIVEN any product
- WHEN `POST /products/{id}/delete` is requested
- THEN the response is 404
- AND no row or movement is removed

#### Scenario: Repeated deactivation

- GIVEN an already inactive product
- WHEN deactivation is requested again
- THEN it succeeds without error
- AND `is_active` stays `0` and no movement is added

### Requirement: Staff-Only Access

Every products route MUST pass the `staff` filter admitting `cashier` and `admin`. Anonymous requests MUST redirect to `/login` with the intended URL stored and MUST return no catalog markup. State-changing requests MUST be CSRF-protected.

#### Scenario: Anonymous redirected

- GIVEN no session
- WHEN `GET /products` or `POST /products` is requested
- THEN the response redirects to `/login`
- AND the body contains no product data

#### Scenario: Cashier and admin allowed

- GIVEN an authenticated cashier, then an authenticated admin
- WHEN `GET /products` is requested
- THEN both receive 200 with the catalog page

#### Scenario: Missing CSRF rejected

- GIVEN an authenticated cashier
- WHEN `POST /products` carries no CSRF token
- THEN the request is rejected with a non-2xx response
- AND no product is written

### Requirement: DataTables JSON Listing

`GET /products/data` MUST return `200 application/json` with a `data` array. Each row MUST expose `id` (int), `name` (string), `price_cents` (int), `price_display` (string), `stock` (int), `is_active` (bool), `available` (bool), `low_stock` (bool), and `photo_url` (string or null). No credential or user field MAY appear.

#### Scenario: Payload shape

- GIVEN one active product with stock and one inactive product
- WHEN the JSON endpoint is requested by a cashier
- THEN both rows appear under `data` with exactly the documented keys and types

#### Scenario: Empty catalog

- GIVEN no products
- WHEN the JSON endpoint is requested
- THEN it returns 200 with `{"data":[]}`

### Requirement: Photo Upload Acceptance

Uploads MUST accept JPEG, PNG, and WebP up to and including 2 MB, verified server-side from file content. The stored file MUST use a random name plus the canonical extension inside `writable/uploads/products`, and `image_path` MUST record the stored reference.

#### Scenario: JPEG within limit

- GIVEN a valid 1 MB JPEG
- WHEN the product is created
- THEN `image_path` is set and the file exists with a random name and `.jpg` extension

#### Scenario: PNG and WebP accepted

- GIVEN valid PNG and WebP files
- WHEN each is uploaded
- THEN both products are stored with the matching canonical extension

#### Scenario: Exact size limit accepted

- GIVEN a valid JPEG of exactly 2 MB
- WHEN it is uploaded
- THEN the upload succeeds

### Requirement: Photo Upload Rejection

An upload MUST be rejected when the detected MIME type is not JPEG/PNG/WebP, when the size exceeds 2 MB, when the filename carries more than one extension, or when the declared extension disagrees with the detected MIME. Rejection MUST abort the whole create or update, write no file, change no row, and report a Spanish `Products.*` error.

#### Scenario: Non-image content

- GIVEN PHP source bytes named `photo.jpg`
- WHEN the form is submitted
- THEN it is rejected with the Spanish invalid-image message
- AND no file is written and no product is created

#### Scenario: Oversized

- GIVEN a valid JPEG of 2 MB + 1 byte
- WHEN it is uploaded
- THEN it is rejected and no file is written

#### Scenario: Double extension

- GIVEN a valid JPEG named `photo.php.jpg`
- WHEN it is uploaded
- THEN it is rejected and no file is written

#### Scenario: Extension mismatch

- GIVEN JPEG bytes declared as `.png`
- WHEN it is uploaded
- THEN it is rejected and no file is written

### Requirement: Photo Serving and writable/ Isolation

The only HTTP access to a product photo MUST be `GET /products/{id}/photo`, which MUST return the stored bytes with the correct image content type. It MUST be public read (change 5 needs it), return 404 for unknown products, missing photos, or non-numeric ids, and MUST NOT expose filesystem paths. Anything under `writable/` MUST NOT be reachable over HTTP.

#### Scenario: Photo served

- GIVEN a product with a stored JPEG
- WHEN `/products/{id}/photo` is requested without a session
- THEN it returns 200, `Content-Type: image/jpeg`, and the exact stored bytes

#### Scenario: Missing or unknown

- GIVEN a product without a photo, an unknown id, and a non-numeric id
- WHEN each photo route is requested
- THEN each returns 404

#### Scenario: writable/ is unreachable

- GIVEN a stored photo filename
- WHEN `/writable/uploads/products/{filename}` is requested
- THEN it returns 404
- AND no response contains `writable/` or an absolute path

### Requirement: Spanish Copy via Language Keys

Every product label, heading, button, and message MUST come from `app/Language/es/Products.php` through `lang('Products.*')`. Views and controllers MUST NOT contain user-facing Spanish literals.

#### Scenario: Keys resolve

- WHEN the test scans `lang('Products.*')` references in products code
- THEN every referenced key exists, is a string, and is non-empty

#### Scenario: Rendered copy comes from keys

- GIVEN the language file values
- WHEN the catalog page and its validation errors render
- THEN each visible string equals its `Products.*` value

### Requirement: is_active versus Availability

`is_active` is a manual staff pause and MUST only change through the explicit activate and deactivate actions. Availability MUST be derived as `is_active AND stock > 0` and MUST NOT be stored as a column. Activating through stock changes MUST NOT happen: increasing stock on an inactive product MUST leave it inactive.

#### Scenario: Paused with stock

- GIVEN an inactive product with `stock = 10`
- WHEN it is read or listed
- THEN `is_active` is false and `available` is false

#### Scenario: Active but empty

- GIVEN an active product with `stock = 0`
- WHEN it is listed
- THEN `available` is false

#### Scenario: Sellable

- GIVEN an active product with `stock = 7`
- WHEN it is listed
- THEN `available` is true

#### Scenario: Stock never activates

- GIVEN an inactive product
- WHEN its stock is increased
- THEN `is_active` remains false

## Non-Functional Requirements

- Money MUST remain integer centavos with no float math, per the proposal's locked decision.
- New `app/` code MUST pass the project PSR-12 check and follow CI4 conventions.
- No credential, key, or `.env` value MAY appear in code, views, language files, or test fixtures.
- Every scenario above MUST have a PHPUnit test in the `tests` group against `snak_test`; tests MUST NOT touch `snak`.
- Uploads MUST live only under `writable/uploads/products`, outside the web root.
