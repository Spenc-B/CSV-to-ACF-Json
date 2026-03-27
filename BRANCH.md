# Feature: Direct Import to ACF

## Why
Currently the workflow requires downloading the JSON file, then going to ACF > Tools > Import and uploading it again. This is an unnecessary extra step when ACF is already installed on the same site.

## What This Branch Will Do
- Detect whether ACF (Free or Pro) is active on the site
- Add an "Import Directly to ACF" button alongside Download/Copy on Step 3
- Use ACF's internal API (`acf_import_field_group()`) to register the field group directly into the database
- Show success/failure feedback with a link to the newly created field group in ACF
- If ACF is not active, the button is hidden and only Download/Copy are available

## Acceptance Criteria
- [ ] ACF availability detected and passed to the JS via `wp_localize_script`
- [ ] "Import to ACF" button appears only when ACF is active
- [ ] Clicking the button calls an AJAX handler that imports the field group via ACF API
- [ ] Success message includes a direct edit link to the new field group
- [ ] Error handling for duplicate group keys or import failures
