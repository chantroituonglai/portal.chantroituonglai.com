### Line Item Discount – Upgrade Plan (for Pretty Price Input)

Status: Proposal for review
Owner: Module `pretty_price_input`
Scope: Invoices, Estimates, Proposals (admin forms, HTML previews, PDFs)

### Goals
- Add per-line discount (percent) on items for invoices/estimates/proposals.
- Reflect line-level discounts in calculated totals automatically without editing core files.
- Show discount percentage and discounted value next to each item in HTML/PDF.
- Store discounts persistently per item line for future edits and PDF rendering.

### Non-Goals (Phase 1)
- Fixed-amount per-line discounts (percent only in v1).
- Per-line before/after-tax choice (apply discount before tax in v1).
- Changing core tables or core JS; all logic remains in the module.

### Data Model (Module-owned)
- New table: `ppi_line_discounts`
  - `id` INT PK AI
  - `itemid` INT NOT NULL (references `tblitemable.id`)
  - `rel_type` ENUM('invoice','estimate','proposal') NOT NULL
  - `discount_percent` DECIMAL(10,2) NOT NULL DEFAULT 0.00
  - `created_at` DATETIME
  - `updated_at` DATETIME
  - Unique index on `(itemid)`

Creation: executed on module activation/migration inside `pretty_price_input` module. No changes to core tables.

### Admin UI/UX
- Inject a “Line discount %” input for each item row (both the preview row `.main` and existing table rows) using JS.
  - Input name conventions to capture on submit:
    - Existing rows: `items[ITEM_ID][ppi_discount_percent]`
    - New rows: `newitems[ROW_KEY][ppi_discount_percent]`
  - Lightweight inline control placed in the rate/amount cell (no layout break); responsive and theme-safe CSS.
- Recalculate totals when the discount changes (hooks into `calculate_total`).

### Totals Calculation (Client-side integration)
- Wrap `window.calculate_total` similarly to existing Pretty Price normalization:
  - Pre-step: for every row, compute `effective_rate = rate * (1 - discount_percent/100)` and temporarily set the row’s rate input to `effective_rate` (store original in `data-ppi-original-rate`).
  - Call the original `calculate_total` so core computes taxes/subtotals/total using discounted rates.
  - Post-step: restore the original displayed rate value (preserving Pretty Price formatting).
- This approach updates totals transparently without changing core logic.

### Persistence (Server-side via hooks)
- On add/update of invoice/estimate/proposal, use existing hooks to persist submitted per-line discounts into `ppi_line_discounts`:
  - Add: `after_invoice_added`, `after_estimate_added`, `proposal_created` (or `after_proposal_created` equivalent). Use the POST payload to map `newitems` to inserted records:
    - For updated rows (in future edits): use `items[ITEM_ID][ppi_discount_percent]` directly.
    - For newly inserted rows: fetch all `tblitemable` rows for `rel_id`/`rel_type` and correlate by `item_order` and the posted values (description, qty, rate, unit). Store `discount_percent` against resolved `itemid`.
  - Update: `invoice_updated`, `after_estimate_updated`, `after_proposal_updated` to upsert entries for edited items, remove entries for deleted items.
- No modifications to core models; all mapping/upserts occur in module hook callbacks.

### Rendering (HTML/PDF)
- Use filters provided by the items table to adjust per-item display:
  - `item_preview_rate`: append a short note like `(-X%)` to the rate cell when a discount exists.
  - `item_preview_amount_with_currency`: append per-line discount value below, e.g. `<br><small>Discount X% (-$Y)</small>`; keep safe HTML for PDF.
- Amount math for display uses:
  - Original stored rate = if DB rate is already discounted, reconstruct `original_rate = discounted_rate / (1 - X/100)`; otherwise use stored rate and X directly.
  - Discount value per line = `qty * rate * (X/100)` (before tax).

### Language
- Add new language keys under `modules/pretty_price_input/language/*/pretty_price_input_lang.php`:
  - `ppi_line_discount_label`: Line discount %
  - `ppi_line_discount_note`: Discount {percent}% (-{amount})

### Settings (optional v1)
- Module options (in DB `tbloptions`, namespaced `ppi_*`):
  - `ppi_enable_line_discount` (bool, default on)
  - `ppi_show_pdf_annotation` (bool, default on)
- Basic settings view under module (if needed) to toggle features.

### Files to Add (Module only)
- `modules/pretty_price_input/assets/js/ppi_line_discount.js`
  - Inject input controls; wrap `calculate_total`; read/write discount values.
- `modules/pretty_price_input/assets/css/ppi_line_discount.css`
  - Minimal styling for inline inputs/notes.
- Module PHP additions in `pretty_price_input.php`:
  - Activation: create table if not exists.
  - Head/Footer: enqueue new JS/CSS with cache-busting.
  - Hooks: add listeners for add/update/delete to persist discounts; add filters for item preview cells.

### Edge Cases & Notes
- If a user removes a line, clean up module table entries for that `itemid`.
- If taxes per item are hidden, totals still reflect discounts because rate is adjusted pre-calc.
- Backwards compatibility: Existing documents without discounts remain unaffected.
- Security: Sanitize numeric inputs, clamp percent to [0, 100].

