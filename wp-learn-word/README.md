# WP Learn Word

Learn English vocabulary using the Leitner System. Supports multiple books, CSV import, shortcode frontend, and Zarinpal payments for premium access.

## Features
- Leitner System review (spaced repetition)
- Multiple books; assign words to books
- CSV import (word, translation, example)
- Shortcode-based UI: `[raswp_learn]`
- Paywall after X free unique words; upgrade via Zarinpal
- Admin settings for intervals, counts, and Zarinpal

## Installation
1. Copy the `wp-learn-word` folder to `wp-content/plugins/wp-learn-word`.
2. In WordPress Admin > Plugins, activate “WP Learn Word”.
3. Go to WP Learn Word > Settings and configure:
   - Words per session
   - Free words limit
   - Require login
   - Leitner intervals (days, comma-separated; e.g., `1,2,4,8,16`)
   - Zarinpal Merchant ID, Amount, Sandbox
4. Create Books and Words under WP Learn Word menu, or import via CSV.

## CSV Import
- Navigate to WP Learn Word > CSV Import.
- Choose an existing Book or create a new one.
- Upload a `.csv` file with columns:
  - Column A: word (required)
  - Column B: translation (required)
  - Column C: example (optional)
- UTF-8 encoding recommended.

## Shortcode
- Insert into any page or post:
```
[raswp_learn]
```
- The UI allows selecting a Book, starting a session, and marking “I knew it” or “I forgot”.

## Leitner Logic
- Boxes move up on correct, reset to 1 on wrong.
- Intervals come from Settings (`leitner_intervals`). Default: 1,2,4,8,16 days.
- The session fetch picks:
  - Due words for the user based on last reviewed and box interval
  - If not enough due words, fills with new words the user hasn’t seen

## Paywall & Zarinpal
- Users can review up to “Free words limit” unique words.
- After hitting the limit, the paywall prompts upgrade via Zarinpal.
- Configure Merchant ID and Sandbox in Settings.
- Callback handled at `?raswp_zarinpal_callback=1`.

## Developer Notes
- All functions/classes use `raswp_` prefix to avoid conflicts.
- Custom tables:
  - `wp_raswp_progress` for user-word Leitner progress
  - `wp_raswp_orders` for payment orders
- Custom post types:
  - `raswp_book`
  - `raswp_word` (meta: `raswp_translation`, `raswp_example`, `raswp_book_id`)
- Shortcode registered: `raswp_learn`.
- AJAX endpoints:
  - `raswp_get_words`
  - `raswp_update_progress`
  - `raswp_start_payment`

## Troubleshooting
- If CPT menus don’t show under WP Learn Word, re-save permalinks or re-activate the plugin.
- For Zarinpal sandbox, ensure you use a sandbox Merchant ID and environment.
- Check browser console/network for AJAX errors.

## License
GPL-2.0-or-later