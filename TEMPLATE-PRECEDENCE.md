# WCDP Template Override Precedence System

## The Problem

When themes copy WooCommerce templates to `yourtheme/woocommerce/` for customization, and WCDP needs to override the same templates for donation features, a conflict arises. The old system had a simple "theme always wins" bailout that blocked WCDP donation features.

## Three-Tier Precedence System

### Tier 1: Explicit WCDP Theme Overrides (Highest Priority)

Create WCDP-specific template overrides that always take precedence:

**Location:** `yourtheme/wc-donation-platform/{namespace}/{template-path}`

```
yourtheme/
└── wc-donation-platform/
    ├── woocommerce/
    │   ├── checkout/form-login.php
    │   └── emails/customer-completed-order.php
    └── woocommerce-subscriptions/
        └── myaccount/my-subscriptions.php
```

Use this tier when you need donation-specific customizations separate from regular WooCommerce templates. Explicit overrides win regardless of the precedence mode.

### Tier 2: Theme WooCommerce Overrides (Configurable)

When the theme has a WooCommerce override but no explicit WCDP override, the `wcdp_template_override_precedence` filter decides:

| Mode | Behavior |
|------|----------|
| `'plugin'` (default) | The WCDP template wins |
| `'theme'` | The theme template wins, **except** for `single-product/*` templates |
| `'theme_force'` | The theme template always wins |

```php
add_filter('wcdp_template_override_precedence', function($mode, $template_name) {
    // Restore the legacy behavior: theme wins, except on single product pages.
    return 'theme';
}, 10, 2);
```

**Filter Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$mode` | string | Current mode, default `'plugin'` |
| `$template_name` | string | Template path (e.g., `'checkout/payment.php'`) |
| `$template` | string | Full path to theme template |
| `$plugin_template` | string | Full path to WCDP template |
| `$namespace` | string | `'woocommerce'` or `'woocommerce-subscriptions'` |

### Tier 3: No Theme Override (Default)

When the theme has no WooCommerce override, the WCDP template is used automatically.

## Backward Compatibility

The default `'plugin'` mode is a behavior change: WCDP templates now win over theme WooCommerce overrides in donation contexts (checkout, emails, single product donation form). Previously the theme won everywhere except `single-product/*`.

To keep the old behavior site-wide, set the filter to `'theme'`:

```php
add_filter('wcdp_template_override_precedence', function($mode) {
    return 'theme';
});
```

To make the theme win everywhere, including `single-product/*`:

```php
add_filter('wcdp_template_override_precedence', function($mode) {
    return 'theme_force';
});
```

## Configuration Examples

### Selective Template Control

Theme wins for checkout templates, WCDP elsewhere:

```php
add_filter('wcdp_template_override_precedence', function($mode, $template_name) {
    if (strpos($template_name, 'checkout/') === 0) {
        return 'theme';
    }
    return $mode;
}, 10, 2);
```

### Per-Site Configuration (Multisite)

```php
add_filter('wcdp_template_override_precedence', function($mode, $template_name) {
    return (get_current_blog_id() === 5) ? 'theme_force' : $mode;
}, 10, 2);
```

## Debugging

### Check Which Template is Used

```php
add_filter('wcdp_get_template', function($template, $template_name) {
    error_log("WCDP using: {$template} for {$template_name}");
    return $template;
}, 10, 2);
```
