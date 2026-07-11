# B418 Telegram for Contact Form 7

A WordPress plugin by **branch418** that sends Contact Form 7 submissions to Telegram.

- **Multiple bots** — tokens are verified against the Telegram API on save.
- **Multiple chats** — private chats, groups, channels, and forum topics (thread IDs), with a built-in chat-ID detector.
- **Connections** — route any form to any bot and any number of chats.
- **Templates** — per-form messages using CF7-style `[field-name]` tags plus specials (`[all_fields]`, `[_form_title]`, `[_page_url]`, …), optional Telegram HTML formatting.
- **Testing & log** — test messages per chat/connection and a recent-activity log.

See `readme.txt` for the WordPress.org listing (installation, FAQ, changelog).

## Development

```bash
npm install
npm run build   # production bundle → dist/
npm run watch   # rebuild on change
```

The admin UI is Vue 3 (Vite, IIFE bundle) built on the `@branch418/shared` component library. The PHP side lives in `includes/`.

## Extending (Pro / third-party)

The plugin exposes a hook API; an add-on can extend it without touching core:

| Hook | Purpose |
|---|---|
| `b418_wp_tg_cf7_loaded` (action) | Boot an add-on after core init |
| `b418_wp_tg_cf7_should_send` | Veto a connection per submission (conditional routing) |
| `b418_wp_tg_cf7_placeholders` | Add template placeholders |
| `b418_wp_tg_cf7_message_text` | Filter the final message text |
| `b418_wp_tg_cf7_send_args` | Filter the Telegram `sendMessage` payload |
| `b418_wp_tg_cf7_after_send` (action) | React to each delivery (e.g. send attachments) |
| `b418_wp_tg_cf7_default_template` | Replace the built-in default template |
| `b418_wp_tg_cf7_collections` | Register extra stored collections |
| `b418_wp_tg_cf7_bootstrap_data` | Extend the admin app bootstrap payload |
| `b418_wp_tg_cf7_localize_data` | Extend data localized to the admin app |
| `b418_wp_tg_cf7_log_max_entries` | Change activity-log retention |