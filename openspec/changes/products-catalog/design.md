# Design: Products Catalog and Stock Control

## Technical Approach

One owner per rule: migrations hold what the DB can enforce (unsigned amounts, unique name, FK), `ProductModel` owns constants, scopes and projections, `ProductService`/`StockControlService` own validation and transactions, `ProductsController` owns HTTP only, `ProductImageService` owns the filesystem. Availability is derived, never stored. Follows the `auth-roles` patterns: services over fat models, `ValidationException` error bag, `lang()`-keyed messages only, `staff` filter, `DatabaseTestTrait` + `.env.testing`.

Verified against CI4 4.7.5 source (not assumed): `Validation::getErrorMessage()` runs custom error strings through `lang()`, so `'Products.x'` resolves without a `lang()` call; `BaseConnection::transBegin()` nests by depth only — an inner `transRollback()` is a depth decrement, so the outer caller **must** roll back; `FeatureTestTrait` builds the request per call and `FileCollection` reads `service('superglobals')->getFilesArray()`, so upload fixtures go through `setFilesArray()` and must be cleared between tests; `UploadedFile::isValid()`/`move()` require `is_uploaded_file()`, which is false for test fixtures (see D4).

Deltas from proposal prose, all spec-driven: price is `> 0` (spec scenario rejects `0`), `reason` is free text ≤255 (a whitelist would reject valid reasons the spec allows), controller is `ProductsController` (plural, matches the route prefix), and there is no `StockMovementModel` — the service is the only writer and all three statements must run on one connection to make the row lock mean anything.

## Architecture Decisions

### D1 — Schema

| Column | Type | Notes |
|---|---|---|
| `products.id` | `INT UNSIGNED` AI | PK |
| `name` | `VARCHAR(120) NOT NULL` | unique index; `utf8mb4_unicode_ci` makes it case-insensitive |
| `description` | `VARCHAR(500) NULL` | |
| `price_cents` | `INT UNSIGNED NOT NULL` | DB refuses negatives; validation refuses `0` |
| `stock` | `INT UNSIGNED NOT NULL DEFAULT 0` | DB-level non-negative invariant |
| `image_path` | `VARCHAR(255) NULL` | **filename only**, never a path |
| `is_active` | `TINYINT(1) NOT NULL DEFAULT 1` | manual pause |
| `created_at`/`updated_at` | `DATETIME NULL` | |

Indexes: `addKey('id', true)`, `addUniqueKey('name')`, `addKey('is_active')`.

`stock_movements`: `id BIGINT UNSIGNED AI`, `product_id INT UNSIGNED NOT NULL`, `delta INT NOT NULL` (signed — change 5 writes negatives), `reason VARCHAR(255) NOT NULL`, `user_id INT UNSIGNED NOT NULL`, `created_at DATETIME NOT NULL`. Indexes: PK, `addKey(['product_id', 'created_at'])`, `addKey('user_id')`, `addForeignKey('product_id', 'products', 'id', 'CASCADE', 'RESTRICT')`.

`down()`: `$this->forge->dropTable('stock_movements', true)` then `dropTable('products', true)`; rollback order already satisfies the FK.

**Rejected**: FK on `user_id` → `users.id` (existing auth tests `TRUNCATE users`, which MySQL refuses on any FK parent even with zero children; an actor index gives the same query value, and no user-delete feature exists); `DECIMAL` price (locked integer-centavos decision); `TEXT` description (500-char rule is the spec).

Migrations: `2026-10-21-110000_CreateProducts.php`, `2026-10-21-110100_CreateStockMovements.php`.

### D2 — `ProductModel`

```php
protected $allowedFields = ['name', 'description', 'price_cents', 'image_path'];
protected array $casts   = ['price_cents' => 'integer', 'stock' => 'integer', 'is_active' => 'boolean'];
protected $skipValidation = true;   // same as UserModel: rules live in the service

public const LOW_STOCK_THRESHOLD = 5;   // stock < 5  → 6 and 5 not low, 4 and 0 low
public const AVAILABLE_STOCK_MIN = 1;   // the "stock > 0" half of availability
public const ACTIVE_ON           = 1;

public function sellable(): self   { return $this->where('is_active', self::ACTIVE_ON)->where('stock >=', self::AVAILABLE_STOCK_MIN); }
public function lowStock(): self   { return $this->where('stock <', self::LOW_STOCK_THRESHOLD); }
public static function isAvailable(bool $active, int $stock): bool { return $active && $stock >= self::AVAILABLE_STOCK_MIN; }
public static function isLowStock(int $stock): bool { return $stock < self::LOW_STOCK_THRESHOLD; }
public function nameExists(string $name, ?int $ignoreId = null): bool;  // no is_active filter: deactivated names stay reserved
public function setActive(int $id, bool $active): bool;                 // never writes a movement
public static function listRow(object $p): array;                       // the single JSON projection
```

