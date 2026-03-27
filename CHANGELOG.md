# Changelog

All notable changes to this project will be documented in this file.

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
