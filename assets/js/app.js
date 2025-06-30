require("../css/app.css");

document.addEventListener("DOMContentLoaded", function () {
  // Event handler that copies the value of element's [data-link]
  // attribute to clipboard.
  //
  // Usage example:
  //
  //   <button data-value="foo">Copy foo to clickboard</button>
  //
  //   let el = document.querySelector('button');
  //   el.addEventListener('click', copyToClipboard);
  //
  // To do so it creates a non visible textarea with the value,
  // selects it and tell the browser to copy the selected content.
  // After the value has been copied, the textarea is removed from
  // DOM again.
  function copyToClipboard(event) {
    let el = document.createElement("textarea");

    el.value = event.currentTarget.dataset.value;
    el.setAttribute("readonly", "");
    el.style.position = "absolute";
    el.style.left = "-9999px";
    document.body.appendChild(el);
    el.select();

    document.execCommand("copy");

    document.body.removeChild(el);
  }

  // Initialize tooltips (replacing Bootstrap's jQuery tooltip)
  function initializeTooltips() {
    const tooltipElements = document.querySelectorAll(
      '[data-toggle="tooltip"]'
    );
    tooltipElements.forEach(function (element) {
      element.addEventListener("mouseenter", function () {
        showTooltip(this);
      });
      element.addEventListener("mouseleave", function () {
        hideTooltip(this);
      });
    });
  }

  function showTooltip(element) {
    const title =
      element.getAttribute("title") ||
      element.getAttribute("data-original-title");
    if (!title) return;

    const tooltip = document.createElement("div");
    tooltip.className = "tooltip fade in";
    tooltip.innerHTML = `<div class="tooltip-inner">${title}</div>`;

    // Position tooltip
    const rect = element.getBoundingClientRect();
    tooltip.style.position = "absolute";
    tooltip.style.top = rect.top - 35 + "px";
    tooltip.style.left = rect.left + rect.width / 2 - 50 + "px";
    tooltip.style.zIndex = "1070";

    element.setAttribute("data-original-title", title);
    element.removeAttribute("title");
    element.tooltip = tooltip;
    document.body.appendChild(tooltip);
  }

  function hideTooltip(element) {
    if (element.tooltip) {
      document.body.removeChild(element.tooltip);
      element.tooltip = null;
      const originalTitle = element.getAttribute("data-original-title");
      if (originalTitle) {
        element.setAttribute("title", originalTitle);
      }
    }
  }

  // Initialize popovers (replacing Bootstrap's jQuery popover)
  function initializePopovers() {
    const popoverElements = document.querySelectorAll(
      '[data-toggle="popover"]'
    );
    popoverElements.forEach(function (element) {
      element.addEventListener("click", function (e) {
        e.preventDefault();
        togglePopover(this);
      });
    });
  }

  function togglePopover(element) {
    const existingPopover = document.querySelector(".popover");
    if (existingPopover) {
      existingPopover.remove();
      return;
    }

    const content = element.getAttribute("data-content");
    const title =
      element.getAttribute("data-title") || element.getAttribute("title");

    if (!content) return;

    const popover = document.createElement("div");
    popover.className = "popover fade in";
    popover.innerHTML = `
      ${title ? `<h3 class="popover-title">${title}</h3>` : ""}
      <div class="popover-content">${content}</div>
    `;

    // Position popover
    const rect = element.getBoundingClientRect();
    popover.style.position = "absolute";
    popover.style.top = rect.bottom + 10 + "px";
    popover.style.left = rect.left + "px";
    popover.style.zIndex = "1070";

    document.body.appendChild(popover);

    // Close popover when clicking outside
    document.addEventListener("click", function closePopover(e) {
      if (!popover.contains(e.target) && e.target !== element) {
        popover.remove();
        document.removeEventListener("click", closePopover);
      }
    });
  }

  // Initialize dropdowns (replacing Bootstrap's jQuery dropdown)
  function initializeDropdowns() {
    const dropdownToggles = document.querySelectorAll(
      '[data-toggle="dropdown"]'
    );
    dropdownToggles.forEach(function (toggle) {
      toggle.addEventListener("click", function (e) {
        e.preventDefault();
        toggleDropdown(this);
      });
    });
  }

  function toggleDropdown(toggle) {
    const dropdown = toggle.closest(".dropdown");
    const dropdownMenu = dropdown.querySelector(".dropdown-menu");
    const isOpen = dropdownMenu && !dropdownMenu.classList.contains("hidden");

    // Close all other dropdowns
    document
      .querySelectorAll(".dropdown .dropdown-menu")
      .forEach(function (menu) {
        menu.classList.add("hidden");
      });

    // Toggle current dropdown
    if (!isOpen && dropdownMenu) {
      dropdownMenu.classList.remove("hidden");

      // Close dropdown when clicking outside
      document.addEventListener("click", function closeDropdown(e) {
        if (!dropdown.contains(e.target)) {
          dropdownMenu.classList.add("hidden");
          document.removeEventListener("click", closeDropdown);
        }
      });
    }
  }

  // Fade out flash notifications (replacing jQuery fadeOut)
  function fadeOutFlashNotifications() {
    const flashNotifications = document.querySelectorAll(".alert");
    flashNotifications.forEach(function (notification) {
      notification.style.transition = "opacity 0.5s";
      setTimeout(function () {
        notification.style.opacity = "0";
        setTimeout(function () {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 500);
      }, 10000);
    });
  }

  // Initialize all components
  initializeTooltips();
  initializePopovers();
  initializeDropdowns();

  // initialize copy-to-clickboard buttons
  document
    .querySelectorAll('[data-button="copy-to-clipboard"]')
    .forEach(function (el) {
      el.addEventListener("click", copyToClipboard);
    });

  // Fade out flash notifications after 10 seconds
  fadeOutFlashNotifications();
});
