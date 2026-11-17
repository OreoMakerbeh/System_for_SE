function initializeRiderDialogs() {
  document.querySelectorAll("[data-rider-open]").forEach((button) => {
    const dialog = document.getElementById(button.dataset.riderOpen);
    if (dialog instanceof HTMLDialogElement) {
      button.addEventListener("click", () => dialog.showModal());
    }
  });
  document.querySelectorAll("[data-rider-dialog]").forEach((dialog) => {
    dialog.querySelector("[data-rider-close]").addEventListener("click", () => dialog.close());
    dialog.addEventListener("close", () => {
      dialog.querySelectorAll("[data-password-toggle]").forEach((button) => {
        const input = document.getElementById(button.getAttribute("aria-controls"));
        input.value = "";
        input.type = "password";
        const label = `Show ${button.dataset.passwordLabel}`;
        button.setAttribute("aria-pressed", "false");
        button.setAttribute("aria-label", label);
        button.title = label;
      });
    });
    if (dialog.hasAttribute("data-open-on-load")) {
      dialog.showModal();
      dialog.querySelector('[aria-invalid="true"]')?.focus();
    }
  });
}
function initializeLogin() {
  const loginForm = document.querySelector("[data-login-form]");
  if (!(loginForm instanceof HTMLFormElement)) {
    return;
  }
  const roleSelect = loginForm.querySelector('[name="role"]');
  const loginInput = document.getElementById("loginInput");
  const loginLabel = document.getElementById("loginLabel");
  const humanCheck = document.getElementById("humanCheck");
  const humanCheckStatus = document.getElementById("humanCheckStatus");
  const captchaDialog = document.getElementById("captchaDialog");
  const captchaDialogError = document.getElementById("captchaDialogError");
  const captchaVerify = document.getElementById("captchaVerify");
  let captchaVerified = false;
  const updateLoginField = () => {
    const isCustomer = roleSelect.value === "customer";
    loginLabel.textContent = isCustomer ? "Mobile number" : "Email or mobile number";
    loginInput.type = isCustomer ? "tel" : "text";
    loginInput.inputMode = isCustomer ? "tel" : "text";
  };
  roleSelect.addEventListener("change", updateLoginField);
  updateLoginField();
  humanCheck.addEventListener("change", () => {
    if (humanCheck.checked && !captchaVerified) {
      humanCheck.checked = false;
      captchaDialog.showModal();
    }
  });
  document.querySelectorAll(".captcha-tile").forEach((tile) => {
    tile.addEventListener("click", () => {
      const isSelected = tile.getAttribute("aria-pressed") === "true";
      tile.setAttribute("aria-pressed", String(!isSelected));
      tile.classList.toggle("is-selected", !isSelected);
      captchaDialogError.hidden = true;
    });
  });
  document.getElementById("captchaCancel").addEventListener("click", () => {
    captchaDialog.close();
  });
  captchaVerify.addEventListener("click", async () => {
    const selectedTiles = Array.from(document.querySelectorAll(".captcha-tile.is-selected"));
    if (selectedTiles.length === 0) {
      captchaDialogError.textContent = "Select the matching photos first.";
      captchaDialogError.hidden = false;
      return;
    }
    captchaVerify.disabled = true;
    captchaVerify.textContent = "Checking...";
    const formData = new FormData();
    selectedTiles.forEach((tile) => {
      formData.append("captcha_choices[]", tile.dataset.choice);
    });
    try {
      const response = await fetch(loginForm.dataset.captchaUrl, {
        method: "POST",
        headers: {
          "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
          Accept: "application/json"
        },
        body: formData
      });
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.message || "That selection does not match. Try again.");
      }
      captchaVerified = true;
      humanCheck.checked = true;
      humanCheck.disabled = true;
      humanCheckStatus.textContent = "Verified";
      captchaDialog.close();
    } catch (error) {
      captchaDialogError.textContent = error.message;
      captchaDialogError.hidden = false;
    } finally {
      captchaVerify.disabled = false;
      captchaVerify.textContent = "Verify";
    }
  });
  loginForm.addEventListener("submit", (event) => {
    if (!captchaVerified) {
      event.preventDefault();
      humanCheckStatus.textContent = "Complete the human check before signing in.";
      captchaDialog.showModal();
    }
  });
}
function initializePasswordVisibility() {
  document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    const input = document.getElementById(button.getAttribute("aria-controls"));
    if (!(input instanceof HTMLInputElement)) {
      return;
    }
    button.hidden = false;
    button.addEventListener("click", () => {
      const showPassword = input.type === "password";
      const action = showPassword ? "Hide" : "Show";
      const label = `${action} ${button.dataset.passwordLabel}`;
      input.type = showPassword ? "text" : "password";
      button.setAttribute("aria-pressed", String(showPassword));
      button.setAttribute("aria-label", label);
      button.title = label;
    });
  });
}
function initializeFormActions() {
  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
      return;
    }
    if (!window.confirm(form.dataset.confirm)) {
      event.preventDefault();
    }
  });
  document.addEventListener("change", (event) => {
    const field = event.target;
    if (field instanceof HTMLSelectElement && field.hasAttribute("data-auto-submit")) {
      field.form?.requestSubmit();
    }
  });
}
function initializeFeedbackRatings() {
  document.querySelectorAll("[data-star-rating]").forEach((group) => {
    const choices = [...group.querySelectorAll("[data-rating-value]")];
    const selectedValue = () => Number(group.querySelector("input:checked")?.value || 0);
    const highlight = (value) => {
      choices.forEach((choice) => {
        choice.classList.toggle(
          "is-highlighted",
          Number(choice.dataset.ratingValue) <= value
        );
      });
    };
    group.classList.add("is-enhanced");
    highlight(selectedValue());
    group.addEventListener("pointerover", (event) => {
      const choice = event.target.closest("[data-rating-value]");
      if (choice) highlight(Number(choice.dataset.ratingValue));
    });
    group.addEventListener("pointerleave", () => highlight(selectedValue()));
    group.addEventListener("change", () => {
      const value = selectedValue();
      highlight(value);
      group.querySelector("[data-rating-description]").textContent = `${value} out of 5 · ${choices[value - 1].dataset.ratingLabel}`;
    });
  });
  const message = document.querySelector("[data-feedback-message]");
  const count = document.querySelector("[data-feedback-count]");
  if (message && count) {
    message.addEventListener("input", () => {
      count.textContent = `${Array.from(message.value).length}/2000`;
    });
  }
}
function initializeDeliveryAlerts() {
  const deliveryPopup = document.querySelector(".delivery-popup");
  if (deliveryPopup instanceof HTMLDialogElement && deliveryPopup.open) {
    deliveryPopup.close();
    deliveryPopup.showModal();
  }
  const notificationPoller = document.querySelector("#delivery-notification-poller");
  if (notificationPoller instanceof HTMLElement) {
    const showDeliveryAlerts = (alerts) => {
      if (alerts.length === 0) {
        return;
      }
      const dialog = document.createElement("dialog");
      dialog.className = "delivery-popup";
      dialog.setAttribute("aria-labelledby", "delivery-popup-heading-live");
      const content = document.createElement("div");
      content.className = "delivery-popup-content";
      const eyebrow = document.createElement("span");
      eyebrow.className = "eyebrow";
      eyebrow.textContent = "DELIVERY UPDATE";
      const heading = document.createElement("h2");
      heading.id = "delivery-popup-heading-live";
      heading.textContent = alerts.length === 1 ? "Your order has arrived" : "Your orders have arrived";
      const list = document.createElement("div");
      list.className = "delivery-popup-list";
      alerts.forEach((alert) => {
        const link = document.createElement("a");
        link.className = "delivery-popup-order";
        link.href = alert.url;
        const message = document.createElement("span");
        message.textContent = alert.message;
        const view = document.createElement("span");
        view.className = "notification-link";
        view.textContent = "View order";
        link.append(message, view);
        list.append(link);
      });
      const form = document.createElement("form");
      form.method = "dialog";
      const dismiss = document.createElement("button");
      dismiss.className = "button button-primary delivery-popup-dismiss";
      dismiss.type = "submit";
      dismiss.textContent = "Got it";
      form.append(dismiss);
      content.append(eyebrow, heading, list, form);
      dialog.append(content);
      document.body.append(dialog);
      dialog.addEventListener("close", () => dialog.remove(), { once: true });
      dialog.showModal();
    };
    const pollForDeliveryAlerts = () => {
      if (document.hidden) {
        return;
      }
      fetch(notificationPoller.dataset.url, {
        headers: { Accept: "application/json" },
        cache: "no-store"
      }).then((response) => {
        if (!response.ok) {
          throw new Error(
            `Delivery notification request failed (${response.status}).`
          );
        }
        return response.json();
      }).then((data) => showDeliveryAlerts(data.alerts)).catch((error) => console.error(error));
    };
    window.setInterval(pollForDeliveryAlerts, 15e3);
    pollForDeliveryAlerts();
  }
}
initializeLogin();
initializePasswordVisibility();
initializeFormActions();
initializeDeliveryAlerts();
initializeRiderDialogs();
initializeFeedbackRatings();