`stock` and `is_active` are **excluded** from `$allowedFields` (the `UserModel::role` precedent): stock is written only by `StockControlService`, `is_active` only by `setActive()`, so no request array can mass-assign either.

`price_cents` cast to `integer` because MySQLi runs `numberNative=false`; `listRow()` re-casts explicitly anyway.

BOB accessor — integer-only, no `floatval`/division:

```php
public static function priceDisplay(int $cents): string
{
    return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
}
```
`1250 → "12.50"`, `100 → "1.00"`, `5 → "0.05"`.

### D3 — `StockControlService`

```php
final class StockControlService
{
    public const REASON_OPENING = 'opening_stock';   // immutable audit identifier, not locale copy

    public function __construct(private readonly BaseConnection $db) {}

    public function increase(int $productId, int $delta, string $reason, int $userId): void;
    public static function guardResult(int $result): void;   // 0 ok, -1 throws
}
```

Order inside `increase()`: guards **before** the transaction (cheap, zero writes) → `transBegin()` → `SELECT id, stock FROM products WHERE id = ? FOR UPDATE` → `$newStock = (int) $row->stock + $delta` → `guardResult($newStock)` → `UPDATE products SET stock, updated_at` (only those two columns, so `is_active` cannot flip) → one `INSERT INTO stock_movements` → `transCommit()`; any `Throwable` → `transRollback()` + rethrow. All three statements use `$this->db` (not models) so the lock, the update and the audit share one connection.

Guards: `$delta < 1` → `Products.stockDeltaInvalid`; `trim($reason) === ''` → `Products.stockReasonRequired`; `mb_strlen(trim($reason)) > 255` → `Products.stockReasonLength`; unknown product → `Products.productNotFound`. Every rejection throws `ValidationException` with a field key (`delta`, `reason`, `product`) and writes nothing.

`guardResult()` is the seam for the spec's pure-function scenario and for change 5's decrements; `increase()` still calls it (defensive). Opening stock reuses the same path: `ProductService::create()` wraps insert + `increase($id, $stock, self::REASON_OPENING, $userId)` in one outer transaction and skips `increase` when `$stock === 0`. CI4 nests by depth, so the inner commit does not commit; the outer catch rolls the product row back too — this is the one non-obvious failure mode and it is asserted by the rollback test.

**Rejected**: reason whitelist/enum (spec defines only non-empty + ≤255); a model for the insert (a second connection would silently escape the transaction); `transException(true)` (Query Builder throws under `DBDebug=true` already).

### D4 — Photo storage and serving

```php
final class ProductImageService
{
    public const MAX_BYTES = 2_097_152;
    public const MIME_TO_EXT = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const NAME_PATTERN = '/^[a-f0-9]{32}\.(?:jpg|png|webp)$/';

    public function __construct(private readonly string $directory = WRITEPATH . 'uploads/products') {}
    public function store(UploadedFile $file): string;  // returns "ab12…f0.jpg"
    public function path(string $filename): ?string;    // null unless NAME_PATTERN matches and is_file()
    public function delete(?string $filename): void;    // same guard, then @unlink
}
```

`store()` checks in this order and writes nothing until all pass: `$file->getError() !== UPLOAD_ERR_OK` → reject; `filesize($file->getPathname()) > MAX_BYTES` → reject (real bytes, not the client-declared size); `finfo` via `$file->getMimeType()` must be in `MIME_TO_EXT`; client name must contain exactly one dot (`substr_count($name, '.') === 1`) and its lowercased extension must equal the canonical extension of the detected MIME. Then `mkdir($directory, 0755, true)`, `bin2hex(random_bytes(16)) . '.' . $ext`, `copy($file->getPathname(), $target)`.

Two deliberate deviations from CI4's helpers: `getError()` instead of `isValid()` and `copy()` instead of `move()`, because both `isValid()` and `move()` require `is_uploaded_file()`, which is false for the temp files used by tests (D8). Guarantees are unchanged: the MIME comes from file content, the size from the real file, the name is server-generated, and the source path is PHP's own upload temp path. No env branch, no test-only code path.

`ProductsController::photo()` (public route, outside the group):

