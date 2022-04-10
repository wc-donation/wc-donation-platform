<?php
/**
 * Display Variation Settings
 *
 * @var bool $has_child
 * @var WC_product $product
 */

if (!defined ('ABSPATH')) exit;

if ($has_child) : ?>

	<input type="hidden" name="variation_id" id="variation_id" value="">
	<?php foreach ($attributes as $attribute => $options) :

		$esc_attribute = esc_attr (sanitize_title ($attribute));

		$terms = wc_get_product_terms (
				$id,
				$esc_attribute,
				array(
						'fields' => 'all',
				)
		);

		$attributeLayout = get_post_meta ($id, 'wcdp-settings[wcdp-attr-' . $esc_attribute . ']', true);

		/*
		 * $attributeLayout = 0: Default Layout
		 * $attributeLayout = 1: Box Layout
		 * $attributeLayout = 2: custom html
		 */
		?>
		<div class="variations wcdp_variation wcdp-row">
			<?php if ($attributeLayout != 2): //No Label for Custom HTML ?>
			<label class="wcdp-variation-heading" for="<?php echo $esc_attribute; ?>">
				<?php echo wc_attribute_label($attribute, $product); ?>
				<abbr class="required" title="<?php esc_html_e('required', 'wc-donation-platform'); ?>">*</abbr>
			</label>
			<?php else :
				//Display Custom HTML
				do_action ('wcdp_custom_html_' . $esc_attribute . '_' . $id);
			endif;

			//Display Box Layout
			if ($attributeLayout == 1):
				$args = array(
						'ul-id' => 'suggestion-' . $esc_attribute,
						'ul-class' => 'wcdp_options wcdp_su',
						'name' => $esc_attribute,
						'options' => array()
				);

				if (!empty($options)) {
					if ($product && taxonomy_exists ($attribute)) {
						// Get terms if this is a taxonomy - ordered. We need the names too.
						$terms = wc_get_product_terms (
								$id,
								$attribute,
								array(
										'fields' => 'all',
								)
						);

						foreach ($terms as $term) {
							if (in_array ($term->slug, $options, true)) {
								$args['options'][] = array(
										'input-id' => 'wcdp_value_' . $term->slug,
										'input-value' => $term->slug,
										'label-text' => esc_html(apply_filters('woocommerce_variation_option_name', $term->name, $term, $esc_attribute, $product)),
								);
							}
						}
					} else {
						foreach ($options as $option) {
							$args['options'][] = array(
									'input-id' => 'wcdp_value_' . esc_attr($option),
									'input-value' => esc_attr($option),
									'label-text' => esc_html(apply_filters('woocommerce_variation_option_name', esc_attr($option), $option, $esc_attribute, $product)),
							);
						}
					}
				}

				echo WCDP_Form::wcdp_generate_fieldset ($args, $product);
			endif;
			?>

			<div <?php if ($attributeLayout != 0) {
				echo 'style="display:none"';
			} //Hide for other Layouts
			?>>
				<?php wc_dropdown_variation_attribute_options (
						array(
								'options' => $options,
								'attribute' => $esc_attribute,
								'product' => $product,
						)
				); ?>
			</div>
		</div>

	<?php endforeach; ?>


<?php elseif (is_a ($product, 'WC_Product_Grouped')):
	/**
	 * Select between products for grouped products
	 */
	$args = array(
			'ul-id' => 'WCDP_ProductSettings',
			'ul-class' => 'wcdp_options',
			'name' => 'wcdp_products',
			'options' => array(),
	);
	$ids = $product->get_children ('edit');
	foreach ($ids as $id) {
		$products = wc_get_product($id);
		if ($products && $products->is_purchasable () && WCDP_Form::is_donable( $id )) {
			$args['options'][] = array(
					'input-id' => 'wcdp_value_' . $id,
					'input-value' => $id,
					'label-text' => esc_html($products->get_title()),
			);
		}
	} ?>
	<div class="wcdp-product-choice wcdp-row">
		<?php echo WCDP_Form::wcdp_generate_fieldset ($args); ?>
	</div>
<?php endif; ?>
