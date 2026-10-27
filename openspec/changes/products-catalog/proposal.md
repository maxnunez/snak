# Proposal: Products Catalog and Stock Control

**Change**: `products-catalog` (change 3 of 8). Depends on `auth-roles` (archived 2026-10-21).

## Intent

The counter sells physical snacks but records nothing: no photo, price or unit count. Cashiers need a staff-only catalog and every stock change must be traceable, or changes 5 (reservations) and 7 (POS) have no inventory to decrement.

## Scope

### In Scope

- `products` + `stock_movements` migrations, models, services.
- Staff CRUD (cashier + admin), client-side DataTables over a JSON endpoint.
- Photo upload to `writable/uploads/products`, served through a controller route.
- Explicit stock increase with reason, actor, and audit row.
- Sellable query (`is_active` AND `stock > 0`), low-stock flag, cashier list.

### Out of Scope (non-goals)

Reservations, POS, payments, reports, categories, barcodes/SKU, suppliers, bulk import, stock-decrement UI (change 5), editing audit rows, thumbnails, multi-warehouse.

## Decisions (locked, not reopened)

| Area | Decision |
|---|---|
| Schema | `products(id, name, description?, price_cents, stock, image_path?, is_active, timestamps)`, index `is_active`; `stock_movements(id, product_id, delta, reason, user_id, created_at)` |
| Money | Integer centavos (BOB), never float; 2 decimals at display only |
| Photo | jpg/png/webp ≤2 MB, server-side MIME check; `/products/{id}/photo`; `writable/` never exposed |
| Access / list | Authenticated `cashier` + `admin`; DataTables **client-side** (small catalog) fed by JSON |
| Stock | Increase is explicit: one audit row with reason + user; threshold **5** as one constant; `stock = 0` is not sellable |
| State | `is_active` (manual pause) vs availability (`stock > 0`): separate, documented, never merged |

## Capabilities

**New**: `products` (record, money, CRUD, photo, listing, active/available semantics); `stock-control` (audited increases, non-negative stock, threshold, sellable filter).
**Modified**: none.

## Approach

Migration → `ProductModel`/`StockMovementModel` → `ProductService` (validation, photo, activation) + `StockService` (increase + audit in one transaction) → `ProductController` under `staff:cashier`, plus views. Availability is a query scope, never a stored column.

## Requirement → Verification

| Requirement | Verified by |
|---|---|
| Product validation (name, price ≥0, stock ≥0) | DB/unit boundary tests |
| Money integer centavos, no float casts | Unit test on casts + display format |
| Crud staff-only (anonymous 302, wrong role 403) | Feature tests |
| Photo MIME/size/extension checked; bad upload writes nothing | Feature test with fixtures |
| Photo via controller; direct `writable/` path 404 | Feature tests |
| JSON endpoint shape for DataTables | Feature test on payload |
| Increase writes audit row (reason, user); atomic | DB test incl. rollback |
| Invalid increase (0/negative, no reason) rejected | DB test |
| Threshold 5 boundaries (6/5/4) | Unit test |
| `stock = 0` and inactive excluded from sellable query | DB test on scope |
| Spanish copy only from Language files | Language-key scan test |

## Affected Areas

Migrations (`products`, `stock_movements`); `app/Models/{Product,StockMovement}Model.php`; `app/Services/{Product,Stock}Service.php`; `app/Controllers/ProductController.php`; `app/Config/Routes.php`; `app/Views/products/*`; `app/Views/cashier/index.php`; `app/Language/es/Products.php`; `tests/{database,feature,unit}/Product*Test.php`.

## Review Budget Forecast

| Slice | Content | Forecast |
|---|---|---|
| 1 | Schema, models, constants, sellable scope, tests | ~280 |
| 2 | Service + CRUD + views + JSON, tests | ~390 |
| 3 | Photo upload/serve + stock increase + low-stock UI, tests | ~300 |

~970 lines ⇒ 3 stacked PRs. Slice 2 is the overflow risk; if it exceeds 400, split at the photo seam and move serving with it.

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Slice 2 >400 lines | High | Measure before apply; split photo seam |
| Upload traversal / executable file | Med | Random filename, extension allowlist, MIME check, no direct serving |
| Availability duplicated in views and queries | Med | One scope + one constant; tests cover both states |

Open: photo route visibility — default public read, since change 5's student catalog needs it.

## Rollback Plan

Revert the three commits, then `php spark migrate:rollback` (drops `stock_movements`, then `products`); delete `writable/uploads/products` manually. No production data exists.

## Dependencies

`auth-roles` session + `staff` filter; `users.id` for `stock_movements.user_id`; base layout already loads DataTables/SweetAlert2; writable upload directory.

## Success Criteria

- [ ] Cashier creates, edits, pauses and lists products with photo, price and stock.
- [ ] Every increase leaves exactly one audit row with reason and user.
- [ ] Zero-stock products never reach the sellable query or sell list.
- [ ] Low-stock indicator at ≤4, absent at 5.
- [ ] Photos render through the controller; `writable/` unreachable over HTTP.
- [ ] `vendor/bin/phpunit` passes; each PR ≤400 changed lines.
