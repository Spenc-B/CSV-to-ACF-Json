# Feature: Type Hint Row in CSV

## Why
Auto-detection is good but not perfect. Power users who prepare CSVs in bulk want to specify field types directly in the spreadsheet rather than changing them one-by-one in the UI. A dedicated "type hint" row lets you define `url`, `textarea`, `true_false`, etc. right in the CSV.

## What This Branch Will Do
- Add a new upload option: "CSV includes a type hint row"
- When enabled, an additional row (row 2 with categories, or row 2 without) is treated as field type definitions
- Valid ACF type slugs in this row override auto-detection completely
- Invalid or empty cells fall back to auto-detection as normal
- The type hint row is excluded from sample data preview

## CSV Format Example
```
, Threat Intelligence,,,, Alerting,,,
Competitor_Name, Comp_PII, Comp_IP, Comp_DNS, Comp_Alerts, Comp_Reporting
text, true_false, text, text, select, textarea
Acme Corp, yes, 192.168.1.1, ...
```

## Acceptance Criteria
- [ ] New checkbox option in Step 1: "CSV includes a type hint row"
- [ ] Type hint row parsed and validated against known ACF field type slugs
- [ ] Valid hints override auto-detection; invalid/empty fall back gracefully
- [ ] Hint row excluded from sample data display
- [ ] Works with and without the category row option
