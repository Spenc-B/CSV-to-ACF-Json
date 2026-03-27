# Feature: Split into Multiple Field Groups

## Why
A single CSV with many categories currently generates one large field group with tabs. Sometimes it's better to split these into separate ACF Field Groups — each category becomes its own group. This gives more control over placement, ordering, and which post types each group applies to.

## What This Branch Will Do
- Add an option in Step 2: "One field group per category" vs "Single field group with tabs" (current default)
- When split mode is selected, each category generates an independent field group in the JSON output
- Each group gets its own key, title (from category name), and location rules
- Allow per-group location rule overrides in the UI
- JSON output becomes an array of multiple field groups instead of one

## Acceptance Criteria
- [ ] Toggle between single group (with tabs) and multiple groups (per category)
- [ ] Each generated group has a unique key and the category name as title
- [ ] Location rules can be set globally or per-group
- [ ] Downloaded JSON contains an array of field groups, all importable in one go
- [ ] Works correctly when no categories are present (falls back to single group)
