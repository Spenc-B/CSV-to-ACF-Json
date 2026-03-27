# Feature: Repeater Field Detection

## Why
CSVs often have numbered column patterns like `Arg_1_Intelligence`, `Arg_2_Ops`, `Arg_3_Growth` — these represent repeater sub-fields, not separate flat fields. Currently they get imported as individual text fields, requiring manual restructuring into a Repeater in ACF.

## What This Branch Will Do
- Detect numbered column patterns during CSV parsing (e.g. `prefix_1_suffix`, `prefix_2_suffix`)
- Group matched columns into a Repeater field with the common prefix as the repeater name
- The suffix becomes the sub-field name within the repeater
- Show the detected repeater structure in Step 2 with a visual nested layout
- Allow the user to confirm, break apart, or rearrange the repeater groupings
- Generate proper ACF Repeater JSON with `sub_fields` array

## Pattern Detection Examples
- `Arg_1_Intelligence`, `Arg_2_Ops`, `Arg_3_Growth` → Repeater "Arg" with sub-field per unique suffix, OR Repeater "Arg" with a single text sub-field (3 max rows)
- `phone_1`, `phone_2`, `phone_3` → Repeater "Phone" with one sub-field
- `feature_title_1`, `feature_desc_1`, `feature_title_2`, `feature_desc_2` → Repeater "Feature" with sub-fields "Title" and "Desc"

## Acceptance Criteria
- [ ] Numbered patterns detected and grouped during upload
- [ ] Repeater fields shown with nested sub-field display in Step 2
- [ ] User can break a repeater back into flat fields if the detection is wrong
- [ ] Generated JSON produces valid ACF Repeater with `sub_fields`, `min`, `max`
