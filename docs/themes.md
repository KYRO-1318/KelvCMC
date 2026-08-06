# KelvCMC theme guide

The client portal is fully themeable through **CSS variables**. The admin panel (Filament) supports its own theme via the Filament theming system.

## How theming works

The client portal layout loads two stylesheets:

1. **The theme stylesheet** — a small CSS file under `public/css/themes/` that overrides the CSS variables.
2. **The compiled portal CSS** — `resources/css/app.css` (Tailwind + component classes) which reads those variables.

Switching the active theme is a **database setting** (`appearance.active_theme`), changeable from the admin panel (System → Themes) or via `php artisan tinker`.

## The design tokens

`resources/css/app.css` defines these variables (defaults):

```css
:root {
    --k-bg: #020617;              /* page background */
    --k-bg-soft: #0f172a;         /* secondary background */
    --k-bg-card: rgba(30, 41, 59, 0.45);  /* card background */
    --k-border: rgba(148, 163, 184, 0.14); /* borders */
    --k-text: #f1f5f9;            /* text */
    --k-text-muted: #94a3b8;      /* muted text */
    --k-accent: #8b5cf6;          /* primary accent */
    --k-accent-strong: #7c3aed;   /* darker accent (gradients) */
    --k-accent-soft: rgba(139, 92, 246, 0.14); /* tinted backgrounds */
    --k-success: #34d399;
    --k-warning: #fbbf24;
    --k-danger: #fb7185;
}
```

Everything else (buttons, badges, nav links, stats) is derived from these tokens, so a theme is usually **just 5 lines of CSS**.

## Creating a theme

1. Create `public/css/themes/mytheme.css`:

```css
/* My theme — sunset */
:root {
    --k-bg: #1c1917;
    --k-bg-soft: #292524;
    --k-accent: #fb923c;
    --k-accent-strong: #ea580c;
    --k-accent-soft: rgba(251, 146, 60, 0.14);
}
```

2. Register it in `config/themes.php`:

```php
'mytheme' => [
    'name' => 'Sunset',
    'description' => 'Warm amber dark theme.',
    'css' => 'css/themes/mytheme.css',
],
```

3. Activate it from **Admin → System → Themes** (or set `Setting::set('appearance.active_theme', 'mytheme')`).

## Theming the client layout further

- **Component classes** (`.card`, `.btn-primary`, `.nav-link`, `.badge`, `.input`, `.stat-card`) live in `resources/css/app.css` under `@layer components`. You can override them there or with your own CSS loaded after.
- Need full control? Fork the layout at `resources/views/layouts/client.blade.php` and the components in `resources/views/components/`.

## Theming the admin panel (Filament)

Filament 3 ships a built-in compiled theme with dark mode (enabled by default in `AdminPanelProvider`, brand colors set via `->colors([...])`). For full control you can build a custom Filament theme:

1. Run `composer install` first (the theme imports Filament's vendor CSS).
2. Edit `resources/css/filament/admin/theme.css` (it currently imports the Filament theme).
3. Uncomment `->viteTheme('resources/css/filament/admin/theme.css')` in `app/Providers/Filament/AdminPanelProvider.php` and add the file to `vite.config.js` inputs.
4. Rebuild: `npm run build`.

You may also add the Filament Tailwind preset to `tailwind.config.js` when customizing:

```js
import preset from './vendor/filament/filament/tailwind.config.preset'
export default { presets: [preset], /* … */ }
```

Set brand colors in `AdminPanelProvider`:

```php
->colors([
    'primary' => \Filament\Support\Colors\Color::Violet,
])
```

Next: [API](api.md) · [Plugins](plugins.md)