```php
$product = model(ProductModel::class)->find($id);          // (:num) route → non-numeric ids never reach here
$path    = $product?->image_path ? $images->path($product->image_path) : null;
if ($path === null) { throw PageNotFoundException::forPageNotFound(); }
$mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][pathinfo($path, PATHINFO_EXTENSION)];
return $this->response
    ->setHeader('Content-Type', $mime)
    ->setHeader('Content-Length', (string) filesize($path))
    ->setHeader('Cache-Control', 'public, max-age=86400')
    ->setHeader('X-Content-Type-Options', 'nosniff')
    ->setBody(file_get_contents($path));
```

Traversal is structurally impossible: the DB value is a bare filename, `path()` re-validates it against `NAME_PATTERN`, and a crafted `image_path` (`../../.env`) resolves to `null` → 404. `writable/` isolation is deployment-level (`DocumentRoot` is `public/`, vhost `snak.conf`), so `/writable/...` hits the front controller and 404s; `writable/.htaccess` stays as defence in depth for a misconfigured docroot.

### D5 — Routes and controller

```php
$routes->get('products/(:num)/photo', 'ProductsController::photo/$1');   // public read (change 5)
$routes->group('products', ['filter' => 'staff:cashier,admin'], static function (RouteCollection $routes): void {
    $routes->get('/', 'ProductsController::index');
    $routes->get('data', 'ProductsController::data');
    $routes->get('new', 'ProductsController::new');
    $routes->post('/', 'ProductsController::create');
    $routes->get('(:num)/edit', 'ProductsController::edit/$1');
    $routes->post('(:num)', 'ProductsController::update/$1');
    $routes->post('(:num)/activate', 'ProductsController::activate/$1');
    $routes->post('(:num)/deactivate', 'ProductsController::deactivate/$1');
    $routes->post('(:num)/stock', 'ProductsController::increaseStock/$1');
});
```

`staff:cashier,admin` is mandatory: `Filters` splits arguments on `,`, and `staff:cashier` alone would 403 every admin. There is no delete route, so `POST /products/{id}/delete` falls through to 404. CSRF is the existing global `csrf` filter (POST only); every form uses `csrf_field()`. The controller contains no role check.

Actions: `index` (view + `lowStock()->countAllResults()` summary), `data` (JSON), `new`/`edit` (form view), `create`/`update` (validate → photo → service → redirect with flash), `activate`/`deactivate` (`setActive`, idempotent), `increaseStock` (`StockControlService::increase` with `(int) service('session')->get(AuthService::SESSION_USER_ID)`). `find($id)` null → `PageNotFoundException`. `ValidationException` → `redirect()->back()->withInput()->with('errors', $e->getErrors())`; success → `with('message', lang('Products.created'))`.

Photo orchestration stays in the controller (three lines): `store()` → call the service → on `Throwable` `delete($new)` and rethrow; on a successful update with a new photo, `delete($old)`. Rejection therefore never leaves a file behind.

### D6 — Views and Spanish copy

| File | Content |
|---|---|
| `Views/products/index.php` | Bootstrap table `#productsTable`; DataTables client-side (`serverSide: false`, `ajax: {url: '/products/data'}`); columns Foto (thumb via `photo_url`), Nombre, Precio (`price_display`), Stock, Estado (`is_active`), Disponible (`available`), Acciones (Editar, Activar/Desactivar POST, inline stock form). Badges: `low_stock` → `badge-warning`; `available === false` → `badge-secondary`. DataTables strings come from `Products.datatable*` keys JSON-encoded into `section('pageScripts')` with `JSON_HEX_*` flags. |
| `Views/products/form.php` | Shared create/edit (`$product === null` → create). `enctype="multipart/form-data"`, POST to `/products` or `/products/{id}`, `csrf_field()`. Inputs: name, description, `price_cents` (label `Products.priceLabel` + hint showing the formatted BOB equivalent), readonly stock with a hidden `stock` field on edit (so the "stock unchanged" rule is testable), `photo` input with `accept` + `<img src="/products/{id}/photo">` preview, `is_active` never rendered. |
| `Views/cashier/index.php` | Modify: one link to `/products` labelled `lang('Products.title')`. |

`ProductsController::data()` payload — exact keys and order:

```json
{"data":[{"id":1,"name":"Agua 500ml","price_cents":350,"price_display":"3.50",
          "stock":7,"is_active":true,"available":true,"low_stock":false,
          "photo_url":"/products/1/photo"}]}
```
`photo_url` is `null` without a photo; `is_active`/`available`/`low_stock` are real JSON booleans; empty catalog → `{"data":[]}`. Rows are built by `ProductModel::listRow()` so change 5's sell list reuses one definition.

