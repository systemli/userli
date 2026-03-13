# templates/ — Twig Templates

85 templates across 12 subdirectories.

## Base Template Hierarchy

Three base templates — every page extends one:

```
base.html.twig              # Root: HTML shell, dark mode, assets, navbar, flash messages
├── base_page.html.twig     # Full pages (account, admin, start)
│   Blocks: page_title, page_subtitle, page_content
└── base_step.html.twig     # Multi-step flows (registration, recovery, init)
    Blocks: step_icon, step_title, step_description, step_content, step_footer
```

**Choose the right base:**
- Dashboard, settings, list pages → extend `base_page.html.twig`
- Wizard flows, login, registration → extend `base_step.html.twig`
- Custom layout needed → extend `base.html.twig` directly

## Directory Map

| Directory | Templates | Purpose |
|-----------|-----------|---------|
| `Account/` | 14 | User self-service pages (password, aliases, 2FA, OpenPGP) |
| `Admin/` | 11 | Admin panel (users, domains, settings, maintenance) |
| `Alias/` | - | Alias management partials |
| `Email/` | - | Email notification templates |
| `Form/` | 1 | `fields.html.twig` — global Symfony form theme |
| `Init/` | - | First-run setup |
| `Recovery/` | - | Account recovery flow |
| `Registration/` | - | Registration multi-step |
| `Security/` | - | Login page |
| `Start/` | - | Landing/index page |
| `Voucher/` | - | Invite code pages |
| `bundles/` | - | Bundle template overrides |

## Shared Partials (root level)

- `_flashes.html.twig` — Flash message rendering
- `_navbar.html.twig` / `_navbar_desktop.html.twig` / `_navbar_mobile.html.twig` / `_navbar_tablet.html.twig` — Responsive nav
- `_navbar_user_menu.html.twig` — Authenticated user dropdown
- `_notifications.html.twig` — User notification display
- `_locale_switcher.html.twig` — Language picker
- `_delete_password_modal.html.twig` — Reusable delete confirmation modal

## Conventions

- **TailwindCSS** utility classes everywhere — no custom CSS classes
- **Heroicons**: `{{ ux_icon('heroicons:icon-name') }}` — not raw SVG
- **Translations**: All text via `{{ 'key'|trans }}` — never hardcoded strings
- **Dark mode**: All templates support dark mode via `dark:` Tailwind prefix
- **Responsive**: Mobile-first with `sm:`, `md:`, `lg:` breakpoints
- **Accessibility**: ARIA attributes, semantic HTML (`<nav>`, `<main>`, `<section>`)
- **User content**: Use `|safe_html` filter — NEVER `|raw`
- **Form theme**: `templates/Form/fields.html.twig` customizes all form widget rendering
