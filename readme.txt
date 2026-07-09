=== B418 Telegram for Contact Form 7 ===
Contributors: branch418
Tags: contact form 7, telegram, notifications, cf7, forms
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send Contact Form 7 submissions to Telegram. Multiple bots and chats, per-form routing, and fully customizable message templates.

== Description ==

B418 Telegram for Contact Form 7 relays every Contact Form 7 submission straight to Telegram — to a private chat, a group, a supergroup topic, or a channel.

**Features**

* **Multiple bots** — add any number of Telegram bots; tokens are validated against the Telegram API when saved.
* **Multiple chats** — private chats, groups, channels, and forum topics (thread IDs supported). A built-in helper detects chat IDs for you.
* **Flexible routing** — connect any form to any bot and any number of chats. One form can notify several chats; several forms can share one chat.
* **Message templates** — write a custom message per form using the same `[field-name]` tags you already use in CF7 mail, plus special tags like `[all_fields]`, `[_form_title]`, `[_page_url]`, `[_user_ip]`. Optional Telegram HTML formatting (bold, italic, links).
* **Test everything** — send a test message to a chat or fire a whole connection with sample data before going live.
* **Activity log** — see recent deliveries and errors right in the dashboard.

The plugin never blocks or alters your form flow: mail is sent exactly as before, Telegram delivery runs alongside it.

**Extensible by design**

Developers (and the upcoming Pro add-on) can extend the plugin via hooks: `b418_wp_tg_cf7_should_send`, `b418_wp_tg_cf7_message_text`, `b418_wp_tg_cf7_placeholders`, `b418_wp_tg_cf7_send_args`, `b418_wp_tg_cf7_after_send`, `b418_wp_tg_cf7_bootstrap_data`, and more.

== Installation ==

1. Install and activate Contact Form 7.
2. Install and activate this plugin.
3. Open **CF7 Telegram** in the admin menu.
4. Add a bot: create one with [@BotFather](https://t.me/BotFather) and paste its token.
5. Add a chat: invite the bot to your group/channel (or start a private chat with it), then use “Detect chats” or enter the chat ID manually.
6. Create a connection: pick a form, a bot, one or more chats, and (optionally) a template. Done — submissions now land in Telegram.

== Frequently Asked Questions ==

= Where do I get a bot token? =

Message [@BotFather](https://t.me/BotFather) on Telegram, send `/newbot`, and follow the prompts. BotFather replies with the token.

= How do I find my chat ID? =

Add your bot to the chat (for channels: as an administrator) and send any message there. Then, in **Bots & Chats → Detect chats**, pick the bot — the plugin lists every chat the bot has recently seen.

= Can one form notify several chats? =

Yes. A connection accepts any number of chats, and you can create any number of connections per form.

= Does it work with supergroup topics (threads)? =

Yes — set the optional thread ID on the chat.

= Does it change how CF7 sends email? =

No. Telegram delivery runs in addition to normal CF7 mail and never interrupts it.

== Changelog ==

= 1.0.0 =
* Initial release: multiple bots and chats, form-to-chat connections, per-form message templates, test sending, chat ID detection, activity log.
