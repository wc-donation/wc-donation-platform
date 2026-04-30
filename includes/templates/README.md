# WCDP Template Hook Reference

Action hooks added to the WCDP template layer to allow addon plugins and custom code
to inject content at defined points within every donation form style.

---

## Available Hooks

All hooks pass two arguments: `$product_id` (int) and `$context` (string).

| Hook | File | Fires |
|------|------|-------|
| `wcdp_before_donation_form` | `wcdp_form.php` | Before any form style renders (all styles) |
| `wcdp_after_donation_form`  | `wcdp_form.php` | After any form style renders (all styles) |
| `wcdp_before_step_amount`   | `wcdp_step_1.php` | Before the amount picker form element |
| `wcdp_after_step_amount`    | `wcdp_step_1.php` | Above the Next/Donate button, after the amount fields |
| `wcdp_before_donor_details` | `wcdp_step_2.php` | Before billing/shipping fields render |
| `wcdp_after_donor_details`  | `wcdp_step_2.php` | After billing/shipping fields render |

### Context values

`$context` describes *where* the form is rendered, not which style is used.

| Context value | Where it appears |
|---------------|-----------------|
| `shortcode`   | `[wcdp_donation_form]` shortcode on any page, or placed via the block editor |
| `product-page`| WooCommerce single product page (regardless of style) |

All hooks fire across styles 1–5. The exception is `wcdp_before_donor_details` and
`wcdp_after_donor_details`, which do **not** fire for style 4 — that style only renders
the amount picker and redirects to the WooCommerce checkout page. To inject content
around the donor fields for a style 4 flow, use the standard WooCommerce checkout hooks
(`woocommerce_checkout_before_customer_details` / `woocommerce_checkout_after_customer_details`)
on the checkout page itself.

---

## Usage Example

```php
function my_addon_after_donor_details( $product_id, $context ) {
    echo '<div class="my-addon-field">Custom field here</div>';
}
add_action( 'wcdp_after_donor_details', 'my_addon_after_donor_details', 10, 2 );
```

To target a specific product:

```php
function my_addon_before_amount( $product_id, $context ) {
    if ( 42 !== $product_id ) {
        return;
    }
    echo '<p>This campaign ends June 30.</p>';
}
add_action( 'wcdp_before_step_amount', 'my_addon_before_amount', 10, 2 );
```

---

## Debug / Testing

Add to `wp-config.php` to enable visual hook markers on every donation form:

```php
define( 'WCDP_ADDON_HOOK_DEBUG', true );
```

When active, `includes/class-wcdp-hook-tests.php` is loaded and each hook renders
a banner showing the hook name, product ID, and context string.

---
