Pretty Price Input (Perfex CRM Module)

- Developer: FHC
- Version: 1.0.0
- Purpose: Format and normalize number inputs on sales documents to prevent decimal/thousand confusion when typing or pasting values.

Features
- Live formatting according to system decimal/thousand separators.
- Works on invoice, estimate, proposal item rates, quantity, discounts and adjustments.
- No core file modifications; assets are loaded via hooks.
- Keeps calculations accurate by normalizing values for calculate_total().

Installation
1. Copy the pretty_price_input folder into modules/.
2. In Perfex, go to Setup ➜ Modules and activate Pretty Price Input.

Notes
- Uses app.options.decimal_separator and app.options.thousand_separator.
- JavaScript file: modules/pretty_price_input/assets/js/pretty_price_input.js.


