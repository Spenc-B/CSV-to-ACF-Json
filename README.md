# CSV to ACF JSON

A WordPress plugin that converts CSV column headers into an ACF (Advanced Custom Fields) Field Group JSON file — ready for one-click import into ACF.

## What It Does

Instead of manually clicking through ACF's UI to create dozens of fields, upload a CSV that defines your structure and the plugin generates the import-ready JSON in seconds.

### Key Features

- **3-step wizard**: Upload → Configure → Download
- **Smart category/tab detection**: If your CSV has a category row (row 1) spanning multiple columns, the plugin generates ACF Tab fields to group related fields
- **Auto-detect field types**: Guesses field types from column names and sample data (e.g. `website` → URL, `email` → Email, `is_active` → True/False)
- **Choice options from data**: When a column has 2–15 unique values, auto-detects it as a Select field with choices pre-populated. Choices are editable — add, remove, or switch to radio/checkbox
- **Import Directly to ACF**: When ACF is active, a one-click import button appears on Step 3 — skip the download/upload and create the field group instantly. Includes duplicate protection and a direct edit link
- **Full field type support**: All ACF field types with correct default properties
- **Interactive configuration**: Rename labels, change types, toggle required, add instructions, include/exclude fields
- **Bulk actions**: Apply a field type to all fields at once, or reset to auto-detected types
- **Location rules**: Set post type or page template targeting
- **Drag & drop upload**: Or click to browse
- **JSON preview**: Syntax-highlighted preview before download
- **Copy to clipboard**: Quick copy for pasting into ACF's import

## CSV Format

### With Category Row (recommended)

Row 1 contains category groupings that span multiple columns. Row 2 has field names:

```
,, Threat Intelligence,,,, Alerting,,, Operations,,,
Competitor_Name, Competitor_Flaw, Comp_PII, Comp_IP, Comp_DNS, Comp_SSL, Comp_Alerts, Comp_Reporting, Comp_API, Comp_Ticketing, Comp_BillingSubs, Comp_Prospecting
```

Empty category cells are filled forward — so `Threat Intelligence` applies to all columns until the next non-empty category.

### Without Category Row

Just field names in row 1:

```
name, email, website, description, is_featured, price
```

## Installation

1. Upload the `csv-to-acf-json` folder to `wp-content/plugins/`
2. Activate the plugin
3. Go to **Tools → CSV → ACF JSON**

## Usage

1. **Upload** your CSV file (supports comma, tab, semicolon delimiters)
2. **Configure** each field: adjust labels, pick ACF types, mark required, add instructions
3. **Generate** and download the JSON file — or click **Import Directly to ACF** to create the field group instantly (ACF must be active)
4. Alternatively, in ACF go to **Tools → Import** and upload the downloaded JSON

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Advanced Custom Fields (Free or Pro) for importing the generated JSON

## License

GPL-2.0-or-later
