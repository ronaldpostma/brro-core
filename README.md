# Brro Core — Reference (Cursor / @Docs)

> **Purpose:** This file is the canonical reference for the **Brro Core** WordPress plugin (`brro-core`). Add it to **Cursor → Docs** so the assistant understands what this plugin provides, what body classes are available, what functions exist, and — critically — what is legacy and should no longer be used in new theme code.
> **Stack:** PHP, WordPress hooks/filters, jQuery (admin only). No frontend JS framework.

---

## Document metadata

| Key | Value |
|-----|-------|
| `plugin_slug` | `brro-core` |
| `text_domain` | `brro-core` |
| `current_version` | 2.1.4 |
| `function_prefix` | `brro_` |
| `option_prefix` | `brro_` |
| `active_on` | Every Brro WordPress project |

---

## What Brro Core is

Brro Core is the universal utility plugin present on every site built by Brro. It has three distinct roles:

1. **Body class system** — adds role and context classes to the `<body>` tag for use in theme CSS
2. **WordPress admin improvements** — login page customization, menu reordering, editor UX, comments/XML-RPC disabling, private mode
3. **Developer tools** — CSS clamp calculator popup (Brro Calc), dev site badge, toolbar toggle

It is **not** a theme framework. It provides utility infrastructure that the theme builds on top of.

---

## Body class system — IMPORTANT for theme code

Brro Core adds the following classes to the frontend `<body>` tag automatically. Use these in theme CSS to show, hide, or style elements differently based on user role or page context. **Never add these manually — they are injected by the plugin.**

### Role-based classes

| Class | When applied |
|-------|-------------|
| `webadmin` | Current user is a WordPress administrator |
| `webeditor` | Current user ID is in the `brro_editors` option |
| `guest` | User is not logged in |

### Page-context classes

| Class | When applied |
|-------|-------------|
| `parent` | Current hierarchical post has no parent |
| `child` | Current hierarchical post has a parent |
| `not-hierarchical` | Current post type is not hierarchical |
| `featuredimg-set` | Current single post has a featured image |

### Usage example in theme CSS

```css
/* Hide admin-only elements from guests */
.guest .admin-only-element {
    display: none;
}

/* Show editor toolbar only for editors and admins */
.webeditor .editor-controls,
.webadmin .editor-controls {
    display: flex;
}
```

---

## Available PHP functions

These functions are globally available in any theme template once Brro Core is active.

### `brro_is_elementor_active()`
Returns `true` if Elementor is active, `false` otherwise. Useful for conditional logic in themes that may or may not use Elementor.

```php
if ( brro_is_elementor_active() ) {
    // Elementor-specific code
}
```

### `brro_is_dev_site_subdomain()`
Returns `true` if the current site is running on a dev/stage/staging/test subdomain. Useful for conditional output or debugging.

```php
if ( brro_is_dev_site_subdomain() ) {
    // Dev-only output
}
```

---

## Available shortcodes

### `[navburger]`
Outputs a CSS/JS animated hamburger icon for navigation.

```
[navburger style="60px 40px 8px 3px #ffffff #BE8845"]
```

Parameters (space-separated in `style`):
1. Width
2. Height
3. Bar height
4. Bar border-radius
5. Bar color (default state)
6. Bar color (hover/open state)

---

## ACF functions — LEGACY, do not use in new code

> ⚠️ **Important:** The following ACF-related functions exist in Brro Core for backwards compatibility with older projects. **Do not use them in new theme code.** In all new projects, use ACF's native `get_field()` directly. This decision was made to stay as close as possible to the core ACF plugin for long-term maintainability.

### `brro_get_cached_acf_field( $field_name, $post_id )`
Retrieves an ACF field with transient caching (12 hours). **Legacy only.** Not used in new projects.

### `[acfcontent]` shortcode
Displays an ACF field value in non-PHP contexts.
```
[acfcontent field="field_name" before="<span>" after="</span>"]
```
**Legacy only.** Not used in new projects.

### ACF cache clearing
Cache is automatically cleared on post save via `updated_post_meta`, `added_post_meta`, and `deleted_post_meta` hooks. No action needed from theme code.

---

## Admin features (background — theme code does not need to interact with these)

These features are controlled by the Brro Core settings panel (`/wp-admin/admin.php?page=brro-plugin-settings`) and run automatically. They do not require any theme-level code.
| Feature | What it does |
|---------|-------------|
| **Private mode** | Redirects non-logged-in visitors to login or a custom URL |
| **Preview access** | Cookie-based preview link (`/preview`) for clients |
| **Login customization** | Custom logo, background, and colors on `wp-login.php` |
| **Admin menu reordering** | Custom menu order and custom separators in wp-admin |
| **Editor menu restrictions** | Removes specified menu pages for editor-role users |
| **Comments off** | Disables comments site-wide including REST endpoints |
| **XML-RPC off** | Disables XML-RPC completely |
| **jQuery check** | Ensures jQuery is enqueued on the frontend |
| **Dev site badge** | Red "DEVELOPMENT" badge on dev/stage/test subdomains |
| **Admin color scheme** | Forces "sunrise" color scheme on dev subdomains |
| **Toolbar toggle** | Fixed button for admins to toggle the WP toolbar on frontend |
| **Excerpt on pages** | Enables excerpt field on pages post type for SEO use |
| **Posts menu rename** | Allows renaming the "Posts" menu item and icon |

