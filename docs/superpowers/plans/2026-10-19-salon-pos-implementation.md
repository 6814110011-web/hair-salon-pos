# Salon POS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** เปิดใช้งานหน้าลูกค้า พนักงาน สินค้า บริการ ขาย ใบเสร็จ รายงาน และ Dashboard ที่เชื่อมฐานข้อมูลเดิมได้จริง

**Architecture:** Laravel Controllers/Form Requests รับฟอร์มจาก PHP views ใช้ Models สำหรับความสัมพันธ์ และ Services สำหรับธุรกรรมขาย สต็อก และคืนเงิน ใช้ CSS/JavaScript ธรรมดากับ layout ร่วมกัน ตรวจสิทธิ์และคำนวณยอดที่เซิร์ฟเวอร์

**Tech Stack:** Laravel 12, PHP >=8.2, MariaDB/MySQL, SQLite สำหรับ tests, HTML/PHP, CSS, JavaScript, PHPUnit

**Spec:** `docs/superpowers/specs/2026-10-19-salon-pos-design.md`

## Global Constraints

- ใช้ Laravel 12, PHP views, CSS และ JavaScript ธรรมดา
- ไม่เพิ่ม Blade, Tailwind CSS, Alpine.js หรือ frontend framework
- ใช้ฐานข้อมูลและข้อมูลเดิม ไม่สร้างโปรเจกต์ใหม่ ไม่ล้างฐานข้อมูลจริง
- เก็บระบบเข้าสู่ระบบ โปรไฟล์ และการปิดใช้งานบัญชีที่ทำไว้แล้ว
- เมนูด้านข้างภาษาไทย: Dashboard, ลูกค้า, พนักงาน, สินค้า/สต็อก, บริการ, ขาย, รายงาน
- รายการสินค้า/บริการจำกัดจำนวนเต็มบวกไม่เกิน 999 ต่อรายการที่รวมซ้ำแล้ว
- ราคาสูงสุด decimal(10,2) = 99,999,999.99 บาท = 9,999,999,999 สตางค์
- รองรับหนึ่งวิธีชำระต่อบิล: cash, promptpay, transfer, card
- ประเภทบริการ: cut, wash, color, perm, straighten, treatment, other
- กำหนด timezone แอปเป็น Asia/Bangkok; รายการใหม่เก็บเวลาแอปอย่างสม่ำเสมอ ไม่เดาหรือแปลงเวลาข้อมูลเก่า
- ไม่เปิดส่วนลดกำหนดเอง ไม่เพิ่มกติกาแต้มใหม่ ไม่เชื่อมธนาคาร ไม่เติมบิลทดสอบในฐานข้อมูลจริง
- โฟลเดอร์นี้ไม่เป็น Git repository: ทำงานในโฟลเดอร์เดิม ไม่ init, commit หรือสร้าง worktree อัตโนมัติ
- ทุก task เริ่มจาก test ที่ล้มเหลวด้วยเหตุที่ตั้งใจ แล้ว implement และตรวจ test นั้นก่อน task ถัดไป

## Review Focus

1. ส่งฟอร์มขายซ้ำหรือ token ของพนักงานอื่น: ต้องไม่ขายซ้ำหรือเปิดใบเสร็จข้ามเจ้าของ — Task 6/7
2. ชื่อสินค้า/ลูกค้ามี HTML และข้อมูลตะกร้าผิดพลาดกลับมาจาก validation: ต้องไม่ execute HTML และไม่ทำตะกร้าหาย — Task 2/7
3. รวมสินค้า ID เดิมหลายแถวและแตะขอบเขตยอดเงิน: ตรวจยอดรวมจริง ไม่ให้ตัดสต็อกเกินหรือ overflow — Task 4/6
4. ผู้ดูแลสองคนพยายามปิด admin คนสุดท้าย และเส้นทางปิดโปรไฟล์: ต้องใช้กติกาเดียวกันภายใน transaction — Task 1/3
5. หลายรายการ/หลาย payments ในบิลเก่าและเวลาเที่ยงคืน: ยอดรายงานต้องไม่ทวีคูณและไม่รวมผิดวัน — Task 9

