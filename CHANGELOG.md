# Changelog

All notable changes to this project will be documented in this file.

## [1.4.0] - 2026-03-27

### Added
- **Repeater field detection**: Numbered column patterns (e.g. `arg_1_intel`, `arg_2_ops`) are auto-detected and grouped into ACF Repeater fields with sub-fields
- Repeater notice panel in Step 2 showing detected groups with sub-field details
- "Break Apart" button to revert any mis-detected repeater back to flat fields
- JSON generator produces valid ACF Repeater structure with `sub_fields`, `min`, `max`, table layout

## [1.3.0] - 2026-03-27

### Added
- **ACF JSON → CSV Export**: Reverse conversion mode — upload an ACF JSON file or select an existing field group and download a round-trip-compatible CSV
- Mode toggle to switch between CSV→JSON (import) and JSON→CSV (export)
- Export from existing ACF field groups when ACF is active
- Tab fields converted to category row for seamless re-import
- Metadata rows: field name, label, type, required, instructions, choices
- CSV preview table, download, and copy to clipboard
- Export options: include/exclude category row, delimiter selection

## [1.2.0] - 2026-03-27

### Added
- **Import Directly to ACF**: One-click button on Step 3 imports the generated field group straight into ACF — no file download/upload needed
- ACF active detection — import button only appears when ACF is installed and active
- Duplicate key protection — prevents importing a field group that already exists
- Direct edit link to the newly created field group after successful import

## [1.1.0] - 2026-03-27

### Added
- **Choice options from sample data**: Columns with 2–15 unique values are auto-detected as Select fields with choices pre-populated
- Editable choice tags UI in Step 2 — add, remove choices per field
- Choices row auto-shows/hides when switching between choice types (select, checkbox, radio, button group) and other types
- Choices passed through to generated JSON as ACF-compatible `key: value` pairs

## [1.0.0] - 2026-03-27

### Added
- Initial release
- 3-step wizard: Upload CSV → Configure Fields → Generate & Download JSON
- Auto-detect field types from column names and sample data
- Category row support with ACF Tab field generation
- Interactive field configuration table (labels, types, required, instructions, include/exclude)
- Bulk type apply and auto-detect reset
- Location rule configuration (post type, page template, options page)
- Full ACF field type support with proper type-specific defaults
- Drag & drop upload with delimiter options (comma, tab, semicolon)
- JSON preview, download, and copy to clipboard
