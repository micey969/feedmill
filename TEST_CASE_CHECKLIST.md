# Feedmill Application User Test Checklist

Use this checklist to verify the main user workflows in the Feedmill Production System. Run it in a test or staging environment with representative test data, not against live production records. Most entries create permanent records, and the application does not provide delete controls.

## Test Run Details

- Tester:
- Environment / URL:
- Date:
- Build or release:
- Result key: Pass / Fail / Blocked
- Record any created test IDs and failure details in the notes at the end.

## Preparation

- [ ] Confirm the application and database are available and the login page loads without errors.
- [ ] Prepare active test accounts for each role: `user`, `supervisor`, and `admin`; have an inactive account available for the login check.
- [ ] Prepare at least one active miller, supplier, and ingredient. Have additional test ingredients and enough records to test search and pagination.
- [ ] Use clearly identifiable test names/codes, such as `TEST - ...`, and record the IDs created during the run.
- [ ] For production checks, prepare an active formula with active ingredients and known quantities per batch.
- [ ] For stock and report checks, note the test dates and starting stock values so expected totals can be calculated.

## Login, Navigation, and Access

- [ ] Submit valid credentials for an active account. Expected: login succeeds, the dashboard opens, and the signed-in user's name and job title are shown.
- [ ] Submit an incorrect username or password. Expected: access is denied and an invalid-login message is displayed.
- [ ] Attempt to log in with the inactive account. Expected: access is denied.
- [ ] Use the sidebar links and dashboard shortcuts. Expected: each opens the matching page and the active navigation section is apparent.
- [ ] Sign in as a `user`. Expected: Production (Mixing Sheet) and Reports are available; product management, inventory, miller management, accounts, and audit pages are not available from navigation.
- [ ] As a `user`, open a restricted page directly by URL (for example, Suppliers or Formulas). Expected: access is denied, including for restricted save/update endpoints.
- [ ] Sign in as a `supervisor`. Expected: general operational pages and miller management are available, but User Accounts and Audit Logs are denied, including by direct URL.
- [ ] Sign in as an `admin`. Expected: all application sections, User Accounts, and Audit Logs are available.
- [ ] Sign out. Expected: the session ends and opening a protected page returns to login.
- [ ] Leave a signed-in session inactive for more than 10 minutes, then open a protected page. Expected: the session expires and the user is asked to log in again.

## Dashboard

- [ ] Open the dashboard with known outstanding mixing sheets, pending orders, and a physical stock count. Expected: each status count/date agrees with the corresponding records.
- [ ] Complete or receive a test record, return to the dashboard, and refresh. Expected: affected status figures update.
- [ ] Use each dashboard shortcut. Expected: it opens the related variance, physical stock, receive, or mixing page.
- [ ] Check the dashboard with no matching records (if a clean test database is available). Expected: empty-state values are clear and the page still loads.

## Ingredients and Formulas

- [ ] Add a uniquely named ingredient. Expected: it is saved and appears in the ingredient catalog and active ingredient selectors.
- [ ] Try to add the same ingredient name again. Expected: the duplicate is rejected with a useful message and the catalog is not duplicated.
- [ ] Rename an ingredient and save catalog changes. Expected: the new name is displayed in subsequent views while existing records remain accessible.
- [ ] Deactivate an ingredient. Expected: it is marked inactive and is no longer offered for new formulas, orders, or applicable production entry; existing historical records remain viewable.
- [ ] Reactivate the ingredient. Expected: it becomes available again in active selectors.
- [ ] In the formula editor, add and remove ingredient rows and change quantities. Expected: rows behave correctly and the batch-weight total updates to the sum of entered quantities.
- [ ] Save a valid formula with a unique feed code, name, creator, effective date, and ingredients. Expected: a success message appears and the formula is listed with its metadata and ingredient count.
- [ ] Try saving a formula with a duplicate feed code or invalid/missing required details. Expected: it is rejected without creating a duplicate or partial record.
- [ ] Edit an existing formula's metadata, ingredient list, quantities, or active state. Expected: saved changes appear in the list; stale/conflicting edits are reported instead of silently overwriting newer changes.
- [ ] Search the Formula List by feed code, name, and description. Expected: matching rows appear and clearing the search restores the list.
- [ ] Filter the Formula List to All, Active, and Inactive. Expected: each result set matches its selected status.
- [ ] Navigate Formula List pages when enough records exist. Expected: page controls, record counts, and active filters/search remain consistent.
- [ ] Confirm inactive formulas and formulas without active ingredients are not offered for new mixing sheets.

## Suppliers and Purchase Orders

