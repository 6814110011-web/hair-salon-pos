# SDD ledger — plan: docs/superpowers/plans/2026-10-19-salon-pos-implementation.md

Spec approved and Native execution approved by user on 2026-10-19.
Baseline: php artisan test → 42 passed, 122 assertions.
Ruling: No Git repository exists; execute in the explicitly approved existing project folder and retain this ledger instead of git-only task scripts/commits/worktree creation. Cost if wrong: no automatic Git rollback; source and database backups remain available.
Pre-flight: Tasks1→2–10 share roles, Money and fixtures; consistent interfaces.
Pre-flight: Tasks2–5→6–9 share model relations and catalog fields; customer receipt links guarded until Task7 exists.
Pre-flight: Task6→7 consumes checkout(User,array):Sale and token ownership; consistent.
Pre-flight: Task6/7→8 uses existing cancellation and new refund confirmation; reason limited200 to fit audit notes.
Pre-flight: Tasks6/8→9 keeps item/payment/refund aggregations separate; consistent.
Pre-flight: Tasks1–9→10 navigation uses completed route names; consistent.
Task1: in progress. All implementation remains in current workspace; no production test data.
Task1: complete — tests RED observed for missing roles/command/Money/schema, then full suite 54 passed (147 assertions). Fixed stale guard remember-token rotation found by ProfileTest.
Task2: complete — CustomerTest RED 3 failures, GREEN plus full suite 57 passed; customer search/forms/history/escaping validated.
Task3: complete — EmployeeTest RED observed, full suite 61 passed. Staff create/update/password/role/commission/last-admin validated.
Task4: complete — ProductStockTest RED 5 failures, full suite 66 passed. Catalog cost isolation and stock audit/overflow/validation checked.
Task5: complete — ServiceTest RED observed, full suite 68 passed; category/price/duration/access checked.
Task6: complete — 10 CheckoutTest cases RED before service, full suite 78 passed. Ruling: share CheckoutRules between HTTP request and service to avoid diverging validation; no HTTP coupling. Cost if wrong: one small rules class.
Task7: complete — SalePagesTest RED before routes, full suite 80 passed; checkout form, safe JSON, preserved cart, scoped receipts, historical prices and JS syntax checked.
Task8: complete — CancellationRefundTest RED 3 failures, full suite 83 passed; cancel/refund HTTP authorization, max reason and immutable refund completion evidence validated.
Task9: complete — ReportDashboardTest RED 3 failures, full suite 86 passed; exact cents aggregation, midnight boundaries, pending refunds and scoped dashboards checked. Report aggregates decimal-cast rows in PHP to avoid floating SQLite SUM differences; acceptable salon scale.
Final review: fresh gpt-6-astra reviewer; no Critical issues. Important stock overflow and invalid scalar redisplay verified independently and fixed.
Final: fixed stock overflow — test_cancellation_stock_overflow_rejects_and_rolls_back RED→GREEN.
Final: fixed invalid scalar old input — test_malformed_scalar_input_redisplays_checkout_instead_of_crashing and customer redisplay RED→GREEN, shared scalar FormValue used across PHP forms.
Final: Ruling: re-grade sparse cart preservation from Minor to Important because structurally accepted input disappeared after a recoverable payment error, contrary to retained-cart requirements. Normalize row keys with array_values; regression RED→GREEN. Cost if wrong: one redundant normalization, no data mutation.
Final: Ruling: reviewer set aside real MySQL concurrency; static lock/unique analysis plus SQLite idempotency/atomic rollback tests accepted for this local classroom deliverable; live schema migration succeeded. Cost if wrong: concurrent requests may need retry; multi-client MySQL stress has not been demonstrated.
Final: Ruling: reviewer set aside browser/provisioning; parent performed isolated browser stock→mixed checkout→receipt→report, responsive320px, and live backup/migration/admin provisioning. Print CSS tested by inspection without sending a job to a physical printer. Cost if wrong: printer-specific layout can differ.
Deferred minors: none remain after severity assessment.
Task10: complete — NavigationTest RED before sidebar/home redirect; final suite92 tests/348 assertions, PHP/JS syntax and composer configuration valid;49 app routes. Isolated browser mixed checkout+stock+receipt+report passed;320px mobile menu checked. Fresh reviewer findings fixed RED→GREEN; live backup/migration and exact requested admin account provisioned and logged in. All live modules visited without test-data writes.
Finishing: no Git repository, so the approved in-place delivery applies; no branch/merge/PR menu is applicable. Retain source, ledger and evidence; keep port8000 server for user.
Verification detail: docs/verification.md records browser dialog-control limitation and all reviewer rulings; temporary browser setup script removed. Live8000 server preserved.
