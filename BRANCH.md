# Feature: ACF JSON to CSV (Reverse Export)

## Why
Sometimes you already have an ACF field group and want to edit its structure in a spreadsheet, then re-import. A round-trip workflow (ACF JSON > CSV > edit > ACF JSON) makes bulk field management much faster than clicking through the ACF UI for 20+ fields.

## What This Branch Will Do
- Add an "Export" tab or mode to the plugin page
- Allow uploading an existing ACF JSON export file (or selecting from registered field groups if ACF is active)
- Parse the field group JSON and generate a CSV where:
  - Row 1 = category/tab groupings (derived from Tab fields)
  - Row 2 = field names
  - Columns include: field name, label, type, required, instructions, choices (if any)
- Download the CSV for editing in Excel/Google Sheets
- The exported CSV is fully compatible with re-importing back through the plugin

## Acceptance Criteria
- [ ] Upload ACF JSON file or select existing field group
- [ ] JSON parsed into flat field list, respecting tab groupings
- [ ] CSV generated with category row + field name row + metadata
- [ ] Downloaded CSV can be re-imported through Step 1 with no manual edits
- [ ] Choice fields export their options as comma-separated values in a column
