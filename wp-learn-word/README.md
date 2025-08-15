# WP Learn Word (raswp)

Learn English vocabulary using the Leitner System. Import words by CSV, group them into Books, and gate usage with a free limit and Zarinpal payment to unlock full access.

## Features
- Leitner spaced-repetition review
- CSV import to `raswp_word` posts assigned to `raswp_book`
- Random session size configurable in settings
- Free review limit before upgrade
- Zarinpal payment (sandbox toggle)
- Shortcodes for front-end UI and upgrade button

## Installation
1. Upload the `wp-learn-word` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress > Plugins.
3. Go to WP Learn Word > Settings and configure:
   - Random words per session
   - Free review limit
   - Leitner box intervals (days)
   - Zarinpal Merchant ID, Amount, Sandbox

## Books and Words
- Create Books at WP Learn Word > Books
- Create Words at WP Learn Word > Words (each word has Translation, Example, and assigned Book)
- Or import many words via CSV

## CSV Import
- Go to WP Learn Word > Import CSV
- Select a Book and choose a `.csv` file
- Columns: `word,translation,example`
- First row header is optional; it will be skipped if it starts with `word`

Example:
```
word,translation,example
abandon,ترک کردن,He decided to abandon the plan.
accurate,دقیق,The report was accurate and detailed.
```

## Shortcodes
- `[raswp_leitner]` — render the learning UI
  - Optional: `book="book-slug"` or `book_id="123"` to filter words by Book
- `[raswp_upgrade]` — show the upgrade button

Place shortcodes into any page or post. Users must be logged in to study.

## Payment (Zarinpal)
- Set your Merchant ID in Settings
- Set Amount (IRR) and enable Sandbox for testing
- Users click “Upgrade Now” to be redirected to Zarinpal
- On success, they return and their account is granted premium access

## Leitner Logic
- Boxes are spaced by your configured day intervals, e.g. `1,3,7,14,30`
- Correct answers move a word up one box (max last box)
- Wrong answers reset the word to box 1
- Due words are those without progress or with `next_review_at` in the past

## Developer Notes
- Custom Post Types: `raswp_book`, `raswp_word`
- Tables: `{$wpdb->prefix}raswp_user_progress`, `{$wpdb->prefix}raswp_payments`
- All plugin functions are prefixed `raswp_` and classes `RASWP_`

## Troubleshooting
- If payments fail, verify Merchant ID and Sandbox setting
- Ensure permalinks are enabled and site URL is correct
- Check browser console and `wp-content/debug.log` for errors

## License
Commercial use permitted. No warranty. Modify as needed.