---

## Developer tool: Brro Calc

Brro Calc is a CSS `clamp()` calculator popup available in wp-admin (admins only, developer mode on). It generates fluid `clamp()` values for desktop, tablet, and mobile based on breakpoint settings configured in Brro Core.

**This tool generates the `clamp()` values that go into the theme's stylesheets.** It is a developer utility — it does not output anything on the frontend.

The clamp formula uses configurable breakpoints:
- Desktop: start (default 1024px) → end (e.g. 1600px), reference size (default 1440px)
- Tablet: start (default 768px) → end 'Desktop start -1', reference size (default 768px)
- Mobile: start (default 320px), → end 'Tablet start -1', reference size (default 360px)

## What Cursor should do when responsive sizing is needed

1. **By default, write pixel values** — use the px values from Figma/Design directly unless told otherwise.
2. **Only use clamp() when the user explicitly asks for it** — phrases like 'make this responsive with brro-calc', 'use clamp() from brro-calc', or 'calculate responsive sizing with brro-calc' etc are examples of the trigger.
3. **When asked, use the Node.js formula** in the `@brro-calc` with the project's breakpoint settings — never estimate a clamp() value.
4. **Include the reference comment** on every clamp() value, also shown in `@brro-calc` doc: `/*487px @ 1440*/`
5. **Calculate all values in one pass** when building a new section — don't calculate one at a time.

**Brro Calc generates clamp() values** for responsive sizing on request. Only calculate clamp() values when the user explicitly asks for responsive sizing.

- **Library source**: `https://raw.githubusercontent.com/ronaldpostma/brro-core/main/brro-core/js/brro-core-css-calculator-lib.js`
- **Docs**: See `@brro-calc` for usage instructions, breakpoint settings, and the Node.js calculation formula.

---

## Settings options (wp_options keys)
| Option key | Default | Description |
|------------|---------|-------------|
| `brro_editors` | `2,3,4,5` | Comma-separated user IDs treated as editors |
| `brro_private_mode` | `0` | Enable site-wide private mode |
| `brro_private_mode_redirect` | `''` | URL to redirect non-logged-in users |
| `brro_private_redirect_exceptions` | `''` | Newline-separated paths exempt from private mode |
| `brro_comments_off` | `0` | Disable comments site-wide |
| `brro_xmlrpc_off` | `0` | Disable XML-RPC |
| `brro_developer_mode` | `0` | Enable developer tools (Brro Calc, Elementor tools) |
| `brro_admin_css_all` | `1` | Load admin CSS for all backend users |
| `brro_admin_css_editors` | `1` | Load editor-specific admin CSS |
| `brro_client_help_url` | `''` | URL for custom help menu item |
| `brro_client_help_menutitle` | `''` | Label for custom help menu item |
| `brro_login_backgroundmain` | gradient | Login page background |
| `brro_login_sitelogo` | brro.svg | Login page logo URL |
| `brro_change_posts_menu` | `0` | Enable Posts menu rename |
| `brro_posts_menu_title` | `Articles` | Custom Posts menu label |
| `brro_restrict_editor_access` | `0` | Block editor login during maintenance |

---

## What Cursor should know when working with theme code

1. **Body classes `webadmin`, `webeditor`, `guest`, `parent`, `child`, `not-hierarchical`, `featuredimg-set` are always available** on the frontend. Use them directly in CSS — no PHP needed.
2. **Do not use `brro_get_cached_acf_field()` or `[acfcontent]` in new code.** Always use `get_field()` directly.
3. **Brro Core enqueues jQuery on the frontend.** You do not need to enqueue it again in the theme.
4. **All admin improvements are automatic.** No theme code needs to call or interact with admin functions.

---

## Related files in the plugin
| File | Content |
|------|---------|
| `brro-core.php` | Main plugin file, enqueues, update mechanism |
| `php/brro-core-global.php` | Frontend body classes, ACF utilities (legacy), shortcodes, toolbar toggle, dev badge |
| `php/brro-core-admin.php` | Admin customizations, private mode, login CSS, menu tools |
| `php/brro-core-settings.php` | Settings page registration and fields |
| `js/brro-core-css-calculator-lib.js` | Shared clamp calculation library |
| `js/brro-core-css-calculator-script.js` | Brro Calc popup logic |
| `js/brro-core-elementor-editor-script.js` | Elementor editor clamp converter (developer mode only) |
| `css/brro-core-wp-admin-style.css` | Admin styles for all backend users |
| `css/brro-core-wp-admin-editors-style.css` | Admin styles for editor-role users |

---

*End of Brro Core reference for Cursor @Docs.*