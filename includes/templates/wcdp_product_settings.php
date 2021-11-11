<?php
/**
 * Template for the "Donation Form" tab for editing products
 */
if(!defined('ABSPATH')) exit;

global $post;

?>

<div id="wcdp_donation_form_data" class="panel woocommerce_options_panel hidden">
	<div class="options_group">
		<p class="wcdp_shortcode form-field">
			<label for="wcdp_shortcode"><?php esc_html_e( 'Shortcode', 'wc-donation-platform' ); ?></label>
			<span class="wrap">
				<input type="text" readonly="readonly" onclick="this.select()" value="[wcdp_donation_form id=&quot;<?php echo $post->ID; ?>&quot;]">
				<?php
				/* translators: %s & %s: link html (not visible) */
				echo wc_help_tip( __( 'Add this shortcode where you want to display the donation form.', 'wc-donation-platform') . '<a href="https://wcdp.jonh.eu/documentation/getting-started/shortcode/" target="_blank" rel="noopener">'. __('Shortcode Documentation', 'wc-donation-platform') . '</a>' ); ?>
			</span>
		</p>
		<p class="wcdp_direct_link form-field">
			<label><?php esc_html_e( 'Direct Link', 'wc-donation-platform' ); ?></label>
			<span class="wrap">
				<a href="<?php echo esc_url(wc_get_checkout_url() . '?postid=' . $post->ID ); ?>" target="_blank"><?php echo esc_url(wc_get_checkout_url() . '?postid=' . $post->ID ); ?></a>
			</span>
		</p>
	</div>

	<?php
	$product = wc_get_product($post->ID);

		woocommerce_wp_select(
                     array(
						'wrapper_class'	=> '',
                        'id'        => 'wcdp-amount-layout',
						'class'		=> 'select short wcdp-selection',
						'label'		=> __( 'Layout of Amount suggestions', 'wc-donation-platform' ),
						'options'	=> array(
							'0'	=> __( 'Just input box', 'wc-donation-platform' ),
							'1'	=> __( 'Radio/button selection', 'wc-donation-platform' ),
							'2'	=> __( 'Input box + Range slider', 'wc-donation-platform' ),
							'3'	=> __( 'Expert: custom code with action: wcdp_custom_html_amount', 'wc-donation-platform' ),
						),
						'description' => __( 'Design of the amount field.', 'wc-donation-platform' ),
						'desc_tip'	=> 'true',
						'name'		=> 'wcdp-amount-layout',
						'value'		=> get_post_meta( $post->ID, 'wcdp-settings[0]', true ),
                    )
		);

	$values = json_decode( get_post_meta( $post->ID, 'wcdp-settings[1]', true ) );
	$options = array (
        '1'	=> '1',
        '2'	=> '2',
		'5'	=> '5',
		'10'	=> '10',
		'15'	=> '15',
		'20'	=> '20',
		'25'	=> '25',
		'30'	=> '30',
		'50'	=> '50',
		'75'	=> '75',
		'100'	=> '100',
		'150'	=> '150',
		'200'	=> '200',
		'250'	=> '250',
		'500'	=> '500',
		'750'	=> '750',
		'1000'	=> '1000',
		'1500'	=> '1500',);

	if (is_array($values)){
		foreach ($values as $value){
			if (is_numeric($value)) {
				$options[$value] = $value;
			}
		}
	}

	woocommerce_wp_select(
                        array(
							'wrapper_class'	=> get_post_meta( $post->ID, 'wcdp-settings[0]', true ) == 2 ? '' : 'hidden',
                            'id'        => 'wcdp-settings',
							'class'		=> 'attribute_values',
							'custom_attributes'	=> array (
								'multiple'			=> 'multiple',
								'data-placeholder'	=> "Select amounts"
							),
							'label'		=> __( 'Amount Suggestions', 'wc-donation-platform' ),
							'style'		=> 'width:95%',
							'options'	=> $options,
							'description' => __( 'Display buttons with donation amount suggestions. Create new options by entering a number (decimals seperated by period) and hit space bar.', 'wc-donation-platform' ),
							'desc_tip'	=> 'true',
							'name'		=> 'wcdp-settings[]',
							'value'		=> $values
                        )
	);

	if (is_a( $product, 'WC_Product_Variable' )) {
        $attributes = $product->get_variation_attributes();
        foreach ( $attributes as $attribute => $options ) {
            $attribute_name = esc_attr( sanitize_title( $attribute ) );

            woocommerce_wp_select(
                         array(
                            'wrapper_class'	=> 'show_if_variable',
                            'id'        => 'wcdp-attr-'.$attribute_name,
                            'class'		=> 'select short wcdp-selection',
                            'label'		=> __( 'Layout of ', 'wc-donation-platform' ) . wc_attribute_label( $attribute_name ),
                            'options'	=> array(
                                '0'	=> __( 'Default layout', 'wc-donation-platform' ),
                                '1'	=> __( 'Radio/button selection', 'wc-donation-platform' ),
                                '2'	=> __( 'Expert: custom code with action wcdp_custom_html_', 'wc-donation-platform' ) . esc_attr($attribute_name) . '_' . esc_attr($product->get_id()),
                            ),
                            'description' => __( 'Design of the variation selection. Only for variable products.', 'wc-donation-platform' ),
                            'desc_tip'	=> 'true',
                            'name'		=> 'wcdp-attr-'.$attribute_name,
                            'value'		=> get_post_meta( $post->ID, 'wcdp-settings[wcdp-attr-'.$attribute_name.']', true ),
                        )
            );
        }
	}

	?>

	<script>
		(function($) {
			$('#wcdp-settings').select2({
				tags: true,
				tokenSeparators: [',', ' '],
				createTag: function (params) {
					if (params.term === '' || isNaN(params.term) || params.term <=0) {
						return null;
					}
					return {
						id: params.term,
						text: params.term
					}
				}
			});

			$(window).bind("load", function() {
				show_hide_donable_panel();

				if ($('#_regular_price').val() == '') {
					$('#_regular_price').val(1);
				}
			});

			$('#wcdp-amount-layout,input#_donable').on('change', function(){
				show_hide_donable_panel();
			});

			function show_hide_donable_panel() {
				const is_donable = $('input#_donable:checked').length;
				if ( is_donable ) {
					$( '.show_if_donable' ).show();
				} else {
					$( '.show_if_donable' ).hide();
				}

				if($('#wcdp-amount-layout').val() == 1) {
					$('.wcdp-settings_field').show();
				} else {
					$('.wcdp-settings_field').hide();
				}
			}
		})(jQuery);
	</script>

</div>
