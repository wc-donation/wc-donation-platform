(function () {
  const { __, sprintf } = wp.i18n;

  document.addEventListener("change", (event) => {
    const target = event.target;
    const isFeeRecovery = target.matches(
      "#wcdp_fee_recovery, .wcdp-donation-upsell__input",
    );
    const feeRecoveryChecked =
      document.querySelector("#wcdp_fee_recovery")?.checked === true;

    if (isFeeRecovery || feeRecoveryChecked) {
      triggerCheckoutUpdate();
    }
  });

  if (!document.querySelector(".wcdp-form")) return;

  let currentFormData;

  const serializeForm = (form) =>
    new URLSearchParams(new FormData(form)).toString();

  const setElementVisibility = (element, isVisible) => {
    if (!element) {
      return;
    }

    element.style.display = isVisible ? "" : "none";
  };

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

  function isValidatableField(field) {
    return (
      field instanceof HTMLElement &&
      (field.matches("input") ||
        field.matches("select") ||
        field.matches("textarea"))
    );
  }

  function setValidationState(element, isValid) {
    if (!element) {
      return;
    }

    element.classList.toggle("woocommerce-invalid", !isValid);
    element.classList.toggle("woocommerce-invalid-required-field", !isValid);
    element.classList.toggle("woocommerce-validated", isValid);
  }

  function getAmountValidationContainer(form) {
    return (
      form.querySelector("#wcdp_va_amount") ||
      form.querySelector(".wcdp_variation.wcdp-row") ||
      form
    );
  }

  function getAmountValidationNotice(container) {
    let notice = container.querySelector(".wcdp-required-field-notice");

    return notice;
  }

  function getAmountValidationMessage(amountInput) {
    const minValue = Number(amountInput.min);
    const hasValidMin = Number.isFinite(minValue) && minValue > 0;

    if (amountInput.validity.valueMissing) {
      return __(
        "Please provide a donation amount above.",
        "wc-donation-platform",
      );
    }

    if (amountInput.validity.rangeUnderflow && hasValidMin) {
      return sprintf(
        __(
          "Please enter a donation amount of at least %s.",
          "wc-donation-platform",
        ),
        minValue,
      );
    }

    if (amountInput.validity.rangeOverflow) {
      return sprintf(
        __("Maximum donation amount is %s.", "wc-donation-platform"),
        amountInput.max,
      );
    }

    if (amountInput.validity.stepMismatch) {
      return sprintf(
        __(
          "Please enter an amount in increments of %s.",
          "wc-donation-platform",
        ),
        amountInput.step,
      );
    }

    return __("Please enter a valid donation amount.", "wc-donation-platform");
  }

  function getAmountValidationParts(form) {
    return {
      amountInput: form.querySelector('input[name="wcdp-donation-amount"]'),
      amountSuggestions: form.querySelectorAll(".wcdp_amount_suggestion"),
      selectedAmountOption: form.querySelector(
        ".wcdp_amount input[type='radio']:checked",
      ),
      selectedSuggestion: form.querySelector(".wcdp_amount_suggestion:checked"),
      isOtherSelected: !!form.querySelector(".wcdp_value_other:checked"),
      hasOtherOption: !!form.querySelector(".wcdp_value_other"),
      container: getAmountValidationContainer(form),
    };
  }

  function isCustomAmountBlurIgnored(form) {
    return !!form.__wcdpIgnoreCustomAmountBlur;
  }

  function getAmountOptionInputFromEventTarget(target, amountOptionsList) {
    const optionItem = target.closest("li");
    if (!optionItem || !amountOptionsList.contains(optionItem)) {
      return null;
    }

    return optionItem.querySelector("input[type='radio']");
  }

  function updateAmountValidationUI(form, isValid, message = "") {
    const { container } = getAmountValidationParts(form);
    setValidationState(container, isValid);

    const notice = getAmountValidationNotice(container);
    if (!notice) {
      return;
    }
    notice.textContent = message;
    notice.style.display = isValid ? "none" : "block";
  }

  function validateAmountSelection(form, showValidationMessage = false) {
    const amount = form.querySelectorAll("input[name='wcdp-donation-amount']");

    const {
      amountInput,
      amountSuggestions,
      selectedAmountOption,
      selectedSuggestion,
      isOtherSelected,
      hasOtherOption,
    } = getAmountValidationParts(form);

    if (!amountInput) {
      return true;
    }

    const hasAmountSelection = !!selectedAmountOption;
    const hasSuggestionSelection = !!selectedSuggestion;
    const hasAmountValue = amountInput.value.trim() !== "";

    let isValid = true;
    if (hasOtherOption) {
      if (!hasAmountSelection) {
        isValid = false;
      } else if (isOtherSelected) {
        isValid = hasAmountValue && amountInput.checkValidity();
      }
    } else if (!hasSuggestionSelection) {
      isValid = amountInput.checkValidity();
    }

    if (
      !isOtherSelected &&
      hasSuggestionSelection &&
      amountSuggestions.length
    ) {
      isValid = true;
    }

    if (isOtherSelected && !hasAmountValue) {
      isValid = false;
    }

    if (showValidationMessage) {
      if (
        isOtherSelected &&
        !hasAmountValue &&
        document.activeElement === amountInput
      ) {
        updateAmountValidationUI(form, true, "");
        return false;
      }

      const message = isValid ? "" : getAmountValidationMessage(amountInput);
      updateAmountValidationUI(form, isValid, message);
    } else if (isValid) {
      updateAmountValidationUI(form, true, "");
    }

    return isValid;
  }

  //Send donation selection form
  async function wcdp_submit(step, context, options = {}) {
    const { showValidationMessage = true } = options;
    const formElement = getDonationForm(context);
    if (!formElement) {
      return;
    }

    if (check_validity(formElement, showValidationMessage)) {
      const spinner = document.querySelector("#wcdp-spinner");
      const ajaxButton = document.querySelector("#wcdp-ajax-button");
      if (spinner) spinner.style.display = "block";
      if (ajaxButton) ajaxButton.style.display = "none";

      const formData = serializeForm(formElement);
      const action = formElement.getAttribute("action") || window.location.href;

      try {
        const response = await fetch(action, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          },
          body: formData,
          credentials: "same-origin",
        });

        const payload = await response.json();

        switch (payload.success) {
          case true:
            document.querySelectorAll(".woocommerce-error").forEach((node) => {
              node.remove();
            });
            document.body.dispatchEvent(new CustomEvent("update_checkout"));
            if (ajaxButton) ajaxButton.style.display = "block";
            if (spinner) spinner.style.display = "none";

            const createAccount = document.querySelector("#createaccount");
            if (createAccount) {
              if (payload.recurring) {
                createAccount.checked = true;
                createAccount.dispatchEvent(
                  new Event("change", { bubbles: true }),
                );
              }
            }

            document
              .querySelectorAll(".create-account:has(#createaccount)")
              .forEach((node) => {
                const nodeEl = node;
                if (payload.recurring) {
                  nodeEl.style.display = "none";
                } else {
                  nodeEl.style.display = "";
                }
              });

            wcdp_steps(step, "");
            break;
          default:
            error_message(payload.message, payload.reload, payload.newParams);
            break;
        }
      } catch (error) {
        if (spinner) spinner.style.display = "none";
        error_message(
          __(
            "An unexpected error occurred. Please reload the page and try again. If the problem persists, please contact our support team.",
            "wc-donation-platform",
          ) || "An unexpected error occurred.",
        );
      }
    }
  }

  // Return true if the donation form is filled in correctly
  function check_validity(context, showValidationMessage = true) {
    const form = getDonationForm(context);
    if (!form) {
      return false;
    }

    const variation = form.querySelector("#variation_id");
    try {
      const isFormValid = form.checkValidity();
      const isAmountValid = validateAmountSelection(
        form,
        showValidationMessage,
      );

      return (
        isFormValid && isAmountValid && (!variation || variation.value !== "")
      );
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

  function showRequiredNotice(firstInvalid) {
    const notice = document.querySelector(
      "#wcdp-step-2 .wcdp-required-field-notice",
    );
    if (!notice || !firstInvalid) {
      return;
    }

    notice.setAttribute("role", "alert");
    notice.setAttribute("aria-live", "polite");
    firstInvalid.append(notice);
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
      const ajaxButton = document.querySelector("#wcdp-ajax-button");
      if (ajaxButton) {
        ajaxButton.style.display = "block";
      }
    }

    if (newParams) {
      window.alert(message);
      const currentUrl = new URL(window.location.href);
      Object.entries(newParams).forEach(([key, value]) => {
        currentUrl.searchParams.set(key, value);
      });
      window.location.href = currentUrl.toString();
      return;
    }

    const spinner = document.querySelector("#wcdp-spinner");
    if (spinner) {
      spinner.style.display = "none";
    }

    const existingError = document.querySelector("#wcdp-ajax-error");
    if (existingError) {
      existingError.remove();
    }

    const checkoutForm = document.querySelector(
      "form.checkout.woocommerce-checkout",
    );
    if (checkoutForm) {
      const list = document.createElement("ul");
      list.className = "woocommerce-error";
      list.id = "wcdp-ajax-error";
      list.setAttribute("role", "alert");

      const item = document.createElement("li");
      item.textContent = message;
      list.appendChild(item);

      checkoutForm.prepend(list);
    }
  }

  /**
   * Handle submit of add to cart form
   */
  document
    .querySelector("#wcdp-ajax-send")
    ?.addEventListener("submit", (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const serialized = serializeForm(form);
      if (currentFormData !== serialized) {
        currentFormData = serialized;
        wcdp_submit(2, form);
      } else {
        wcdp_steps(2);
      }
    });

  /**
   * Submit step 1 form automatically for style 2 and on checkout
   */
  let time = 0;
  function autoSubmitDonationSelection(formElement) {
    const serialized = serializeForm(formElement);
    if (currentFormData !== serialized) {
      time++;
      currentFormData = serialized;
      setTimeout(() => {
        time--;
        if (time === 0) {
          wcdp_submit(undefined, formElement, {
            showValidationMessage: true,
          });
        }
      }, 1300);
    }
  }

  /**
   * Handle update of express checkout amount for Stripe Apple/Google Pay & PayPal
   */
  let expresstime = 0;
  let currentprice = 0;
  document.querySelector(".wcdp-body")?.addEventListener("input", function () {
    const button = document.querySelector(
      ".wcdp-body .single_add_to_cart_button",
    );
    const form = document.querySelector("#wcdp-get-send");
    const amountInput = document.querySelector(
      'input[name="wcdp-donation-amount"]',
    );

    if (button && form && form.checkValidity()) {
      button.classList.remove("disabled");
    } else if (button) {
      button.classList.add("disabled");
    }

    document.querySelectorAll(".wcdp-express-amount").forEach((field) => {
      field.value = amountInput ? amountInput.value : "";
    });

    expresstime++;
    setTimeout(() => {
      expresstime--;
      const currentValue = amountInput ? amountInput.value : "";
      if (expresstime === 0 && currentprice !== currentValue) {
        currentprice = currentValue;
        document.body.dispatchEvent(
          new CustomEvent("woocommerce_variation_has_changed"),
        );
        if (button) {
          button.classList.toggle("wcdp_price_changed");
        }
      }
    }, 500);
  });

  // Next and back buttons
  let currentStep = 1;
  document
    .querySelectorAll(".wcdp-form .wcdp-button, .wcdp-step")
    .forEach((button) => {
      button.addEventListener("click", () => {
        const clickedStep = Number.parseInt(
          button.getAttribute("data-step"),
          10,
        );

        if (currentStep === clickedStep) {
          return;
        }

        let targetStep = clickedStep;
        if (currentStep === 1 && clickedStep === 3) {
          targetStep = 2;
        }

        if (currentStep === 1) {
          const donationForm = getDonationForm(button);
          if (!check_validity(donationForm)) {
            return;
          }

          const ajaxSend = donationForm ? serializeForm(donationForm) : "";
          if (currentFormData !== ajaxSend) {
            currentFormData = ajaxSend;
            wcdp_submit(targetStep, donationForm);
            return;
          }
        }

        if (currentStep === 2 && targetStep === 3) {
          const requiredFields = Array.from(
            document.querySelectorAll(
              "#wcdp-step-2 .validate-required input, #wcdp-step-2 .validate-required select",
            ),
          ).filter((field) => isElementVisible(field));

          requiredFields.forEach((field) =>
            field.dispatchEvent(new Event("validate")),
          );

          const invalids = Array.from(
            document.querySelectorAll("#wcdp-step-2 .woocommerce-invalid"),
          ).filter((field) => isElementVisible(field));

          if (invalids.length > 0) {
            const firstInvalid = invalids[0];
            showRequiredNotice(firstInvalid);
            scrollToElementWithOffset(firstInvalid, 200, true);
            const focusable = firstInvalid.querySelector(
              "input, select, textarea, button",
            );
            if (focusable) {
              focusable.focus();
            }
            return;
          }
        }

        wcdp_steps(targetStep, "");
      });
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

    document.querySelectorAll(".wcdp-style5-active").forEach((node) => {
      node.classList.remove("wcdp-style5-active");
    });

    const styleStep = document.querySelector(`#wcdp-style5-step-${step}`);
    if (styleStep) {
      styleStep.classList.add("wcdp-style5-active");
    }

    const progressBar = document.querySelector("#wcdp-progress-bar");
    if (progressBar) {
      progressBar.style.width = `${33.33 * (Number.parseInt(step, 10) - 1)}%`;
    }

    const stepEl = document.getElementById(`wcdp-step-${step}`);
    if (stepEl) {
      document.querySelectorAll(".wcdp-tab").forEach((tab) => {
        tab.style.display = "none";
      });
      stepEl.style.display = "block";
      scrollToElementWithOffset(stepEl, 200, true);
      focusFirstFocusableInStep(stepEl);
      currentStep = step;
    }
  }

  let express_heading_timeout = 10;
  //initialize WCDP in Frontend
  document.addEventListener("DOMContentLoaded", function wcdp_setup() {
    const spinners = document.querySelectorAll(".wcdp-loader");
    const forms = document.querySelectorAll(".wc-donation-platform");

    //show the forms and hide the spinners
    spinners.forEach((spinner) => {
      spinner.style.display = "none";
    });
    forms.forEach((form) => {
      form.style.visibility = "visible";
      form.style.animationName = "wcdp-appear-animation";
      form.style.animationDuration = "1s";
    });

    // init popup
    wcdp_open(false);

    try {
      const amountInput = document.querySelector(
        'input[name="wcdp-donation-amount"]',
      );
      if (amountInput && Number(amountInput.value) !== 0) {
        const ajaxSend = document.querySelector("#wcdp-ajax-send");
        if (ajaxSend) {
          // auto-submit the form to update the checkout with the pre-filled amount
          ajaxSend.dispatchEvent(new Event("change", { bubbles: true }));
        }
      }

      const donationForm = getDonationForm();
      if (check_validity(donationForm, false)) {
        currentFormData = donationForm ? serializeForm(donationForm) : "";
        wcdp_submit(undefined, donationForm, {
          showValidationMessage: false,
        });
      }

      document
        .querySelectorAll("form.woocommerce-checkout select")
        .forEach((select) => {
          if (typeof select.selectWoo === "function") {
            select.selectWoo();
          }
        });
    } finally {
      const triggerTargets = document.querySelectorAll(
        "#wcdp-ajax-send, .wcdp_options",
      );
      triggerTargets.forEach((target) => {
        target.dispatchEvent(new Event("change", { bubbles: true }));
      });
      setTimeout(express_checkout_heading, express_heading_timeout);
    }
  });

  /**
   * Show the Express Donation Header when Stripe or PayPal express checkout available
   */
  function express_checkout_heading() {
    const stripeButtons = document.querySelectorAll(
      "#wc-stripe-payment-request-button",
    );
    const ppcButtons = document.querySelectorAll(
      "#ppc-button, #ppc-button-ppcp-gateway",
    );

    if (
      Array.from(stripeButtons).reduce(
        (count, node) => count + node.children.length,
        0,
      ) +
        Array.from(ppcButtons).reduce(
          (count, node) => count + node.children.length,
          0,
        ) >
      0
    ) {
      const heading = document.querySelector(".wcdp-express-heading");
      if (heading) {
        heading.style.display = "block";
      }
    } else if (express_heading_timeout < 10000) {
      express_heading_timeout *= 2;
      setTimeout(express_checkout_heading, express_heading_timeout);
    }
  }

  //Modal window hash
  window.onhashchange = function () {
    wcdp_open(false);
  };

  document.querySelectorAll(".wcdp-modal-open").forEach((button) => {
    button.addEventListener("click", () => {
      wcdp_open(true);
    });
  });

  //Close modal when excape is pressed
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && event.target instanceof HTMLInputElement) {
      wcdp_close();
    }
  });

  //Clode modal when clicking on the close button
  document.querySelectorAll(".wcdp-modal-close").forEach((button) => {
    button.addEventListener("click", wcdp_close);
  });

  let wcdpOpen = false;
  //Close modal function
  function wcdp_close() {
    if (wcdpOpen) {
      const overlay = document.querySelector(".wcdp-overlay");
      if (overlay) {
        overlay.style.display = "none";
      }
      document.body.style.overflowY = "auto";
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
    const overlay = document.querySelector(".wcdp-overlay");
    if (overlay && (direct || (location.hash === "#wcdp-form" && overlay))) {
      overlay.style.display = "block";
      document.body.style.overflowY = "hidden";
      wcdpOpen = true;
    }
  }

  function triggerCheckoutUpdate(delay = 400) {
    setTimeout(() => {
      document.body.dispatchEvent(new CustomEvent("update_checkout"));
    }, delay);
  }

  //copy value of range slider
  document.addEventListener("input", (event) => {
    const range = event.target;
    if (
      !(range instanceof HTMLInputElement) ||
      !range.classList.contains("wcdp-range")
    ) {
      return;
    }

    const formId = range.closest("form")?.dataset.formid;
    const amount = formId
      ? document.querySelector(
          `form[data-formid="${formId}"] .wcdp_donation_amount_field`,
        )
      : null;

    if (amount) {
      amount.value = range.value;

      if (range.value === range.max) {
        amount.select();
      }
    }
  });

  //copy value of amount input to range slider
  document.querySelectorAll(".wcdp-amount-range-field").forEach((field) => {
    field.addEventListener("input", () => {
      const range = document.querySelector("#wcdp-range");
      const amountInput = document.querySelector(
        'input[name="wcdp-donation-amount"]',
      );
      if (range && amountInput) {
        range.value = amountInput.value;
      }
    });
  });

  //Focus donation amount textfield when "other"-button is selected
  document.querySelectorAll("input.wcdp_value_other").forEach((input) => {
    input.addEventListener("change", (e) => {
      const inputField = input.parentElement?.querySelector(
        "input[name='wcdp-donation-amount']",
      );
      if (inputField) {
        inputField.focus();
        inputField.value = "";
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
        select.dispatchEvent(new Event("change", { bubbles: true }));
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
      validateAmountSelection(form, false);
    });
  }

  function isElementVisible(element) {
    if (!element) {
      return false;
    }

    const style = window.getComputedStyle(element);
    return (
      style.display !== "none" &&
      style.visibility !== "hidden" &&
      element.offsetParent !== null
    );
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
        if (form.dataset.style === "2" || form.dataset.style === "checkout") {
          autoSubmitDonationSelection(form);
        }
      };

      form.addEventListener("change", onFormChange);

      [
        "update_variation_values",
        "woocommerce_update_variation_values",
        "found_variation",
        "reset_data",
        "hide_variation",
        "show_variation",
      ].forEach((eventName) => {
        form.addEventListener(eventName, scheduleVariationSync);
      });

      setupAmountInputSync(form);

      syncSelection(form);
    });
  }
  handleDonationInputs();
})();