## File Structure and Contracts

Paths ต่อไปนี้อยู่ใต้ root `D:/6814110011/hair-salon-pos`.

- `app/Http/Middleware/RequireRole.php`: ตรวจ role ของ request ทุก endpoint ที่ป้องกัน
- `app/Services/StaffAccountService.php`: เปลี่ยนบทบาท/สถานะและรักษา admin ที่ active คนสุดท้าย
- `app/Console/Commands/SalonAdmin.php`: คำสั่ง `salon:admin` สำหรับ bootstrap admin แบบ interactive
- `app/Support/Money.php`: parse/format เงินโดย integer cents ไม่ใช้ float
- `app/Models/{Customer,Product,Service,Sale,SaleItem,Payment,StockMovement,User}.php`: casts/relations ที่แต่ละโมดูลใช้
- `app/Http/Controllers/{Customer,Employee,Product,Stock,Service,Sale,Refund,Report,Dashboard}Controller.php`: หน้าจอและ HTTP adapters ตามชื่อโมดูล
- `app/Http/Requests/Pos/`: validation รายโมดูลเป็นไฟล์เฉพาะ
- `app/Services/{StockService,SaleCheckoutService,PaymentRefundService,SalesReportService}.php`: ธุรกรรมและรายงาน
- `app/Services/{PromotionRedemptionService,SaleCancellationService}.php`: ใช้ของเดิมและปรับเฉพาะสิ่งที่ checkout integration ต้องใช้
- `resources/views/{customers,employees,products,services,sales,reports}/`: `.php` views ตามโมดูล
- `resources/views/partials/pagination.php`: pagination แบบ PHP ธรรมดา ไม่เรียก default Blade pagination
- `resources/views/layouts/{header,footer}.php`, `public/css/app.css`, `public/js/{app,pos}.js`: layout และ assets
- `tests/Support/PosFixtures.php`: trait สำหรับสร้างข้อมูลแยกใน SQLite tests; ไม่เรียกกับฐานข้อมูลจริง
- `tests/Feature/Pos/`: tests ของแต่ละ task; คง tests เดิมที่พฤติกรรมยังตรงกัน

## Task 1: Roles, account safeguards, admin bootstrap and schema contracts

**Files:** Create `RequireRole.php`, `StaffAccountService.php`, `SalonAdmin.php`, `app/Support/Money.php`, `database/migrations/2026_10_19_000003_add_checkout_and_refund_confirmation.php`, `tests/Support/PosFixtures.php`, `tests/Feature/Pos/AccessTest.php`, `tests/Unit/MoneyTest.php`.
Modify `bootstrap/app.php`, `config/app.php`, `routes/auth.php`, `app/Models/User.php`, `app/Http/Controllers/ProfileController.php`, `resources/views/{welcome.php,auth/login.php,layouts/header.php}`, `tests/Feature/Auth/RegistrationTest.php`.

**Interfaces:**
- Produces middleware alias `role` with `handle(Request $request, Closure $next, string ...$roles): Response`.
- Produces `User::hasRole(string ...$roles): bool`.
- Produces `StaffAccountService::update(User $actor, User $target, array $attributes): User` and `deactivateSelf(User $user): void`; active-admin guard shared by both.
- Produces `Money::cents(string|int $value): int` and `Money::decimal(int $cents): string`; reject negative, scientific notation, >2 decimals, or values above column maximum with `InvalidArgumentException`.
- Produces nullable unique `sales.checkout_token` (UUID) and nullable `payment_refunds.completed_by` referencing users with restrictOnDelete; migration down reverses both.
- `PosFixtures` supplies `admin()`, `cashier()`, `stylist()` returning User and `customer(array $attributes = [])`, `product(array $attributes = [])`, `service(array $attributes = [])`, `promotion(array $attributes = [])` returning the corresponding Models.