- [ ] Add a supplier with valid company/contact details. Expected: it appears in the supplier list and is available when creating an order.
- [ ] Edit a supplier and save. Expected: the updated values appear in the list; invalid or conflicting changes show an error.
- [ ] Search suppliers by company, contact person, or country. Expected: matching results and result counts are correct; clearing search restores the list.
- [ ] Navigate supplier pages when enough records exist. Expected: first/previous/next/last controls and counts are correct.
- [ ] Create a purchase order using a supplier, active miller, order date/time, later expected-arrival date, and one or more active ingredients with positive quantities. Expected: the order is saved and appears as Pending in Order History and Receive Orders.
- [ ] Add multiple line items and remove a line before submitting. Expected: only the remaining selected lines are saved.
- [ ] Try an expected-arrival date on or before the order date, missing required fields, or a non-positive quantity. Expected: submission is blocked or rejected and no invalid order is created.
- [ ] Search Order History by order ID, supplier, ingredient, and invoice number. Expected: matching order(s) appear and clearing the search restores the list.
- [ ] Edit a pending order's details or line quantities. Expected: saved changes appear in Order History; a conflict is reported if another user changed the order.
- [ ] Open a completed order. Expected: it is view-only and shows supplier, dates, invoice, transport, ordered/received quantities, receiving date, and miller details.
- [ ] Navigate Order History pages when enough records exist. Expected: page controls, counts, and search are consistent.

## Receiving and Transport

- [ ] Select a pending order in Receive Orders. Expected: supplier, order date, pending quantity, and only pending ingredient lines are shown.
- [ ] Receive all order lines using a valid arrival date/time, active receiving miller, transport, and invoice/delivery note number. Expected: a success message appears; Order History shows the entered quantities and receipt details; the order is Completed and no longer appears as pending.
- [ ] Receive only some lines and uncheck a line not delivered. Expected: selected lines are received, unchecked lines remain pending, and the order remains Pending until every line is received.
- [ ] Enter a received quantity different from the ordered quantity (for example, a partial delivery). Expected: the entered received quantity is retained and displayed accurately; only the selected line is marked received.
- [ ] Try submitting with no selected lines, a zero/negative quantity, an invalid date, or missing required miller/transport. Expected: invalid receipt data is rejected and the order remains unchanged.
- [ ] Attempt to receive the same line again after it is completed. Expected: it cannot be received twice; an already-modified order is reported as a conflict where applicable.
- [ ] Add a transport in the Receive page's transport catalog. Expected: it is saved and available in the receipt form.
- [ ] Rename a transport and save changes. Expected: the new name appears in the catalog and selectors; duplicate names are rejected with a clear message.
- [ ] Search the transport catalog by name and ID. Expected: matching rows and the visible-row count update correctly.

## Physical Stock

- [ ] Start a new physical stock count. Expected: the current date and active ingredients are shown.
- [ ] Enter non-negative whole-number quantities and save. Expected: the count is saved for the selected date and a success message appears.
- [ ] Open the saved stock record from the record navigation. Expected: its date and quantities are displayed as read-only.
- [ ] Try saving a second physical count for the same date. Expected: the duplicate date is rejected and the existing count is unchanged.
- [ ] Try submitting a future date or invalid/negative quantity. Expected: the entry is rejected and no invalid stock record is added.
- [ ] Check record navigation with multiple stock dates. Expected: new, previous, next, and last-record navigation opens the correct records and stops at the ends.
- [ ] Check the dashboard's Last Physical Closing Date after saving a count. Expected: it reflects the latest saved count.

## Production Mixing and Materials Used

- [ ] Open a new Mixing Sheet. Expected: active formulas with active ingredients and active millers are selectable; the default date is today.
- [ ] Select a formula, miller, batch size, and valid whole-ton production quantity. Expected: ingredient quantities and bag count are calculated from the formula and selected production amount.
- [ ] Check supported batch sizes and valid capacities: 0.5-ton batches up to 5 tons, 1-ton batches up to 10 tons, and 2-ton batches up to 20 tons (2-ton batches require an even tonnage). Expected: valid combinations save; invalid combinations are rejected with no partial sheet.
- [ ] Save a valid mixing sheet. Expected: a new sheet number is assigned and the saved sheet shows the selected formula, date, miller, batch configuration, calculated ingredient usage, and bag count.
- [ ] Use mixing-sheet navigation to open the new sheet and older sheets. Expected: the displayed record matches the selected sheet and navigation boundaries work.
- [ ] Print a saved mixing sheet. Expected: the print layout contains the sheet and relevant production details without application navigation or screen-only controls; a print action is recorded in Audit Logs.
- [ ] Open Materials Used for the new sheet. Expected: calculated bags and ingredient quantities match the mixing sheet.
- [ ] Enter actual bags and actual ingredient usage. Expected: bag and ingredient variances update as actual minus calculated, including positive and negative results; totals equal the sum of their rows.
- [ ] Save the variance record. Expected: the values persist when the sheet is reopened and the success message is shown.
- [ ] Reopen a fully recorded sheet. Expected: it is read-only and cannot be edited again.
- [ ] Try saving missing, malformed, negative, or out-of-range variance values. Expected: invalid values are rejected and no partial variance update is saved.
- [ ] Use Materials Used navigation. Expected: first/previous/next/last record controls open the correct sheet and show the correct position/count.

