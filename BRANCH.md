# Feature: Conditional Logic Builder

## Why
Many ACF field groups use conditional logic — "show this field only when another field equals X". Currently the plugin generates fields with no conditional logic, requiring manual setup in ACF after import. A simple builder in Step 2 would save significant post-import configuration.

## What This Branch Will Do
- Add a "Conditions" column or expandable row per field in the Step 2 table
- Simple rule builder: "Show this field when [Field Dropdown] [equals/not equals] [Value]"
- Field dropdown auto-populated from the other fields in the current CSV
- Support AND/OR logic groups (matching ACF's conditional logic structure)
- Generate proper `conditional_logic` arrays in the JSON output

## Acceptance Criteria
- [ ] Each field row has an expandable "Add Condition" UI
- [ ] Condition builder shows field selector, operator, and value input
- [ ] Multiple conditions supported (AND within a group, OR across groups)
- [ ] Generated JSON includes `conditional_logic` arrays matching ACF's format
- [ ] Fields referenced in conditions use correct field keys