### QA Acceptance Criteria
- Admin can set per-line discount percent on invoices/estimates/proposals.
- Changing per-line discount updates subtotal, taxes, and total correctly before save.
- Saved document re-opened shows the discount controls populated per line.
- Generated PDFs show “Discount X% (-$Y)” for each discounted line.
- No core files under `application/*` are edited; only module files are added/changed.

### Rollout
- Phase 1 (this plan): Percent-only, before-tax, per-line discount with PDF note.
- Phase 2 (below): Fixed-amount per-line, per-line before/after-tax mode, settings UI.

---

## Phase 2 Plan (for review)

### Goals
- Support per-line discount type: percent or fixed amount.
- Support per-line tax mode: before-tax (affects item rate) or after-tax (affects final total only).
- Provide module settings to enable/disable Phase 2 features and define totals behavior when mixing modes.

### Data Model Migration
- Alter table `ppi_line_discounts`:
  - Add `discount_type` ENUM('percent','amount') NOT NULL DEFAULT 'percent'
  - Add `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00
  - Add `tax_mode` ENUM('before_tax','after_tax') NOT NULL DEFAULT 'before_tax'
- Backfill existing rows to `discount_type='percent'`, `tax_mode='before_tax'`.
- Implement safe migration in module activation/upgrade path.

### Admin UI/UX Changes
- In each item row, add:
  - Type selector: `%` or `$` (amount) → names: `items[ID][ppi_discount_type]` or `newitems[K][ppi_discount_type]`.
  - Tax mode selector: `Before tax` or `After tax` → names: `items[ID][ppi_tax_mode]` or `newitems[K][ppi_tax_mode]`.
  - Discount value input: if type=`percent` use `%` input; if type=`amount` use currency input.
- Visual hints under amount cell reflect chosen mode: e.g., “Discount 10% before tax” or “Discount $5 after tax”.

### Totals Calculation Strategy (Client-side)
- Before-tax lines (percent or amount):
  - Convert into an effective rate before calling core `calculate_total`.
    - Percent: `effective_rate = rate * (1 - p/100)` (same as Phase 1).
    - Amount: `effective_rate = rate - (amount / qty)` (guard `qty>0`).
- After-tax lines (percent or amount):
  - Do not change rates; instead, compute an “after-tax line discount sum” post core calculation and apply via the document `adjustment` field (negative value), so taxes remain intact.
  - Maintain the user-entered `adjustment` by summing: `final_adjustment = user_adjustment + ppi_after_tax_total * (-1)`.
  - Store original user adjustment in a hidden data attribute to avoid drift while editing.

Computation details for after-tax lines per row:
- Compute line total including taxes from DOM post-calc:
  - `line_total_with_tax = (qty * rate + sum(taxes_on_line))` using tax rates read from each row (fallback: recompute from known tax percentages available in the row or item tax data if present).
- Apply discount:
  - Percent: `disc = line_total_with_tax * (p/100)`
  - Amount: `disc = min(amount, line_total_with_tax)`
- Sum over all after-tax rows to `ppi_after_tax_total`.
- Update totals UI and `input[name="adjustment"]` to reflect the combined value.

### Persistence (Server-side)
- Enhance save/update hooks to capture and store `ppi_discount_type`, `ppi_discount_percent`, `ppi_discount_amount`, and `ppi_tax_mode` for both existing (`items[ID]`) and new rows (`newitems[K]`).
- No core totals recalculation changes on server; for after-tax, totals are reflected via the (negative) document `adjustment` that the client-side has set.

### PDF/HTML Rendering
- For before-tax items: compute and display discount as in Phase 1 but extended for amount type.
- For after-tax items: compute per-line taxes in PHP (using `get_*_item_taxes` helpers) to derive `line_total_with_tax`, then calculate and display the discount (percent or amount) accordingly in the `item_preview_amount_with_currency` filter.
- Note label includes mode: e.g., “Discount 10% (after tax) (-$Y)”.

### Settings (Module)
- `ppi_enable_fixed_amount` (bool, default on)
- `ppi_enable_after_tax` (bool, default on)
- `ppi_adjustment_strategy` (enum: `combine_with_user_adjustment`, `separate_but_display_only`)
  - Default: combine by writing to `adjustment` input; preserves server parity.

### Edge Cases & Safeguards
- Amount-type with `qty=0`: do not divide; cap discount at `rate*qty`.
- Mixed before/after-tax lines: both strategies run; do not double count.
- User modifies `adjustment` manually: preserve user value and add module-computed after-tax discount on top; show tooltip indicating the auto-applied portion.

### QA Acceptance Criteria (Phase 2)
- Per-line type and tax mode controls work for new and existing rows.
- Before-tax lines alter subtotal/taxes/total correctly.
- After-tax lines reduce the final total (via negative adjustment) without changing taxes.
- Values persist after save and are reflected in PDF with correct notes and amounts.
- Settings toggle the features as expected.

### Implementation Steps
1. DB migration to add fields; backfill defaults.
2. Extend JS to add selectors and logic for type/mode; implement after-tax adjustment strategy safely.
3. Extend PHP hooks to store new fields and clean up on delete.
4. Enhance PDF/HTML filters to compute correct per-line discount display for both modes/types.
5. Add settings view and options registration.
