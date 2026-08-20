# Rework Inventory Question Bank Workflow & Fix Default Options

## Goal
Fix the broken `default_options` feature for Multiple Choice (Unscored) categories, and reorganize the Staff UI so questions are added directly inside their respective Sub-Categories (when sub-categories exist) rather than in a flat list with a dropdown.

## Part 1: Fix `default_options`
**Findings:** 
1. **Saving Category Default Options:** The `default_options` field itself actually saves correctly to the database on the backend.
2. **Student Rendering:** The student form properly falls back to `$data->default_options` if an item's options are truly `null`.
3. **The Root Cause:** The bug lies in the Staff UI's "Custom options" checkbox. When a staff member unchecks "Custom options", the AlpineJS frontend simply hides the textarea but **does not clear its value**, and the checkbox state itself is never sent to the server. Consequently, the server blindly saves the hidden text as custom options, and the student form never falls back to the default options because the item still technically has options saved in the database!

**Proposed Changes:**
- In `resources/views/staff/question-bank/edit.blade.php` and `create.blade.php`:
  - Add a hidden input or send the `useCustomOptions` boolean state to the server.
  - Or simply add `@change="if(!item.useCustomOptions) item.options = ''"` to the checkbox so that unchecking it clears the data before submission.
- In `QuestionBankController.php`:
  - Listen for the `useCustomOptions` boolean field in the request payload. If it's false, explicitly set the item's options to `null` regardless of whether the text field had leftover data.

## Part 2: Rework Sub-Category Workflow
**Proposed Changes:**
1. **Model/Data Structure:** No changes. Data integrity of DASS21/CAT is preserved.
2. **Staff Views (`edit.blade.php`, `create.blade.php`):**
   - **For Categories without Sub-Categories (e.g. ERQ):** 
     - The Questions tab will render exactly as it does today (a flat list of items).
   - **For Categories with Sub-Categories (e.g. DASS21):**
     - We will group the UI by Sub-Category.
     - We'll iterate through `subcategories`, creating a visual section/card for each.
     - Inside each Sub-Category section, we'll render the items assigned to it (`item.question_subcategory_id == sub.id`).
     - Each Sub-Category section will have its own "Add Question" and "Bulk Add" buttons that automatically set the `question_subcategory_id` for the new items.
     - The "Sub-Category" dropdown on individual items will be removed, since they are structurally grouped.
     - An "Ungrouped" section at the bottom will catch any items that lack a `question_subcategory_id`.
   - **Sub-Categories Tab:**
     - Add an "Add Question" button next to each sub-category row that switches to the Questions tab and automatically adds a new item to that sub-category.
   - **Item Numbering:**
     - We will maintain the existing behavior where the "Item #" is manually editable (or auto-incremented globally). For DASS21, items are numbered 1-21 across all sub-categories, so we must **not** scope auto-incrementing per sub-category. 

## Open Questions
- Is adding `@change="if(!item.useCustomOptions) item.options = ''"` to the checkbox sufficient to clear the custom options, or would you prefer I add a `use_custom_options` boolean field to the backend validation to enforce this securely on the server side?

## User Review Required
Please review the proposed approach for reorganizing the sub-categories. If you approve, I will proceed with the implementation.
