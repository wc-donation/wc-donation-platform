jQuery( function( $ ) {
    //Send donation selection form
    function wcdp_submit(step) {
        if (check_validity('#wcdp-ajax-send')) {
            $('#wcdp-spinner').show();
            $('#wcdp-ajax-button').hide();
            const formData = $("#wcdp-ajax-send").serialize();
            $.ajax({
                type: 'POST',
                url: $("#wcdp-ajax-send").attr('action'),
                data: formData
            })
                .done(function( response ) {
                    switch (response.success) {
                        case true:
                            $('.woocommerce-error').remove();
                            $( 'body' ).trigger( 'update_checkout' );
                            //$('.wcdp-button[value=2]').trigger("click");
                            $('#wcdp-ajax-button').show();
                            $('#wcdp-spinner').hide();
                            if (response.recurring) {
                                $('#createaccount').prop("checked", true).trigger('change');
                                $('.create-account:has(#createaccount)').hide();
                            } else {
                                $('.create-account:has(#createaccount)').show();
                            }
                            wcdp_steps(step);
                            break;
                        default:
                            error_message(response.message, response.reload);
                            break;
                    }

                })
                .fail(function() {
                    $('#wcdp-spinner').hide();
                    $('#ajax-unexpected-error').show();
                });
        }
    }

	// Return true if the donation form is filled in correctly
	function check_validity(id) {
		try {
			return $(id)[0].reportValidity() && ($('#variation_id').length == 0 || $('#variation_id').attr("value") != '');
		} catch(err) {
			return false;
		}
	}

    function error_message(message, reload) {
        if (!reload) {
            $('#wcdp-ajax-button').show();
        }
        $('#wcdp-spinner').hide();
        $('#wcdp-ajax-error').remove();
		$('form.checkout.woocommerce-checkout').prepend('<ul class="woocommerce-error" id="wcdp-ajax-error" role="alert"><li></li></ul>');
		$('#wcdp-ajax-error li').text(message);
    }

    $('#wcdp-ajax-send').on('submit', function(e){
		e.preventDefault();
		wcdp_submit('2');
	});

    //Submit step 1 form automatically for style 3
	let time = 0;
	$( '.wcdp-body > #wcdp-ajax-send' ).on('input blur keyup paste change', function (){
        time++;
        setTimeout(function() {
            time--;
            if (time == 0) {
                wcdp_submit();
            }
        }, 1300);
    });

	let ecpresstime = 0;
	let currentprice = 0;
	$( '.wcdp-body' ).on('input blur keyup paste change', function (){
		if (currentprice != $('#wcdp-donation-amount').val()) {
			$('.wcdp-express-amount').val($('#wcdp-donation-amount').val());
			currentprice = $('#wcdp-donation-amount').val();
			ecpresstime++;
			setTimeout(function() {
				ecpresstime--;
				if (ecpresstime == 0) {
					$(document.body).trigger('woocommerce_variation_has_changed');
				}
			}, 500);
		}
	});

    //Next and back buttons
	let currentStep = 1;
	$('.wcdp-button,.wcdp-step').click(function (){
		const step = $(this).attr('value');
		if (currentStep != 1) {
            wcdp_steps(step);
        } else if (step != 1) {
            wcdp_submit(step);
        }
    });

    function wcdp_steps(step){
		$(":root")[0].style.setProperty('--wcdp-step-2', 'var(--wcdp-main)');
        $(":root")[0].style.setProperty('--wcdp-step-3', 'var(--wcdp-main)');
        switch (step) {
            case '3':
                $("#wcdp-step-2").show();
                $( 'form.checkout' ).find( '.input-text:visible, select:visible, input:checkbox:visible' ).trigger( 'validate' );
                if ($('#wcdp-step-2 .woocommerce-invalid:visible').length > 0) {
                    $('#wcdp-invalid-fields').show();
                    $('#place_order').hide();
                } else {
                    $('#wcdp-invalid-fields').hide();
                    $('#place_order').show();
                }
                $(":root")[0].style.setProperty('--wcdp-step-3', 'var(--wcdp-main-2)');
            case '2':
                $(":root")[0].style.setProperty('--wcdp-step-2', 'var(--wcdp-main-2)');
            case '1':
                break;
            default:
                return;
        }
		$('.wcdp-style5-active').removeClass('wcdp-style5-active');
		$('#wcdp-style5-step-'+step).addClass('wcdp-style5-active');
        $("#wcdp-progress-bar").css('width', 33.33*(parseInt(step)-1)+'%');
        $(".wcdp-tab").hide();
        $("#wcdp-step-"+step).show();
        currentStep = step;
    }

	let express_heading_timeout = 10;
    //trigger selectWoo/Select2, Open modal when hash is #wcdp-form
    $(document).ready(function wcdp_setup() {
		$('.woocommerce-checkout select').selectWoo();
		$('.wcdp-loader').hide();
		$('.wc-donation-platform').css({"visibility": "visible", "animation-name": "wcdp-appear-animation", "animation-duration": "1s" });
		wcdp_open(false);
		try {
			if ($('.wcdp-choose-donation')[0].checkValidity()) {
				wcdp_submit();
			}
		} finally {
			$( '#wcdp-ajax-send,.wcdp_options' ).trigger('change');
			setTimeout(express_checkout_heading, express_heading_timeout);
		}
	});

	function express_checkout_heading() {
		if ($('#wc-stripe-payment-request-button').children().length + $('#ppc-button').children().length > 0) {
			$('.wcdp-express-heading').show();
		} else if (express_heading_timeout<10000) {
			express_heading_timeout = express_heading_timeout*2;
			setTimeout(express_checkout_heading, express_heading_timeout);
		}
	}

    //Modal window hash
    window.onhashchange = function(){
        wcdp_open(false);
    }

	$('.wcdp-modal-open').click(function() {
		wcdp_open(true);
	});

    //Close modal when excape is pressed
    $(document).on("keypress", "input", function (e) {
		if (e.key == "Escape") {
            wcdp_close();
        }
    });

    //Clode modal when clicking on the close button
    $('.wcdp-modal-close').click(wcdp_close);

    var wcdpOpen = false;
    //Close modal function
    function wcdp_close(){
    	if (wcdpOpen) {
			$('.wcdp-overlay').hide();
			$('body').css('overflow-y', ' auto');
			history.pushState("", document.title, window.location.pathname + window.location.search);
			wcdpOpen = false;
		}
    }

    //Open modal function
    function wcdp_open(direct){
		const x = $('.wcdp-overlay')
        if (direct || location.hash == '#wcdp-form' && x.length > 0) {
			x.show();
            $('body').css('overflow-y', 'hidden');
			wcdpOpen = true;
        }
    }

    //copy value of ul choices to corresponding input field
    $( '.wcdp_options' ).change(function() {
        var name = this.attributes['wcdp-name'].value;
        var value = $('input[name="'+name+'"]:checked').val();
        if (value) {
            $("#wcdp-"+name).val(value);
            $('#'+name).val(value);
            $('#'+name).trigger('change');
        }
    });

	$(document).on("change", "#wcdp_fee_recovery" , function() {
		setTimeout(function() {
			$( 'body' ).trigger( 'update_checkout' );
		}, 400);
	});
	$(document).on("change", "input[name='payment_method']" , function() {
		if ($('#wcdp_fee_recovery').prop('checked')) {
			setTimeout(function() {
				$( 'body' ).trigger( 'update_checkout' );
			}, 400);
		}
	});

    //copy value of range slider
    $( '#wcdp-range' ).on('input', function () {
        $('#wcdp-donation-amount').val($( '#wcdp-range' ).val());
        if ($( '#wcdp-range' ).val() == $( '#wcdp-range' ).attr('max')) {
            $('#wcdp-donation-amount').select();
        }
    });
    //copy value of amount input to range slider
    $( '.wcdp-amount-range-field' ).on('input', function () {
        $('#wcdp-range').val($( '#wcdp-donation-amount' ).val());
    });

    //Select the right donation suggestion field
    $('#wcdp-donation-amount').on('change', function(){
        var name = '#wcdp_value_' + $('#wcdp-donation-amount').val().replace(/./g, "-");
        //console.log($('#wcdp_value_' + ).prop("checked", true));
        if ($(name).length == 0) {
            $('#wcdp_value_other').prop("checked", true);
        } else {
            $(name).prop("checked", true)
        }
    });

    //Focus donation amount textfield when "other"-button is selected
    $( '#wcdp_value_other' ).click(function() {
        $('#wcdp-donation-amount').focus();
    });

    //Disable unavailable choices
    $( '.wcdp-choose-donation' ).on('change', function() {
        $('.wcdp_su input').each(function() {
            if ($('#'+this.name + ' option[value="' + this.value + '"]').length == 0) {
                this.setAttribute("disabled", "true");
            } else {
                this.removeAttribute("disabled");
            }
        });
    });
});