`app/Language/es/Products.php` (~48 keys): validation `nameRequired`, `nameLength`, `nameDuplicate`, `descriptionLength`, `priceRequired`, `priceInvalid`, `stockInvalid`, `stockEditForbidden`, `stockDeltaInvalid`, `stockReasonRequired`, `stockReasonLength`, `stockInsufficient`, `productNotFound`, `photoInvalid`, `photoTooLarge`, `photoExtension`, `photoMime`; flash `created`, `updated`, `activated`, `deactivated`, `stockAdded`; UI `title`, `newTitle`, `editTitle`, `catalogLink`, `nameLabel`, `descriptionLabel`, `priceLabel`, `priceHint`, `stockLabel`, `photoLabel`, `photoCurrent`, `saveButton`, `cancelButton`, `editButton`, `activateButton`, `deactivateButton`, `increaseButton`, `deltaLabel`, `reasonLabel`, `photoColumn`, `nameColumn`, `priceColumn`, `stockColumn`, `statusColumn`, `availableColumn`, `actionsColumn`, `lowStockBadge`, `lowStockSummary`, `activeBadge`, `inactiveBadge`, `availableBadge`, `unavailableBadge`, `datatableSearch`, `datatableEmpty`, `datatableZero`, `datatableInfo`.

### D7 — Availability semantics

| Concept | Owner | Values |
|---|---|---|
| `is_active` | explicit `setActive()` only | manual pause, survives stock changes |
| available | `isAvailable()` / `sellable()` | `is_active && stock >= 1` |
| low stock | `isLowStock()` / `lowStock()` | `stock < 5` (0 is low, 5 is not) |

Never merged, never stored. Views, JSON and queries call these helpers/scopes; no literal `5`, `0` or `1` appears in a view or controller. `sellable()` is the only query definition of sellability and backs change 5's sell list.

## Data Flow

```
POST /products            csrf → staff:cashier,admin → ProductsController::create
  └→ ProductService::validate (normalize name, rules, uniqueness)   [no writes]
  └→ ProductImageService::store (mime/size/extension)               [file written last]
  └→ transBegin
       ├ INSERT products (stock defaults to 0)
       ├ StockControlService::increase(stock>0) → SELECT … FOR UPDATE → UPDATE → INSERT movement
       └ transCommit                     any Throwable → rollback + delete(new file) + rethrow

GET /products/data        → ProductModel::findAll → listRow() → {"data":[…]}
GET /products/{id}/photo  → find → path() (regex re-validation) → 404 | bytes + image/* + cache
```

## File Changes

| File | Action | Description |
|---|---|---|
| `app/Database/Migrations/2026-10-21-110000_CreateProducts.php` | Create | products + unique name + is_active index |
| `app/Database/Migrations/2026-10-21-110100_CreateStockMovements.php` | Create | audit table + FK RESTRICT |
| `app/Models/ProductModel.php` | Create | fields, casts, constants, scopes, price accessor, `listRow` |
| `app/Services/StockControlService.php` | Create | audited increase, guards, row lock, transaction |
| `app/Services/ProductService.php` | Create | normalize/validate, create/update/setActive, opening stock |
| `app/Services/ProductImageService.php` | Create | store/path/delete, allowlists, random name |
| `app/Controllers/ProductsController.php` | Create | CRUD, activate/deactivate, stock, JSON, photo |
| `app/Views/products/index.php`, `form.php` | Create | listing + shared form |
| `app/Views/cashier/index.php` | Modify | catalog link |
| `app/Language/es/Products.php` | Create | every visible string |
| `app/Config/Routes.php` | Modify | public photo route + staff group |
| `app/Config/Services.php` | Modify | `products`, `stock`, `productImages` factories |
| `tests/_support/ProductTestTrait.php` | Create | FK-safe reset, staff session, upload fixtures |
| `tests/database/ProductModelTest.php` | Create | casts, scopes, uniqueness, indexes |
| `tests/database/StockControlServiceTest.php` | Create | audit row, rollback, guards, lock |
| `tests/database/ProductServiceTest.php` | Create | rules, normalization, stock-edit rejection, opening stock |
| `tests/feature/ProductsCrudTest.php` | Create | access, CSRF, CRUD, ids, idempotence |
| `tests/feature/ProductsDataTest.php` | Create | payload shape/types, empty, flags |
| `tests/feature/ProductPhotoTest.php` | Create | accept/reject, serving, `writable/` 404 |
| `tests/feature/StockIncreaseTest.php` | Create | endpoint increase + actor, invalid input |
| `tests/unit/ProductMoneyTest.php`, `ProductsLanguageTest.php` | Create | centavos formatting, key resolution |

