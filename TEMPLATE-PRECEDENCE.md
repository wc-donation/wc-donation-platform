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

Use this tier when you need donation-specific customizations separate from regular WooCommerce templates.

### Tier 2: Theme WooCommerce Overrides (Configurable)

When theme has WooCommerce overrides but no explicit WCDP override, precedence is configurable.

**Default:** Theme templates win (backward compatible)

**Admin Setting:** WooCommerce → Settings → Donations → Template Override Precedence
- "Respect theme customizations" (default)
- "Force donation features"

**Filter for Per-Template Control:**

```php
add_filter('wcdp_template_override_precedence', function($mode, $template_name) {
    // Force WCDP for checkout templates only
    if (strpos($template_name, 'checkout/') === 0) {
        return 'plugin';
    }
    return 'theme';  // Theme wins elsewhere
}, 10, 2);
```

**Filter Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$mode` | string | Current mode: `'theme'` or `'plugin'` |
| `$template_name` | string | Template path (e.g., `'checkout/payment.php'`) |
| `$template` | string | Full path to theme template |
| `$plugin_template` | string | Full path to WCDP template |
| `$namespace` | string | `'woocommerce'` or `'woocommerce-subscriptions'` |

### Tier 3: No Theme Override (Default)

When theme has no WooCommerce override, WCDP template is used automatically.

## Backward Compatibility

Fully backwards compatible. Existing installations work unchanged, continuing the existing "theme wins" approach out of the box.

## Configuration Examples

### Simple: Enable Donation Features Globally

If donation features are broken by theme templates:

**Admin:** WooCommerce → Settings → Donations → Template Override Precedence → "Force donation features"

**Code:**
```php
update_option('wcdp_template_override_precedence', 'plugin');
```

### Advanced: Selective Template Control

Force WCDP for checkout, keep theme for everything else:

```php
add_filter('wcdp_template_override_precedence', function($mode, $template_name) {
    $checkout_templates = ['checkout/form-login.php', 'checkout/payment.php', 'checkout/form-checkout.php'];
    return in_array($template_name, $checkout_templates) ? 'plugin' : 'theme';
}, 10, 2);
```

### Per-Site Configuration (Multisite)

```php
add_filter('wcdp_template_override_precedence', function($mode, $template_name) {
    return (get_current_blog_id() === 5) ? 'plugin' : 'theme';
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
