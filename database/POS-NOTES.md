# Database changes — 19 October 2026

Apply changes to an existing imported `hair_salon_pos.sql` database with `php artisan migrate`.
Do not re-import the old SQL dump over the updated database. New installations also use migrations.
The original SQL file in Downloads is unchanged.

## Staff

`users.is_activate` is renamed to `is_active`, preserving all existing values.
Closing a profile sets `is_active=false`; it preserves the user ID and sales history.
Login rejects inactive accounts and middleware signs out existing inactive sessions.
Keep role and activation changes out of public registration/profile mass assignment.

## Promotions

`promotion_redemptions` records the customer, promotion, sale and visit milestone.
There is a database unique constraint on customer + promotion + active milestone.
Each sale has at most one redemption. A cancelled redemption keeps its history with
`active_milestone=NULL`, allowing a future qualifying sale to claim that milestone.

`PromotionRedemptionService::redeem($saleId)` records a paid sale's assigned promotion.
The rule is one reward for the latest completed milestone per promotion (5, 10, 15 visits,
for example); it does not accumulate unclaimed rewards from earlier milestones.
Changing a promotion's required visits after use needs a new promotion record.

## Cancellation

`SaleCancellationService::cancel($saleId, $admin, $reason)` runs inside a transaction:

- Only an active admin can cancel; a reason is required.
- A repeated cancellation does not add stock, points or refunds again.
- Original stock movements and point transactions are kept; new reversal records
  reference them through unique `reversal_of_id` fields.
- A paid product bill without matching stock deductions is rejected for manual review.
- Earned points are removed and redeemed points are returned. If the customer already
  spent the earned points and the reversal would make the balance negative, all
  changes are rolled back for manual resolution.
- A counted visit is undone only if the sale has `visit_counted=true`.
- Any recorded promotion redemption is released with a cancellation timestamp.
- Each payment on a paid bill gets a `payment_refunds` record with status `pending`.
  This is a request to return the original payment amount, excluding cash change.
  No bank transfer or gateway refund is made. A future refund screen must record
  actual completion, reference and completion time after money is returned.

## Requirements for the future checkout module

POS sale, cancellation and refund-confirmation screens now call these services.
Checkout is implemented in SaleCheckoutService; these rules also apply to future integrations.
Checkout must write the following in one transaction before it calls the redemption service:

1. Lock the sale and customer, then products in ascending ID order.
2. Validate prices, quantities, discounts, available stock and payment totals on the server.
3. Record `sale_items` using `App\Models\Product` (or `product`) for products.
4. Deduct product stock and create positive-quantity `out` stock movements with `sale_id`.
5. Record payments and mark the sale `paid` only after confirmed payment.
6. Increment the customer's visit count once per paid bill and set `visit_counted=true`.
7. Record earned/redeemed points and `balance_after` while holding the customer lock.
   `earn` adds points; `redeem`/`expire` deduct their absolute value; `adjust` is signed.
8. Record promotion redemption inside that same transaction when a promotion is applied.
   A rejected redemption must roll back the discounted sale as well.

All future point and visit writers must use the same customer locking rule. Existing
legacy bills are not guessed/backfilled: `visit_counted` defaults to false and older
stock movements retain a NULL `sale_id` until verified.

Sale reports must exclude cancelled sales from revenue and show pending refunds separately.