- [x] Write tests: unauthenticated protected requests redirect login, authenticated wrong role gets 403, GET/POST `/register` gets 403 and creates no user, last active admin cannot self-deactivate, an ordinary cashier can still deactivate, migration up/down preserves legacy sales, command promotes only the email selected by the operator.
- [x] Write money assertions: `Money::cents('0.10') === 10`, `Money::cents('99999999.99') === 9999999999`, `Money::decimal(10005) === '100.05'`; assert each of `'-1','1e3','1.001','100000000.00'` throws `InvalidArgumentException`.

```php
public function test_money_keeps_exact_cents(): void
{
    $this->assertSame(10, Money::cents('0.10'));
    $this->assertSame('100.05', Money::decimal(10005));
}
```
- [x] Run `php artisan test --filter='AccessTest|MoneyTest|RegistrationTest|ProfileTest'`; confirm new behavior fails before implementation, rather than failing for fixture syntax.
- [x] Implement roles and aliases. Profile closure calls `deactivateSelf`, which locks active admins in ID order before checking the last-admin rule. Keep `User::$fillable` restricted to name/email/password; staff mutations use validated allowlisted attributes only.
- [x] Implement `salon:admin` with email prompt, explicit confirmation for an existing user, hidden/confirmed password for a new user, validation and hashed password. Do not run the interactive command without an operator-selected account.
- [x] Remove public signup links; keep registration route names returning 403 to avoid dangling route references. Add the migration and Money helper. Set app timezone to Asia/Bangkok, without rewriting old rows.
- [x] Run the same tests; require PASS. Run `php artisan list` and check `salon:admin` is registered. Record completed task; Git commit skipped because no Git repository.

## Task 2: Customer management and history

**Files:** Create `app/Http/Controllers/CustomerController.php`, `app/Http/Requests/Pos/CustomerRequest.php`, `resources/views/customers/{index,form,show}.php`, `resources/views/partials/pagination.php`, `tests/Feature/Pos/CustomerTest.php`.
Modify `app/Models/{Customer,Sale}.php`, `routes/web.php`.

**Interfaces:**
- Consumes `role:admin,cashier`, PosFixtures.
- Produces routes `customers.index/create/store/show/edit/update` under `/customers` using resource routing without destroy.
- `CustomerController` uses conventional resource method signatures; `show(Customer $customer): View` supplies customer and paginated sale history with sale items.
- Produces `Customer::sales(): HasMany`, `Sale::customer(): BelongsTo`, decimal casts for sale totals.
- Pagination partial consumes an `Illuminate\Contracts\Pagination\LengthAwarePaginator` named `$paginator` and preserves query parameters.

- [x] Write tests: both admin/cashier can create/update; duplicate phone is rejected; `total_visits` injection is ignored; search matches name/phone; stylist gets 403; customer history displays paid/cancelled status; cashier cannot use another cashier's receipt link.
- [x] Add an escaping test that creates customer name `<script>alert(1)</script>` then asserts response contains the escaped value and not the literal script. Pagination must render `.php` views without a Vite manifest or Blade application components.

```php
public function test_customer_names_are_escaped(): void
{
    $customer = $this->customer(['name' => '<script>alert(1)</script>']);
    $this->actingAs($this->admin())->get(route('customers.show', $customer))
        ->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
}
```
- [x] Run `php artisan test --filter=CustomerTest`, confirm failures for missing routes/behavior.
- [x] Implement validation: name nullable max255, phone required max15 unique ignoring the bound customer. Persist only name/phone. Search/filter inputs bounded to max255; paginate 15 with query string.
- [x] Implement reusable PHP pagination and forms with `csrf_field()`, `method_field('patch')`, escaped old input and error messages. Receipt links appear only for admin or the bill owner; history still shows service details. Until Task7 registers sales.show, guard links with Route::has('sales.show') so this task works independently.
- [x] Run `php artisan test --filter='CustomerTest|PlainPhpViewsTest'`; require PASS. Mark task completed.

## Task 3: Employee management

**Files:** Create `app/Http/Controllers/EmployeeController.php`, `app/Http/Requests/Pos/EmployeeRequest.php`, `resources/views/employees/{index,form}.php`, `tests/Feature/Pos/EmployeeTest.php`.
Modify `StaffAccountService.php`, `routes/web.php`.

