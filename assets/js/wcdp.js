jQuery(function ($) {
  $(document).on(
    "change",
    '#wcdp_fee_recovery, input[name="payment_method"]',
    function () {
      if (
        $(this).is("#wcdp_fee_recovery") ||
        $("#wcdp_fee_recovery").prop("checked")
      ) {
        triggerCheckoutUpdate();
      }
    }
  );

  if (!document.querySelector(".wcdp-form")) return;

  let currentFormData;
  //Send donation selection form
  function wcdp_submit(step) {
    if (check_validity("#wcdp-ajax-send")) {
      $("#wcdp-spinner").show();
      $("#wcdp-ajax-button").hide();
      const form = $("#wcdp-ajax-send");
      const formData = form.serialize();
      $.ajax({
        type: "POST",
        url: form.attr("action"),
        data: formData,
      })
        .done(function (response) {
          switch (response.success) {
            case true:
              $(".woocommerce-error").remove();
              $("body").trigger("update_checkout");
              //$('.wcdp-button[value=2]').trigger("click");
              $("#wcdp-ajax-button").show();
              $("#wcdp-spinner").hide();
              if (response.recurring) {
                $("#createaccount").prop("checked", true).trigger("change");
                $(".create-account:has(#createaccount)").hide();
              } else {
                $(".create-account:has(#createaccount)").show();
              }
              wcdp_steps(step);
              break;
            default:
              error_message(
                response.message,
                response.reload,
                response.newParams
              );
              break;
          }
        })
        .fail(function () {
          $("#wcdp-spinner").hide();
          error_message($(".wcdp-choose-donation").attr("wcdp-error-default"));
        });
    }
  }

  // Return true if the donation form is filled in correctly
  function check_validity(id) {
    const variation = $("#variation_id");
    try {
      return (
        $(id)[0].reportValidity() &&
        (variation.length === 0 || variation.attr("value") != "")
      );
    } catch (err) {
      return false;
    }
  }

  /**
   * Add error message banner to Dom
   * @param message
   * @param reload
   */
  function error_message(
    message = "An unexpected error occurred. Please reload the page and try again. If the problem persists, please contact our support team.",
    reload = true,
    newParams = false
  ) {
    if (!reload) {
      $("#wcdp-ajax-button").show();
    }
    if (newParams) {
      alert(message);
      let currentUrl = new URL(window.location.href);
      Object.entries(newParams).forEach(([key, value]) =>
        currentUrl.searchParams.set(key, value)
      );
      window.location.href = currentUrl.toString();
    }
    $("#wcdp-spinner").hide();
    $("#wcdp-ajax-error").remove();
    $("form.checkout.woocommerce-checkout").prepend(
      '<ul class="woocommerce-error" id="wcdp-ajax-error" role="alert"><li></li></ul>'
    );
    $("#wcdp-ajax-error li").text(message);
  }

  /**
   * Handle submit of add to cart form
   */
  $("#wcdp-ajax-send")?.on("submit", function (e) {
    e.preventDefault();
    const serialized = $("#wcdp-ajax-send").serialize();
    if (currentFormData != serialized) {
      currentFormData = serialized;
      wcdp_submit(2);
    } else {
      wcdp_steps(2);
    }
  });

  /**
   * Submit step 1 form automatically for style 3
   */
  let time = 0;
  $(".wcdp-body > #wcdp-ajax-send")?.on(
    "input blur keyup paste change",
    function () {
      if (currentFormData != $("#wcdp-ajax-send").serialize()) {
        time++;
        currentFormData = $("#wcdp-ajax-send").serialize();
        setTimeout(function () {
          time--;
          if (time === 0) {
            wcdp_submit();
          }
        }, 1300);
      }
    }
  );

  /**
   * Handle update of express checkout amount for Stripe Apple/Google Pay & PayPal
   */
  let expresstime = 0;
  let currentprice = 0;
  $(".wcdp-body")?.on("input blur keyup paste change load", function () {
    const button = document.querySelector(
      ".wcdp-body .single_add_to_cart_button"
    );
    const form = document.querySelector("#wcdp-get-send");
    if (button && form && form.checkValidity()) {
      button.classList.remove("disabled");
    } else if (button) {
      button.classList.add("disabled");
    }
    $(".wcdp-express-amount").val(
      $('input[name="wcdp-donation-amount"]').val()
    );
    expresstime++;
    setTimeout(function () {
      expresstime--;
      if (
        expresstime == 0 &&
        currentprice != $('input[name="wcdp-donation-amount"]').val()
      ) {
        currentprice = $('input[name="wcdp-donation-amount"]').val();
        $(document.body).trigger("woocommerce_variation_has_changed");
        if (button) {
          button.classList.toggle("wcdp_price_changed");
        }
      }
    }, 500);
  });

  //Next and back buttons
  let currentStep = 1;
  $(".wcdp-form .wcdp-button, .wcdp-step").click(function () {
    const step = $(this).attr("data-step");
    if (currentStep != 1) {
      wcdp_steps(step);
    } else if (step !== 1) {
      if (!check_validity("#wcdp-ajax-send")) return;
      const ajaxSend = $("#wcdp-ajax-send").serialize();
      if (currentFormData != ajaxSend) {
        currentFormData = ajaxSend;
        wcdp_submit(step);
      } else {
        wcdp_steps(step);
      }
    }
  });

  function wcdp_steps(step, formid = "") {
    const root = $(":root")[0];
    root.style.setProperty("--wcdp-step-2", "var(--wcdp-main)");
    root.style.setProperty("--wcdp-step-3", "var(--wcdp-main)");
    switch (step) {
      case "3":
        $("#wcdp-step-2").show();
        $("form.checkout")
          .find(".input-text:visible, select:visible, input:checkbox:visible")
          .trigger("validate");
        if ($("#wcdp-step-2 .woocommerce-invalid:visible").length > 0) {
          $("#wcdp-invalid-fields").show();
          $("#place_order").hide();
        } else {
          $("#wcdp-invalid-fields").hide();
          $("#place_order").show();
        }
        root.style.setProperty("--wcdp-step-3", "var(--wcdp-main-2)");
      case "2":
        root.style.setProperty("--wcdp-step-2", "var(--wcdp-main-2)");
        break;
      case "1":
        break;
      default:
        return;
    }
    $(".wcdp-style5-active")?.removeClass("wcdp-style5-active");
    $("#wcdp-style5-step-" + step)?.addClass("wcdp-style5-active");
    $("#wcdp-progress-bar")?.css("width", 33.33 * (parseInt(step) - 1) + "%");
    $(".wcdp-tab")?.hide();
    $("#wcdp-step-" + step)?.show();
    currentStep = step;
  }

  let express_heading_timeout = 10;
  //initialize WCDP in Frontend
  $(document).ready(function wcdp_setup() {
    $(".wcdp-loader")?.hide();
    $(".wc-donation-platform")?.css({
      visibility: "visible",
      "animation-name": "wcdp-appear-animation",
      "animation-duration": "1s",
    });
    wcdp_open(false);
    try {
      if ($('input[name="wcdp-donation-amount"]')?.val() != 0) {
        $("#wcdp-ajax-send")?.trigger("change");
      }
      if ($(".wcdp-choose-donation")[0].checkValidity()) {
        currentFormData = $("#wcdp-ajax-send")?.serialize();
        wcdp_submit();
      }
      $("form.woocommerce-checkout select")?.selectWoo();
    } finally {
      $("#wcdp-ajax-send,.wcdp_options")?.trigger("change");
      setTimeout(express_checkout_heading, express_heading_timeout);
    }
  });

  /**
   * Show the Express Donation Header when Stripe or PayPal express checkout available
   */
  function express_checkout_heading() {
    if (
      $("#wc-stripe-payment-request-button")?.children()?.length +
        $("#ppc-button, #ppc-button-ppcp-gateway")?.children()?.length >
      0
    ) {
      $(".wcdp-express-heading")?.show();
    } else if (express_heading_timeout < 10000) {
      express_heading_timeout = express_heading_timeout * 2;
      setTimeout(express_checkout_heading, express_heading_timeout);
    }
  }

  //Modal window hash
  window.onhashchange = function () {
    wcdp_open(false);
  };

  $(".wcdp-modal-open")?.click(function () {
    wcdp_open(true);
  });

  //Close modal when excape is pressed
  $(document).on("keypress", "input", function (e) {
    if (e.key === "Escape") {
      wcdp_close();
    }
  });

  //Clode modal when clicking on the close button
  $(".wcdp-modal-close")?.click(wcdp_close);

  var wcdpOpen = false;
  //Close modal function
  function wcdp_close() {
    if (wcdpOpen) {
      $(".wcdp-overlay")?.hide();
      $("body")?.css("overflow-y", " auto");
      history.pushState(
        "",
        document.title,
        window.location.pathname + window.location.search
      );
      wcdpOpen = false;
    }
  }

  //Open modal function
  function wcdp_open(direct) {
    const x = $(".wcdp-overlay");
    if (direct || (location.hash === "#wcdp-form" && x.length > 0)) {
      x.show();
      $("body")?.css("overflow-y", "hidden");
      wcdpOpen = true;
    }
  }

  function triggerCheckoutUpdate(delay = 400) {
    setTimeout(() => $("body").trigger("update_checkout"), delay);
  }

  //copy value of range slider
  $(document).on("input", ".wcdp-range", function () {
    let $range = $(this);
    let formId = $range.closest("form").data("formid");
    let $amount = $(
      `form[data-formid="${formId}"] .wcdp_donation_amount_field`
    );

    if ($amount.length) {
      $amount.val($range.val());

      if ($range.val() == $range.attr("max")) {
        $amount.select();
      }
    }
  });

  //copy value of amount input to range slider
  $(".wcdp-amount-range-field")?.on("input", function () {
    $("#wcdp-range")?.val($('input[name="wcdp-donation-amount"]')?.val());
  });

  //Focus donation amount textfield when "other"-button is selected
  document.querySelectorAll(".wcdp_value_other").forEach((button) => {
    button.addEventListener("click", () => {
      button.parentElement?.querySelector(".wcdp-input-field")?.focus();
    });
  });

  function syncSelection() {
    const inputs = this.querySelectorAll(".wcdp_su input");

    inputs.forEach((input) => {
      const matchingOption = this.querySelector(
        `select option[value="${input.value}"]`
      );
      if (!matchingOption || !matchingOption.classList.contains("attached"))
        return;

      if (matchingOption.classList.contains("enabled")) {
        input.removeAttribute("disabled");
      } else {
        input.setAttribute("disabled", "true");
      }

      if (input.checked && matchingOption) {
        matchingOption.parentElement.value = input.value;
        $(matchingOption.parentElement).trigger("change");
      }
    });

    // copy amount from selectedAmount input field
    const selectedAmount = this.querySelector(
      ".wcdp_amount_suggestion:checked"
    );
    const amountInput = this.querySelector(
      'input[name="wcdp-donation-amount"]'
    );
    const amountAttributeInput = this.querySelector(
      'input[name="attribute_wcdp_donation_amount"]'
    );
    if (selectedAmount && amountInput) {
      amountInput.value = selectedAmount.value;
    }
    if (amountAttributeInput && amountInput) {
      amountAttributeInput.value = amountInput.value;
    }
  }

  function handleDonationInputs() {
    const forms = document.querySelectorAll("form.wcdp-choose-donation");
    forms.forEach((form) => {
      //const formid = form.dataset['formid'];
      form.addEventListener("change", syncSelection);
    });
  }
  handleDonationInputs();

  wp.hooks.addFilter(
    "wcstripe.express-checkout.map-line-items", // Hook name
    "wc-donation-platform/modify-cart-data", // Unique namespace
    function (cartData) {
      cartData.items.forEach((item) => {
        // Modify each item as needed
        item.label = item.name;
      });
      console.log(cartData);

      return cartData;
    }
  );
});