## Testing Strategy

| Layer | What | Approach |
|---|---|---|
| Unit | `priceDisplay` (0.05/1.00/12.50), `isAvailable`, `isLowStock`, `guardResult(0)` ok / `guardResult(-1)` throws, every `Products.*` key referenced | plain `CIUnitTestCase`; key scan uses `/Products\.([A-Za-z0-9_]+)/` over `app/Services`, `app/Controllers`, `app/Views/products/` — broader than the auth version, because custom validation errors are bare `'Products.x'` strings |
| Database | unique name (index + `nameExists`, case/whitespace/deactivated), unsigned price/stock, casts, `sellable()`/`lowStock()`, increase writes exactly one row with actor, forced rollback, opening stock audited, no-UI stock edit rejected atomically | `DatabaseTestTrait` (`$DBGroup='tests'`, `$migrate=true`, `$migrateOnce=true`, `$refresh=false`, `$namespace=null`) + `ProductTestTrait` |
| Feature | anonymous 302 + no markup, cashier **and** admin 200, missing CSRF → `SecurityException`, payload shape/types, photo accept/reject/serve, `/writable/uploads/products/{name}` → 404, no delete route | `FeatureTestTrait` + `AuthTestTrait::postWithCsrf`, `service('superglobals')->setFilesArray()` |

`ProductTestTrait::resetCatalog()` must respect the FK: `truncate('stock_movements')` (leaf) then `DELETE FROM products` — MySQL refuses `TRUNCATE` on a table referenced by an FK **even when the child is empty**. `truncate('users')` stays valid because there is no FK to `users`.

Upload fixtures: no binaries in git. `WRITEPATH . 'tests/uploads/'` (gitignored, created in `setUp`). One base64 1×1 constant per format; pad with NULs to the target size (`finfo` reads only the header): 1 KB JPEG → accepted, exactly `2_097_152` → accepted, `2_097_153` → rejected; `<?php echo 1;` named `photo.jpg` → rejected; valid JPEG as `photo.php.jpg` → rejected; JPEG bytes as `photo.png` → rejected. Each rejection asserts `image_path` is unchanged **and** the upload directory file count is unchanged. Cleanup: `setFilesArray([])` + unlink the fixture dir in `tearDown`.

`writable/` isolation test: store a photo, `$this->get('writable/uploads/products/' . $filename)` → 404, body contains neither `writable/` nor `WRITEPATH`.

## Migration / Rollout

1. `php spark migrate --all` on `snak`; `migrate:status` shows both; one `migrate:rollback` drops both in FK-safe order.
2. Create `writable/uploads/products` with owner `www-data`, mode `0755` (operational, not in git).
3. No data migration: no production rows exist. Rollback = revert commits + `migrate:rollback` + remove the uploads directory (proposal's plan).

**Slice plan** (3 slices, 5 stacked PRs, every commit ≤400 changed lines; tests ship with the code they prove):

| PR | Slice | Content | ~Lines |
|---|---|---|---|
| 1 | 1 | Migrations, `ProductModel`, `ProductTestTrait`, `ProductModelTest`, `ProductMoneyTest` | 310 |
| 2 | 2 | `StockControlService`, `ProductService`, their DB tests | 320 |
| 3 | 2 | Controller CRUD (no `data()`), routes, `form.php`, `Products.php`, `ProductsCrudTest` | 335 |
| 4 | 2 | `index.php`, `data()` JSON, DataTables keys, `ProductsDataTest`, `ProductsLanguageTest` | 235 |
| 5 | 3 | `ProductImageService`, photo route + upload wiring, `ProductPhotoTest`, stock UI, `StockIncreaseTest` | 380 |

**Split point inside slice 2**: form/CRUD first, JSON listing second — the listing depends on `index.php` and the datatable keys, both of which are pure read-side, so PR 4 can lag PR 3 without dead code. Slice 2 as one unit is ~890 lines, hence three PRs, not two. Ordered stacked-to-main; PR 3 is the first demoable PR (cashier creates a product without a photo), PR 5 closes the photo and stock-UI requirements.

## Open Questions

- [ ] Form input is `price_cents` (integer), with the BOB value shown as a read-only hint. If the owner wants a "12,50" BOB field, that parser is integer-only and is a separate, testable change — not silently added here.
- [ ] `writable/uploads/products` ownership/mode is a deployment fact CI cannot assert; the photo tests create their own directory.
- [ ] `snak_test` must be migrated before PR 1's DB tests (same operational dependency as `auth-roles`); the suite fails loudly, it does not skip.