**Interfaces:**
- Consumes `StaffAccountService::update` and `role:admin`.
- Produces resource route names `employees.index/create/store/edit/update` under `/employees`; no physical delete endpoint.
- Adds `StaffAccountService::create(User $actor, array $attributes): User`.

- [x] Write tests: admin creates stylist with hashed password; cashier gets 403; email duplicates rejected; blank edit password preserves hash; commission accepts 0/100 and rejects -0.01/100.01; public profile cannot promote role; disabling staff preserves their sales.
- [x] Test both controller update and profile closure against last-admin protection. Assert a disabled admin does not count as an active replacement, and changing a different user does not inadvertently change the actor.

```php
public function test_cashier_cannot_manage_employees(): void
{
    $this->actingAs($this->cashier())->get(route('employees.index'))->assertForbidden();
    $this->assertDatabaseCount('users', 1);
}
```
- [x] Run `php artisan test --filter=EmployeeTest`, confirm the intended failures.
- [x] Implement conventional index/create/store/edit/update methods, staff validation, explicit active boolean handling, optional password on edit and safe staff service. Lock active admins in ID order, re-read target under lock and check last-admin invariant inside the same transaction.
- [x] Implement search name/email/role, active filter, pagination, role labels, new/edit password hints and status controls in PHP views. No readable password fields or hashes appear in responses.
- [x] Run `php artisan test --filter='EmployeeTest|AccessTest|ProfileTest'`; require PASS. Mark task completed.

## Task 4: Product catalogue and stock ledger

**Files:** Create `app/Http/Controllers/{Product,Stock}Controller.php`, `app/Http/Requests/Pos/{Product,Stock}Request.php`, `app/Services/StockService.php`, `resources/views/products/{index,form,show}.php`, `tests/Feature/Pos/ProductStockTest.php`.
Modify `app/Models/{Product,StockMovement}.php`, `routes/web.php`.

**Interfaces:**
- Produces product routes `products.index/create/store/show/edit/update`; writes and stock history admin-only, index admin/cashier.
- Produces POST `/products/{product}/stock` named `products.stock.store`.
- `StockService::receive(User $actor, Product $product, int $quantity, string $note): StockMovement`.
- `StockService::adjust(User $actor, Product $product, int $targetQuantity, string $reason): StockMovement`.
- Produces `Product::stockMovements(): HasMany`, casts for cost/sell prices and active status.

- [x] Write tests: new product stock is 0 even with injected stock_qty; receive 5 then adjust to 3 gives stock3 and ledger qty5/typein then qty-2/typeadjust; negative target/zero receipt/empty reason fail; no-op adjustment reports no change without adding a misleading ledger row.
- [x] Test SKU uniqueness, low-stock filter, and boundaries: target stock 2147483647 accepted, an addition beyond it rejected with ledger and stock unchanged. Cashier response must not contain the product cost value or cost field, and cannot post stock changes.

```php
public function test_stock_changes_have_an_audit_trail(): void
{
    $actor = $this->admin();
    $product = $this->product(['stock_qty' => 0]);
    app(StockService::class)->receive($actor, $product, 5, 'Receive');
    app(StockService::class)->adjust($actor, $product, 3, 'Count correction');
    $this->assertSame(3, $product->fresh()->stock_qty);
    $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'adjust', 'qty' => -2]);
}
```
- [x] Run `php artisan test --filter=ProductStockTest`, confirm failures.
- [x] Implement admin-only product writes, immutable stock on catalog forms, price bounds with at most two decimals, sku max30/unit max20, nonnegative min_stock and initial stock0. Cashier index projects only id/sku/name/sell_price/stock_qty/unit/is_active.
- [x] Implement StockService transactions, actor checks, row locks, integer bounds, ledger write and unchanged-stock validation. StockRequest selects `mode=receive|adjust` with quantity/target and required reason max255 to fit the note column; type/user/stock_after are never trusted from the client.
- [x] Implement stock status badges and PHP detail/history forms. Run `php artisan test --filter='ProductStockTest|PosIntegrityTest'`; require PASS. Mark task completed.

