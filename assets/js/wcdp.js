jQuery(function ($) {
  $(document).on(
    "change",
    '#wcdp_fee_recovery, .wcdp-donation-upsell__input, input[name="payment_method"]',
    function () {
      if (
        $(this).is("#wcdp_fee_recovery, .wcdp-donation-upsell__input") ||
        $("#wcdp_fee_recovery").prop("checked")
      ) {
        triggerCheckoutUpdate();
      }
    },
  );

  if (!document.querySelector(".wcdp-form")) return;

  let currentFormData;

  function getDonationForm(context) {
    const contextNode = context?.target || context;
    if (contextNode?.closest) {
      const scopedForm = contextNode.closest("form.wcdp-choose-donation");
      if (scopedForm) {
        return scopedForm;
      }
    }

    return (
      document.querySelector("form#wcdp-ajax-send.wcdp-choose-donation") ||
      document.querySelector("form.wcdp-choose-donation")
    );
  }

  //Send donation selection form
  function wcdp_submit(step, context) {
    const formElement = getDonationForm(context);
    if (!formElement) {
      return;
    }

    if (check_validity(formElement)) {
      $("#wcdp-spinner").show();
      $("#wcdp-ajax-button").hide();
      const form = $(formElement);
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
              wcdp_steps(step, "");
              break;
            default:
              error_message(
                response.message,
                response.reload,
                response.newParams,
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
  function check_validity(context) {
    const form = getDonationForm(context);
    if (!form) {
      return false;
    }

    const variation = form.querySelector("#variation_id");
    try {
      return form.reportValidity() && (!variation || variation.value !== "");
    } catch (err) {
      return false;
    }
  }

  function findScrollableAncestor(el) {
    var node = el;
    while (
      node &&
      node !== document.body &&
      node !== document.documentElement
    ) {
      var style = window.getComputedStyle(node);
      if (
        /(auto|scroll)/.test(style.overflow + style.overflowY + style.overflowX)
      ) {
        return node;
      }
      node = node.parentElement;
    }
    return document.scrollingElement || document.documentElement || window;
  }

  function scrollToElementWithOffset(el, offset = 200, smooth = true) {
    if (!el) return;
    try {
      var rect = el.getBoundingClientRect();
      var absoluteTop = rect.top + window.pageYOffset - offset;
      if (absoluteTop < 0) absoluteTop = 0;
      var ancestor = findScrollableAncestor(el);
      var behavior = smooth ? "smooth" : "auto";
      if (
        ancestor === document.scrollingElement ||
        ancestor === document.documentElement ||
        ancestor === window
      ) {
        window.scrollTo({ top: absoluteTop, behavior: behavior });
      } else {
        // scroll the ancestor so the element is visible with offset
        var ancestorRect = ancestor.getBoundingClientRect();
        var scrollTop =
          ancestor.scrollTop + (rect.top - ancestorRect.top) - offset;
        if (scrollTop < 0) scrollTop = 0;
        ancestor.scrollTo({ top: scrollTop, behavior: behavior });
      }
    } catch (e) {
      // ignore
    }
  }

  function showRequiredNotice($firstInvalid) {
    var $notice = $("#wcdp-step-2 .wcdp-required-field-notice");
    if ($notice.length && $firstInvalid && $firstInvalid.length) {
      // ensure accessibility attributes
      $notice.attr("role", "alert");
      $notice.attr("aria-live", "polite");
      $firstInvalid.append($notice);
    }
  }

  function focusFirstFocusableInStep(stepEl) {
    stepEl
      .querySelector(
        'input:not([disabled]):not([type="hidden"]), button:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])',
      )
      ?.focus();
  }

  /**
   * Add error message banner to Dom
   * @param message
   * @param reload
   */
  function error_message(
    message = "An unexpected error occurred. Please reload the page and try again. If the problem persists, please contact our support team.",
    reload = true,
    newParams = false,
  ) {
    if (!reload) {
      $("#wcdp-ajax-button").show();
    }
    if (newParams) {
      alert(message);
      let currentUrl = new URL(window.location.href);
      Object.entries(newParams).forEach(([key, value]) =>
        currentUrl.searchParams.set(key, value),
      );
      window.location.href = currentUrl.toString();
    }
    $("#wcdp-spinner").hide();
    $("#wcdp-ajax-error").remove();
    $("form.checkout.woocommerce-checkout").prepend(
      '<ul class="woocommerce-error" id="wcdp-ajax-error" role="alert"><li></li></ul>',
    );
    $("#wcdp-ajax-error li").text(message);
  }

  /**
   * Handle submit of add to cart form
   */
  $("#wcdp-ajax-send")?.on("submit", function (e) {
    e.preventDefault();
    const serialized = $(this).serialize();
    if (currentFormData != serialized) {
      currentFormData = serialized;
      wcdp_submit(2, this);
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
      const formElement = this;
      if (currentFormData != $(formElement).serialize()) {
        time++;
        currentFormData = $(formElement).serialize();
        setTimeout(function () {
          time--;
          if (time === 0) {
            wcdp_submit(undefined, formElement);
          }
        }, 1300);
      }
    },
  );

  /**
   * Handle update of express checkout amount for Stripe Apple/Google Pay & PayPal
   */
  let expresstime = 0;
  let currentprice = 0;
  $(".wcdp-body")?.on("input blur keyup paste change load", function () {
    const button = document.querySelector(
      ".wcdp-body .single_add_to_cart_button",
    );
    const form = document.querySelector("#wcdp-get-send");
    if (button && form && form.checkValidity()) {
      button.classList.remove("disabled");
    } else if (button) {
      button.classList.add("disabled");
    }
    $(".wcdp-express-amount").val(
      $('input[name="wcdp-donation-amount"]').val(),
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

  // Next and back buttons
  let currentStep = 1;
  $(".wcdp-form .wcdp-button, .wcdp-step").click(function (e) {
    const clickedStep = parseInt($(this).attr("data-step"));

    if (currentStep === clickedStep) return;

    // Determine intended target. If user from step 1 tries to jump directly to step 3, redirect to 2.
    let targetStep = clickedStep;
    if (currentStep === 1 && clickedStep === 3) targetStep = 2;

    // If we're on step 1, ensure step-1 form is valid before moving
    if (currentStep === 1) {
      const donationForm = getDonationForm(this);
      if (!check_validity(donationForm)) return;
      var ajaxSend = donationForm ? $(donationForm).serialize() : "";
      if (currentFormData != ajaxSend) {
        currentFormData = ajaxSend;
        // submit will call wcdp_steps after success; include scroll flag via options when called directly
        wcdp_submit(targetStep, donationForm);
        return;
      }
    }

    // When navigating from step 2 to 3, validate checkout fields first
    if (currentStep === 2 && targetStep === 3) {
      $("#wcdp-step-2 .validate-required")
        .find("input:visible, select:visible")
        .trigger("validate");

      var $invalids = $("#wcdp-step-2 .woocommerce-invalid:visible");
      if ($invalids.length > 0) {
        var $firstInvalid = $invalids.first();
        showRequiredNotice($firstInvalid);
        // scroll the first invalid into view (consider header)
        scrollToElementWithOffset($firstInvalid[0], 200, true);
        // focus the first invalid field for a11y
        try {
          $firstInvalid.find("input,select,textarea,button").first().focus();
        } catch (e) {}
        // ensure browser shows native messages
        var checkoutForm = document.querySelector("form.checkout");
        if (checkoutForm && typeof checkoutForm.reportValidity === "function") {
          checkoutForm.reportValidity();
        }
        return;
      }
    }

    // All checks passed — navigate. Only scroll to top if advancing to the next step (forward) or when redirected forward.
    wcdp_steps(targetStep, "");
  });

  function wcdp_steps(step, formid = "") {
    const root = document.documentElement;
    root.style.setProperty("--wcdp-step-2", "var(--wcdp-main)");
    root.style.setProperty("--wcdp-step-3", "var(--wcdp-main)");
    switch (step) {
      case 3:
        root.style.setProperty("--wcdp-step-3", "var(--wcdp-main-2)");
      case 2:
        root.style.setProperty("--wcdp-step-2", "var(--wcdp-main-2)");
        break;
      case 1:
        break;
      default:
        return;
    }
    $(".wcdp-style5-active")?.removeClass("wcdp-style5-active");
    $("#wcdp-style5-step-" + step)?.addClass("wcdp-style5-active");
    $("#wcdp-progress-bar")?.css("width", 33.33 * (parseInt(step) - 1) + "%");
    const stepEl = document.getElementById("wcdp-step-" + step);
    if (stepEl) {
      $(".wcdp-tab")?.hide();
      stepEl.style.display = "block";
      scrollToElementWithOffset(stepEl, 200, true);
      focusFirstFocusableInStep(stepEl);
      currentStep = step;
    }
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
      const donationForm = getDonationForm();
      if (check_validity(donationForm)) {
        currentFormData = donationForm ? $(donationForm).serialize() : "";
        wcdp_submit(undefined, donationForm);
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
        window.location.pathname + window.location.search,
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
      `form[data-formid="${formId}"] .wcdp_donation_amount_field`,
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
      const inputField = button.parentElement?.querySelector(".wcdp-input-field");
      if (inputField) {
        inputField.focus();
        inputField.value = '';
      }
    });
  });

  function syncVariationButtons(form) {
    const getSelectForGroup = (group) => {
      const input = group.querySelector("input[name]");
      if (!input) return null;
      return (
        group.closest(".wcdp_variation")?.querySelector("select") ||
        form.querySelector(`select[name="attribute_${input.name}"]`) ||
        form.querySelector(`select[name="${input.name}"]`)
      );
    };

    form.querySelectorAll(".wcdp_su").forEach((group) => {
      const select = getSelectForGroup(group);
      if (!select) return;

      const available = new Set(
        Array.from(select.options)
          .filter(
            (option) =>
              option.value &&
              !option.disabled &&
              !option.classList.contains("disabled"),
          )
          .map((option) => option.value),
      );

      group.querySelectorAll("input").forEach((input) => {
        const disabled = !available.has(input.value);
        input.disabled = disabled;
        if (disabled) input.checked = false;
      });

      const selectedInput = group.querySelector(
        "input:checked:not([disabled])",
      );
      const nextValue = selectedInput ? selectedInput.value : "";
      if (select.value !== nextValue) {
        select.value = nextValue;
        $(select).trigger("change");
      }
    });
  }

  function syncAmountSelection(form, preferSelectedSuggestion = true) {
    const selectedAmount = form.querySelector(
      ".wcdp_amount_suggestion:checked",
    );
    const amountInput = form.querySelector(
      'input[name="wcdp-donation-amount"]',
    );
    const amountAttributeInput = form.querySelector(
      'input[name="attribute_wcdp_donation_amount"]',
    );

    if (preferSelectedSuggestion && selectedAmount && amountInput) {
      amountInput.value = selectedAmount.value;
    }

    if (amountAttributeInput && amountInput) {
      amountAttributeInput.value = amountInput.value;
    }
  }

  function syncSelection(form) {
    syncVariationButtons(form);
    syncAmountSelection(form);
  }

  function setupAmountInputSync(form) {
    const amountInput = form.querySelector(
      'input[name="wcdp-donation-amount"]',
    );
    if (!amountInput) {
      return;
    }

    amountInput.addEventListener("input", () => {
      const selectedSuggestion = form.querySelector(
        ".wcdp_amount_suggestion:checked",
      );
      if (
        selectedSuggestion &&
        selectedSuggestion.value !== amountInput.value
      ) {
        selectedSuggestion.checked = false;
      }
      syncAmountSelection(form, false);
    });
  }

  function setupTheme2AmountValidation(form) {
    const amountGroupRadios = form.querySelectorAll(
      ".wcdp_amount input[type='radio']",
    );
    amountGroupRadios.forEach((radio) => {
      radio.required = false;
    });
  }

  function handleDonationInputs() {
    const forms = document.querySelectorAll("form.wcdp-choose-donation");
    forms.forEach((form) => {
      let variationSyncQueued = false;
      const scheduleVariationSync = () => {
        if (variationSyncQueued) return;
        variationSyncQueued = true;
        requestAnimationFrame(() => {
          variationSyncQueued = false;
          syncVariationButtons(form);
        });
      };

      const onFormChange = () => {
        syncSelection(form);
        scheduleVariationSync();
      };

      form.addEventListener("change", onFormChange);

      $(form).on(
        "update_variation_values woocommerce_update_variation_values found_variation reset_data hide_variation show_variation",
        scheduleVariationSync,
      );

      setupAmountInputSync(form);

      if (form.closest(".wcdp-theme-2")) {
        setupTheme2AmountValidation(form);
      }

      syncSelection(form);
    });
  }
  handleDonationInputs();
});
