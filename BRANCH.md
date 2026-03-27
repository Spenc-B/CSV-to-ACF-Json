# Feature: Excel (.xlsx) File Support

## Why
Most people working with spreadsheets use Excel or Google Sheets. Requiring a CSV export is an extra step that introduces encoding issues, delimiter confusion, and lost formatting. Native .xlsx support lets users upload directly from Excel without an intermediate export.

## What This Branch Will Do
- Add .xlsx to the accepted file types in the upload zone
- Bundle a lightweight PHP XLSX reader (e.g. SimpleXLSX or a custom ZipArchive-based parser — no Composer dependency)
- Parse the first sheet of the uploaded .xlsx file into the same row/column format as CSV
- All existing features (category row, field names, sample data, auto-detect) work identically with .xlsx input
- Handle merged cells in the category row (common in Excel for spanning headers)

## Acceptance Criteria
- [ ] Upload zone accepts .xlsx files alongside .csv/.tsv
- [ ] XLSX parsing extracts rows and columns from Sheet 1
- [ ] Merged cells in category row resolved correctly (fill span)
- [ ] All downstream processing (field detection, type guessing, sample preview) works identically
- [ ] No external Composer dependencies — parser is self-contained
- [ ] Graceful error if the file is corrupted or password-protected