## Task 5: Service catalogue

**Files:** Create `app/Http/Controllers/ServiceController.php`, `app/Http/Requests/Pos/ServiceRequest.php`, `resources/views/services/{index,form}.php`, `tests/Feature/Pos/ServiceTest.php`.
Modify `app/Models/Service.php`, `routes/web.php`.

**Interfaces:**
- Produces resource routes `services.index/create/store/edit/update`; index all three roles, writes admin only.
- Service casts price decimal:2 and is_active boolean. Stored categories use existing enum values.

- [x] Write tests: admin creates/edits/deactivates a service; cashier/stylist can list but cannot write; invalid category, negative/excess price, zero/fractional duration fail; searching category and name filters correctly.

```php
public function test_stylist_can_read_but_cannot_create_services(): void
{
    $this->actingAs($this->stylist())->get(route('services.index'))->assertOk();
    $this->post(route('services.store'), [])->assertForbidden();
    $this->assertDatabaseCount('services', 0);
}
```
- [x] Run `php artisan test --filter=ServiceTest`, confirm failures.
- [x] Implement resource controller/forms using price max99999999.99 and duration integer 1..2147483647, active status, search and pagination. Do not delete services or rewrite historical sale-item names/prices.
- [x] Run `php artisan test --filter=ServiceTest`; require PASS. Mark task completed.

## Task 6: Atomic checkout backend

**Files:** Create `app/Services/SaleCheckoutService.php`, `app/Http/Requests/Pos/CheckoutRequest.php`, `tests/Feature/Pos/CheckoutTest.php`.
Modify `app/Models/{Sale,SaleItem,Payment,User}.php`, `PromotionRedemptionService.php` only when required to preserve its existing contract.

**Interfaces:**
- Consumes Money, active models, existing `PromotionRedemptionService::redeem(int $saleId): int`.
- Produces `SaleCheckoutService::checkout(User $actor, array $data): Sale`.
- Validated input: `checkout_token` UUID, optional `customer_id`, optional `promotion_id`, `items` array1..100 containing `type=product|service,id,qty=1..999,expected_price` decimal and `stylist_id?`, `method=cash|promptpay|transfer|card`, `received` decimal, `ref_no` optional max255 (required unless cash). `expected_price` is compared against server price only to reject stale catalog quotes; it never sets the charged price.
- Produces `Sale::items(): HasMany`, `payments(): HasMany`, `user(): BelongsTo`; `SaleItem::itemable(): MorphTo`, `stylist(): BelongsTo`, `sale(): BelongsTo`; `Payment::sale(): BelongsTo`; relevant decimal/bool casts.
- Store product/service FQCN in itemable_type for compatibility with existing cancellation service.

- [x] Write tests with product100 x2 + service150 and received500: paid total350, change150, one payment, stock decremented2, positive out movement linked to sale, original names/prices snapshotted and customer visits incremented exactly1.
- [x] Test walk-in sale leaves customer/visits absent; a service requires an active stylist; inactive/missing products or stylist, insufficient cash, empty cart, invalid qty and missing noncash ref are rejected with zero partial writes.
- [x] Test client price/total/discount/user_id are ignored; missing or mismatched expected_price rejects stale/manipulated quotes; duplicate product rows merge before stock checks and combined qty >999 rejects; aggregate total above99999999.99 rejects atomically; use decimal values0.10/0.20 without floating error.
- [x] Test duplicate checkout token returns the same bill with counts of sales/payments/movements unchanged; token owned by another cashier is denied; direct service calls reject inactive/noncashier actors too.
- [x] Test promotion visit5 qualifying at checkout, visit4 rejection, reused milestone rejection with rollback, percent rounding half-up in integer cents, max100% and amount reduction capped at subtotal; malformed existing promotion config rejects instead of producing negative/overflow totals.

