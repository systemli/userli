require("../css/app.css");
import DOMPurify from "dompurify";

/**
 * HTML Sanitization with DOMPurify
 *
 * This application uses DOMPurify to safely sanitize HTML content and prevent XSS attacks.
 *
 * USAGE IN TWIG TEMPLATES:
 *
 * 1. For user-generated or dynamic HTML content, use |safe_html instead of |raw:
 *    ❌ BAD:  {{ content|raw }}
 *    ✅ GOOD: {{ content|safe_html }}
 *
 * 2. The |safe_html filter provides:
 *    - Server-side basic sanitization
 *    - Client-side DOMPurify sanitization
 *    - Allows safe HTML tags: b, i, em, strong, u, br, p, span, div, a
 *    - Removes dangerous attributes and JavaScript URLs
 *
 * USAGE IN JAVASCRIPT:
 *
 * 1. Use the global sanitizeHTML() function for dynamic content:
 *    element.innerHTML = sanitizeHTML(userContent);
 *
 * 2. Content marked with data-safe-html is automatically sanitized on page load
 *
 * SECURITY BENEFITS:
 * - Prevents XSS attacks
 * - Removes malicious scripts and attributes
 * - Maintains legitimate HTML formatting
 * - Double-layer protection (server + client)
 */

// Utility function to safely sanitize HTML content
function sanitizeHTML(html) {
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS: [
      "b",
      "i",
      "em",
      "strong",
      "u",
      "br",
      "p",
      "span",
      "div",
      "a",
    ],
    ALLOWED_ATTR: ["href", "target", "class"],
    ALLOW_DATA_ATTR: false,
  });
}

// Make sanitizeHTML globally available
window.sanitizeHTML = sanitizeHTML;

// Automatically sanitize content marked with data-safe-html attribute
function initializeSafeHtml() {
  const elements = document.querySelectorAll("[data-safe-html]");
  elements.forEach((element) => {
    const originalContent = element.innerHTML;
    element.innerHTML = sanitizeHTML(originalContent);
    element.removeAttribute("data-safe-html");
  });
}

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
    const button = event.currentTarget;
    const textToCopy = button.dataset.value;

    // Use modern clipboard API with fallback
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard
        .writeText(textToCopy)
        .then(function () {
          showCopySuccess(button);
        })
        .catch(function (err) {
          console.error("Copy failed:", err);
          fallbackCopy(textToCopy);
          showCopySuccess(button);
        });
    } else {
      // Fallback for older browsers or non-secure contexts
      fallbackCopy(textToCopy);
      showCopySuccess(button);
    }
  }

  function fallbackCopy(text) {
    let el = document.createElement("textarea");
    el.value = text;
    el.setAttribute("readonly", "");
    el.style.position = "absolute";
    el.style.left = "-9999px";
    document.body.appendChild(el);
    el.select();
    document.execCommand("copy");
    document.body.removeChild(el);
  }

  function showCopySuccess(button) {
    // Store original content and update button to show success
    const originalContent = button.innerHTML;
    button.innerHTML = `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>`;

    // Add success styling
    button.classList.add("text-green-600", "border-green-300", "bg-green-50");
    const originalClasses = [];

    // Store and remove original color classes
    if (button.classList.contains("text-gray-700")) {
      originalClasses.push("text-gray-700");
      button.classList.remove("text-gray-700");
    }
    if (button.classList.contains("text-green-700")) {
      originalClasses.push("text-green-700");
      button.classList.remove("text-green-700");
    }
    if (button.classList.contains("bg-gray-100")) {
      originalClasses.push("bg-gray-100");
      button.classList.remove("bg-gray-100");
    }
    if (button.classList.contains("bg-white")) {
      originalClasses.push("bg-white");
      button.classList.remove("bg-white");
    }
    if (button.classList.contains("border-gray-300")) {
      originalClasses.push("border-gray-300");
      button.classList.remove("border-gray-300");
    }

    // Reset after 2 seconds
    setTimeout(function () {
      button.innerHTML = originalContent;
      button.classList.remove(
        "text-green-600",
        "border-green-300",
        "bg-green-50"
      );
      originalClasses.forEach((cls) => button.classList.add(cls));
    }, 2000);
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
    tooltip.innerHTML = `<div class="tooltip-inner">${sanitizeHTML(
      title
    )}</div>`;

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
      ${title ? `<h3 class="popover-title">${sanitizeHTML(title)}</h3>` : ""}
      <div class="popover-content">${sanitizeHTML(content)}</div>
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
  initializeSafeHtml();

  // initialize copy-to-clickboard buttons
  document
    .querySelectorAll('[data-button="copy-to-clipboard"]')
    .forEach(function (el) {
      el.addEventListener("click", copyToClipboard);
    });

  // Fade out flash notifications after 10 seconds
  fadeOutFlashNotifications();
});
