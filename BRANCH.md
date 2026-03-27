# Feature: Validation Hints from Sample Data

## Why
ACF fields support validation properties like `maxlength`, `min`, `max`, and pattern constraints. By analyzing the actual data in the CSV, we can pre-populate these — saving the user from guessing or manually checking their data to set sensible limits.

## What This Branch Will Do
- Analyze sample data values during CSV parsing to derive validation hints:
  - **Text fields**: detect max string length, suggest `maxlength`
  - **Number fields**: detect min/max range, suggest `min` and `max`
  - **URL fields**: detect common patterns (e.g. all start with `https://`)
  - **Email fields**: detect common domains
- Show validation hints as editable values in an expandable row per field in Step 2
- User can accept, modify, or clear each hint before generation
- Hints written into the appropriate ACF field properties in the JSON output

## Acceptance Criteria
- [ ] Sample data analyzed for text length, numeric range, and common patterns
- [ ] Validation hints displayed per field in Step 2 (expandable detail row)
- [ ] Hints are editable — user can override before generation
- [ ] Generated JSON includes `maxlength`, `min`, `max`, `placeholder` from accepted hints
- [ ] Empty/cleared hints produce empty strings (ACF default behavior)