```php
public function test_checkout_is_idempotent(): void
{
    $cashier = $this->cashier();
    $product = $this->product(['sell_price' => '100.00', 'stock_qty' => 5]);
    $input = ['checkout_token' => (string) Str::uuid(), 'method' => 'cash', 'received' => '200.00',
        'items' => [['type' => 'product', 'id' => $product->id, 'qty' => 2, 'expected_price' => '100.00']]];
    $first = app(SaleCheckoutService::class)->checkout($cashier, $input);
    $second = app(SaleCheckoutService::class)->checkout($cashier, $input);
    $this->assertSame($first->id, $second->id);
    $this->assertSame(3, $product->fresh()->stock_qty);
    $this->assertDatabaseCount('sales', 1);
    $this->assertDatabaseCount('payments', 1);
}
```
- [x] Run `php artisan test --filter=CheckoutTest`, confirm each new scenario fails for the intended missing implementation.
- [x] Implement service: revalidate critical input/domain rules; transaction creates unique-token bill, locks customer and product IDs ascending, reads authoritative catalog prices/status, validates stylist, merges items, calculates bounded integer cents, writes items/stock ledgers/visit, sets paid within transaction, records promotion redemption and payment. Rollback on any failure.
- [x] Generate bill_no from20 hex characters of UUID, retry only a confirmed bill_no unique collision. Resolve concurrent checkout_token collisions outside the failed transaction by loading the committed bill and checking actor ownership; never swallow unrelated SQL errors.
- [x] CheckoutRequest rejects structural errors with Thai messages, strips untrusted fields and requires numeric scale<=2. Service applies the same critical bounds to its public API. Run `php artisan test --filter='CheckoutTest|MoneyTest|PosIntegrityTest'`; require PASS. Mark task completed.

## Task 7: Sale screen, bill list and printable receipts

**Files:** Create `app/Http/Controllers/SaleController.php`, `resources/views/sales/{index,create,show}.php`, `public/js/pos.js`, `tests/Feature/Pos/SalePagesTest.php`.
Modify `routes/web.php`, `public/css/app.css`.

**Interfaces:**
- Consumes `SaleCheckoutService::checkout`, models/relations and role middleware.
- Produces `sales.index/create/store/show` routes (`/sales`, `/sales/create`, POST `/sales`, `/sales/{sale}`). Literal `/create` registered before `/{sale}`.
- `SaleController::store(CheckoutRequest $request): RedirectResponse` redirects receipt after success; show/index scope cashier to own sales and admin to all.
- JavaScript consumes explicit safe catalogue JSON (id,name,price,stock,unit,type) and initial old items; it never receives product costs/passwords or full Models serialization.

- [x] Write HTTP tests: checkout page renders active catalog/customer/stylist controls and UUID token; POST redirects correct receipt; admin can read all/cashier only own; stylist cannot access; filter date/status/bill number; receipt retains historical item price/name after catalog edit.
- [x] Test failed checkout keeps submitted item IDs/types/qty/stylist, customer and method but does not trust stale price; all item names and old values escape HTML/JSON script contexts. Assert print stylesheet and receipt payment/change labels exist.

```php
public function test_sales_page_shows_a_real_checkout_form(): void
{
    $this->actingAs($this->cashier())->get(route('sales.create'))->assertOk()
        ->assertSee('name="checkout_token"', false)
        ->assertSee('name="received"', false)
        ->assertSee('/js/pos.js', false);
}
```
- [x] Run `php artisan test --filter=SalePagesTest`, confirm failures.
- [x] Implement screen/controller with search, add/remove items, qty controls, per-service stylist, customer, optional promotion, method/reference/received, calculated preview and a real submit form. Use textContent and json_encode JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT for embedded data.
- [x] Disable submit while pending as a UI convenience while keeping server idempotency. Reconstruct old cart on validation failure and focus error summary. Missing/disabled old catalog entries are shown as errors/removable items, never silently substituted or sold.
- [x] Implement receipt and scoped list; print CSS hides controls/navigation and keeps receipt totals legible. Run `node --check public/js/pos.js` and `php artisan test --filter='SalePagesTest|CheckoutTest'`; require PASS. Mark task completed.

## Task 8: Cancellation and actual refund confirmation screens

