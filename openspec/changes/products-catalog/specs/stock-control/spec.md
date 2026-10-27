# stock-control Specification

**Change**: `products-catalog` (new capability)
**Test group**: `tests` (PHPUnit, database `snak_test`)

## Purpose

Auditable stock for catalog products: every increase is one atomic transaction with exactly one movement row, stock can never go negative, and sellability plus low-stock signalling derive from one shared threshold.

## Requirements

### Requirement: Audited Increase

`POST /products/{id}/stock` MUST increase stock by a positive integer delta, record the trimmed reason and the authenticated actor, and write exactly one `stock_movements` row (`product_id`, `delta`, `reason`, `user_id`, `created_at`). The stock delta, the product update, and the movement MUST commit in a single transaction: if any step fails, neither the stock nor the movement MAY persist. A positive opening stock at create MUST also write exactly one movement.

#### Scenario: Increase writes one row

- GIVEN a product with `stock = 3`
- WHEN a cashier increases it by `4` with a reason
- THEN `stock` is `7`
- AND exactly one new movement exists with `delta = 4`, that reason, and the cashier's `user_id`

#### Scenario: Opening stock is audited

- GIVEN a create with `stock = 10` by an authenticated cashier
- WHEN the product is stored
- THEN `stock` is `10`
- AND exactly one movement of `delta = 10` records the opening reason and the actor

#### Scenario: Failure rolls back both

- GIVEN a forced failure while writing the movement
- WHEN the increase is attempted
- THEN the previous `stock` is unchanged
- AND no movement row remains

### Requirement: Invalid Increase Rejected

The delta MUST be an integer of at least `1`. Zero, negative, non-integer, and missing deltas MUST be rejected. The reason MUST be non-empty after trimming and at most 255 characters. Rejection MUST leave stock unchanged and write zero movement rows.

#### Scenario: Non-positive delta

- GIVEN deltas of `0` and `-5`
- WHEN each is submitted
- THEN each is rejected with the Spanish delta message
- AND stock is unchanged and no movement row is written

#### Scenario: Non-integer delta

- GIVEN deltas of `2.5` and `"cuatro"`
- WHEN each is submitted
- THEN each is rejected and no movement row is written

#### Scenario: Missing reason

- GIVEN a valid delta with a blank or 256-character reason
- WHEN it is submitted
- THEN it is rejected with the Spanish reason message
- AND stock is unchanged

### Requirement: Non-Negative Stock Invariant

Stock MUST never be below zero. Any operation whose result would be negative MUST be rejected atomically, leaving stock and movements untouched. Reaching exactly zero MUST be allowed.

#### Scenario: Below zero rejected

- GIVEN a product with `stock = 2`
- WHEN a change of `-3` is attempted
- THEN it is rejected
- AND `stock` remains `2` with no new movement

#### Scenario: Zero is accepted

- GIVEN the stock guard is evaluated for a result of `0` and of `-1`
- WHEN each result is checked
- THEN `0` is accepted and `-1` is rejected

### Requirement: Low-Stock Threshold

A single constant MUST define the threshold as `5`, with no duplicated literal in views, queries, or controllers. Low-stock MUST mean stock strictly below the threshold, so `6` and `5` are not low and `4` is low; a product at `stock = 0` is also low.

#### Scenario: Six is not low

- GIVEN `stock = 6`
- WHEN the listing row is produced
- THEN `low_stock` is false

#### Scenario: Five is not low

- GIVEN `stock = 5`
- WHEN the listing row is produced
- THEN `low_stock` is false

#### Scenario: Four is low

- GIVEN `stock = 4`
- WHEN the listing row is produced
- THEN `low_stock` is true

#### Scenario: Empty is low

- GIVEN `stock = 0`
- WHEN the listing row is produced
- THEN `low_stock` is true

### Requirement: Sellable Query

A single query scope MUST define sellable products as `is_active = 1 AND stock > 0`. Inactive products with stock and active products without stock MUST both be excluded; products satisfying both conditions MUST be included. The same scope MUST back any sell list.

#### Scenario: Excluded states

- GIVEN an inactive product with `stock = 10` and an active product with `stock = 0`
- WHEN the sellable scope is queried
- THEN neither product is returned

#### Scenario: Included state

- GIVEN an active product with `stock = 1`
- WHEN the sellable scope is queried
- THEN the product is returned

#### Scenario: Scope is reused

- WHEN the cashier sell list and the scope are compared
- THEN both use the same definition and return the same product ids

### Requirement: Low-Stock Visibility in Listing

The products JSON listing MUST expose `low_stock` and availability for every row, computed from the shared constant and the sellable definition, consistent across the catalog and cashier views.

#### Scenario: Flags agree with data

- GIVEN products at stock `6`, `4`, and an inactive one with stock `9`
- WHEN the JSON listing is requested
- THEN their `low_stock` values are false, true, and false
- AND `available` is false for the inactive row

## Non-Functional Requirements

- Stock arithmetic MUST use integers; centavos and quantities MUST never be floats.
- Every stock write and its audit row MUST share one database transaction.
- New `app/` code MUST pass the project PSR-12 check and follow CI4 conventions.
- No credential, key, or `.env` value MAY appear in code, views, language files, or fixtures.
- Every scenario above MUST have a PHPUnit test in the `tests` group against `snak_test`; tests MUST NOT touch `snak`.
