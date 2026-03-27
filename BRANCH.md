# Feature: Choice Options from Sample Data

## Why
When a CSV column only contains a small set of unique values (e.g. 3-8 distinct entries), it's almost certainly a Select, Radio, or Checkbox field. Currently the plugin detects the *type* but leaves the choices empty — the user has to manually add them in ACF after import.

## What This Branch Will Do
- During CSV parsing, collect all unique values per column from the data rows
- If a column has a small number of unique values (default threshold: 15), auto-suggest it as a Select/Radio field and pre-populate the choices
- Show detected choices in the Step 2 configuration table with the ability to edit/remove them
- Pass choices through to the JSON generator so they appear in the exported ACF field definition

## Acceptance Criteria
- [x] Unique values extracted during upload and returned to the JS
- [x] Fields with few unique values auto-set to `select` type with choices pre-filled
- [x] Choices editable in the configure step (add/remove/reorder)
- [x] Generated JSON includes `choices` array for select/radio/checkbox fields