**Files:** Create `app/Http/Controllers/RefundController.php`, `app/Http/Requests/Pos/{CancelSale,Refund}Request.php`, `app/Services/PaymentRefundService.php`, `tests/Feature/Pos/CancellationRefundTest.php`.
Modify `SaleController.php`, `resources/views/sales/show.php`, `routes/web.php`; retain existing `SaleCancellationService` behavior.

**Interfaces:**
- Produces POST `/sales/{sale}/cancel` named `sales.cancel` (admin).
- Produces POST `/refunds/{refund}/complete` named `refunds.complete` (admin).
- `PaymentRefundService::complete(int $refundId, User $actor, string $reference): void`, verifies active admin, locks refund and records completed_by/completed_at/reference only once.
- `SaleController::cancel(CancelSaleRequest $request, Sale $sale): RedirectResponse` calls existing cancellation service then redirects bill with a success message distinguishing pending refund from completed return.

- [x] Write HTTP tests: admin cancels a paid checkout bill; cashier gets403; empty reason rejects; receipt marks cancelled and pending refund; completed refund stores admin/ref/time; duplicate confirmation with different reference preserves original evidence; wrong refund ID404.
- [x] Test actual refund uses payment.amount rather than received500/change300 and does not alter original payment; validate ref string1..255 and cancellation reason1..200 so prefixed note fits255. A201-character reason must fail before any cancellation write.

```php
public function test_cashier_cannot_confirm_refunds(): void
{
    $this->actingAs($this->cashier())->post('/refunds/999/complete', ['ref_no' => 'R1'])
        ->assertForbidden();
    $this->assertDatabaseCount('payment_refunds', 0);
}
```
- [x] Run `php artisan test --filter=CancellationRefundTest`, confirm failures.
- [x] Implement endpoints, forms and service; require cancellation reason max200 consistently in request and backend service. Confirm button clearly says money must already have been returned; no bank integration or automatic transfer claim.
- [x] Run `php artisan test --filter='CancellationRefundTest|PosIntegrityTest|CheckoutTest'`; require PASS. Mark task completed.

## Task 9: Reports and role-specific dashboard

**Files:** Create `app/Services/SalesReportService.php`, `app/Http/Controllers/{Report,Dashboard}Controller.php`, `app/Http/Requests/Pos/ReportRequest.php`, `resources/views/reports/index.php`, `tests/Feature/Pos/ReportDashboardTest.php`.
Modify `resources/views/dashboard.php`, `routes/web.php`, `app/Models/User.php` relations as needed.

**Interfaces:**
- Produces `SalesReportService::summary(string $from, string $to, ?int $sellerId = null): array` with keys `revenue`, `bill_count`, `discount`, `average_bill`, `item_totals`, `payment_totals`, `cancelled_count`, `pending_refunds`; money values decimal strings.
- Boundaries are local app start-of-day inclusive and day after `to` exclusive, consistent with local new-row storage; old data is never automatically reinterpreted.
- GET `/reports` named `reports.index` (admin); GET `/dashboard` uses DashboardController authenticated for all three roles.
- DashboardController passes distinct data by role, not all shop totals hidden with CSS.

- [x] Write tests: two paid bills with multiple items and payments give exact revenue/count/discount; cancelled bill excluded; item totals are before discount; empty report average0; pending refunds filtered by cancellation date; completed refunds excluded from pending.
- [x] Test local timestamps23:59:59 included and next-day00:00:00 excluded, reversed date range validation, admin-only reports, cashier Dashboard only its own sales and stylist only its assigned paid service items.

```php
public function test_empty_report_has_zero_average(): void
{
    $report = app(SalesReportService::class)->summary('2026-10-19', '2026-10-19');
    $this->assertSame('0.00', $report['revenue']);
    $this->assertSame(0, $report['bill_count']);
    $this->assertSame('0.00', $report['average_bill']);
}
```
- [x] Run `php artisan test --filter=ReportDashboardTest`, confirm failures.
- [x] Implement separate sale/item/payment/refund aggregations to avoid multiplying joins; date filter validation `Y-m-d` and from<=to, defaults today; all sums returned as bounded decimal strings. Cancelled count refers cancelled_at in chosen range.
- [x] Implement report summary cards, type/method tables and dates; Dashboard admin low-stock/recent bills, cashier own totals/recent bills, stylist assigned services today. Run `php artisan test --filter=ReportDashboardTest`; require PASS. Mark task completed.

