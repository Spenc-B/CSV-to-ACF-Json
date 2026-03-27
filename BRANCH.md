# Feature: Saved Configuration Templates

## Why
When working with similar CSVs repeatedly (e.g. competitor data exports that evolve over time), you end up reconfiguring the same field types, labels, and settings each time. Saving a template lets you re-apply a known-good configuration to a new or updated CSV instantly.

## What This Branch Will Do
- Add "Save Template" button in Step 2 that stores the current field configuration (types, labels, required, instructions) as a named template
- Templates stored in a WordPress option (serialized array)
- Add "Load Template" dropdown that applies a saved template to the current fields, matching by field name
- Unmatched fields (new columns in the CSV) keep their auto-detected settings
- Template management: list, rename, delete saved templates

## Acceptance Criteria
- [ ] Save current field configuration as a named template via AJAX
- [ ] Load template dropdown populates from saved templates
- [ ] Loading a template matches fields by name and applies saved settings
- [ ] New/unmatched fields retain auto-detected defaults
- [ ] Template management UI for rename and delete operations
- [ ] Templates persist across sessions (stored in wp_options)