## Items Sold Separately

- [ ] Record a sale with a valid date, ingredient, positive whole-kilogram quantity, and Local destination. Expected: it saves and is visible in sales history.
- [ ] Record another sale using Overseas destination. Expected: it saves with the overseas destination and can be distinguished in its saved record.
- [ ] Try a future date, missing required field, or invalid quantity. Expected: the entry is rejected and no invalid sale is added.
- [ ] Navigate new and saved sales records. Expected: the selected sale's date, ingredient, quantity, and destination are displayed; saved sales are read-only.

## Reports and Exports

- [ ] For each report, apply a valid date range containing known test records. Expected: only records within the inclusive start/end dates are included.
- [ ] Feed Production Report: compare each formula's actual kilograms and tons with saved actual production usage. Expected: percentages and grand totals are arithmetically correct; grand-total percentage is 100% when records exist.
- [ ] Raw Materials Report: compare a test ingredient's figures. Expected: opening stock + receipts - production usage - separately sold quantity = computed balance; variance = physical stock - computed balance. Totals agree with the displayed ingredient rows.
- [ ] Items Sold Separately Report: compare dates and ingredient quantities with saved local and overseas sale records. Expected: quantities are grouped by date and ingredient and grand totals agree.
- [ ] Production Summary Report: compare sheet details, formula subtotals, and grand totals with mixing and variance records. Expected: target usage, actual usage, usage variance, actual bags, and bag variance are correctly grouped and summed.
- [ ] Use a date range with no matching records. Expected: an explicit empty-state message appears and totals are zero.
- [ ] Enter malformed dates or a start date later than the end date. Expected: a validation message appears and the report does not silently present a misleading range.
- [ ] Export each report to PDF. Expected: a readable PDF downloads with the selected date range, matching rows, totals, and no clipped columns; an export action appears in Audit Logs.

## Administration and Audit

- [ ] As an admin, add an account with a unique username, required profile details, role, and password. Expected: the account appears in the list and can sign in with the assigned role.
- [ ] Try to create a duplicate username or omit required account data. Expected: the request is rejected and no duplicate/invalid account is created.
- [ ] Edit an account's profile, role, active status, and (where supported) password. Expected: saved changes are reflected; an inactive account cannot sign in; changed role permissions take effect on the next authenticated request.
- [ ] Search accounts and navigate account-list pages. Expected: matching records, counts, and pagination are correct.
- [ ] As a supervisor or user, open User Accounts directly. Expected: access is denied.
- [ ] As an admin or supervisor, add a miller with a supported job position. Expected: the miller appears in the roster and active receiving/mixing selectors.
- [ ] Edit a miller's name, position, or active status. Expected: changes are reflected; inactive millers are omitted from new operational selections while historical records remain viewable.
- [ ] Search millers and navigate roster pages. Expected: matching records, counts, and pagination are correct.
- [ ] As a non-admin, open Audit Logs directly. Expected: access is denied.
- [ ] As an admin, inspect Audit Logs after login, record creation/update, print, report export, logout, and timeout checks. Expected: relevant actor, action, timestamp, IP address, and details are recorded.
- [ ] Filter audit records by search term, date range, and action type. Expected: only matching entries are shown and filter state is retained while paging.
- [ ] Export the filtered audit log to CSV. Expected: the downloaded CSV has column headers and only matching records; the export is recorded in the audit log.

## Cross-Workflow Data Consistency

- [ ] Create an ingredient, include it in an active formula, create a mixing sheet, record its actual usage, and open the reports. Expected: the same ingredient/formula and quantities flow through each applicable screen and report.
- [ ] Create an order, receive a test ingredient, and open the Raw Materials Report for that date range. Expected: the received amount appears under receipts.
- [ ] Record a separate sale, then open the Raw Materials Report and Items Sold Separately Report. Expected: the sale appears in the sales report and is included in raw-material usage.
- [ ] Save a physical count after recording receipts/usage. Expected: the Materials Report uses the appropriate opening and latest physical-stock snapshots for the selected period.
- [ ] Refresh affected lists and reports after saving each record. Expected: data remains persisted and duplicate submissions are not created by refresh/navigation.

## Issues and Notes

| Test / section | Result | Record IDs or steps to reproduce | Notes |
| --- | --- | --- | --- |
|  |  |  |  |
|  |  |  |  |
|  |  |  |  |