## Task 10: Integrate navigation, visual verification and handover

**Files:** Modify `resources/views/layouts/{header,footer}.php`, `resources/views/{welcome,dashboard}.php`, `public/{css/app.css,js/app.js}`, `README.md`, `database/POS-NOTES.md`.
Create `database/seeders/SalonDemoSeeder.php`, `tests/Feature/Pos/NavigationTest.php`, `docs/pos-user-guide.md`.

**Interfaces:**
- Consumes all prior routes/role checks; navigation shows only permitted modules and current-page state.
- Optional `SalonDemoSeeder` inserts only named demo customer/catalog/service records with update-or-create-safe identifiers, never users/bills/payments and never truncates tables. It is not auto-invoked by DatabaseSeeder.
- Mobile menu uses button `aria-expanded` and `aria-controls`; no framework dependencies.

- [x] Write NavigationTest: each role sees precisely its menu links; authenticated `/` redirects Dashboard; guest welcome/login has no public signup; empty states and validation summaries are present; no page requires frontend build artifacts.

```php
public function test_authenticated_home_opens_dashboard(): void
{
    $this->actingAs($this->admin())->get('/')->assertRedirect(route('dashboard'));
}
```
- [x] Run `php artisan test --filter=NavigationTest`, confirm failures.
- [x] Implement layout/sidebar/responsive tables with existing semantic CSS. Keep CSRF, active-account checks and keyboard focus. Provide plain PHP pagination everywhere; no default Blade paginator usage.
- [x] Write README/guide: start server, migrate, interactive admin setup, customer/staff/catalog/stock/sale/receipt/report usage, actual refund confirmation, optional demo seed and the fact public registration is closed. Explain PHP/CSS/JS file locations and no npm requirement.
- [x] Run `php artisan test`, PHP lint on changed source/views/migrations/tests, `node --check public/js/app.js`, `node --check public/js/pos.js`, `composer validate --no-check-publish`, `php artisan route:list --except-vendor`. Require passing outputs; record actual totals rather than predicting them.
- [x] Perform browser smoke on an isolated test database/server: admin login → add customer/stylist/product/service → receive stock → sell product+service → print receipt → view report → cancel → confirm refund; cashier forbidden URLs and320px/desktop layouts. Read computer-use skill before browser automation. Do not create demo sales in the user's configured real DB.
- [x] Request a fresh independent code review using Superpowers requesting-code-review, covering role bypass, locking/idempotency, decimal bounds, client injection, refund evidence and report aggregation. Fix actionable findings with regression tests and rerun affected checks.
- [x] Before live migration back up the configured database without printing credentials, then apply only the new migration and verify migrate:status. No migrate:fresh, reset, or automatic admin promotion. If browser verification used isolated DB, keep the user's `.env` untouched.
- [x] Record completed checklist and summarize delivered pages, test evidence, how to provision first admin, and any verification limitation. No commit/PR/deploy because this folder has no Git repository and none was requested.

## Self-review and execution handoff

Spec sections1–3 are covered by Tasks1/7/10; section4 by Tasks1/3 and per-route tests;
sections5.1–5.4 by Tasks2–5; section5.5 by Tasks6/7; section5.6 by Task8;
section5.7 by Task9; section6 by Tasks1/2/4/5/6; section7 by all Form Requests,
Money and Task7's encoding tests; sections8–9 by each test cycle and Task10.

The tasks share role/model/checkout contracts, so this is one ordered integration plan.
Recommended execution: **Native** — implement tasks in this chat, then fresh independent
review at the end. This avoids repeatedly handing shared checkout/report interfaces
between implementers. Alternative: subagent-driven implementation/review per task,
with higher context cost. Wait for the user's plan review and execution selection
before product implementation as required by Superpowers.
