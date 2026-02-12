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

/**
 * Dark Mode Toggle Functionality
 *
 * The dark mode respects system preferences by default but allows users
 * to override it. The preference is stored in localStorage.
 */
function initializeDarkMode() {
  const darkModeToggle = document.getElementById("dark-mode-toggle");

  if (!darkModeToggle) return;

  // Update toggle button icon based on current theme
  function updateToggleIcon() {
    const isDark = document.documentElement.classList.contains("dark");
    const sunIcon = darkModeToggle.querySelector(".icon-sun");
    const moonIcon = darkModeToggle.querySelector(".icon-moon");

    // Update aria-pressed for accessibility
    darkModeToggle.setAttribute("aria-pressed", isDark ? "true" : "false");

    if (sunIcon && moonIcon) {
      if (isDark) {
        sunIcon.classList.remove("hidden");
        moonIcon.classList.add("hidden");
      } else {
        sunIcon.classList.add("hidden");
        moonIcon.classList.remove("hidden");
      }
    }
  }

  // Toggle dark mode
  darkModeToggle.addEventListener("click", function () {
    const isDark = document.documentElement.classList.contains("dark");

    if (isDark) {
      document.documentElement.classList.remove("dark");
      localStorage.setItem("theme", "light");
    } else {
      document.documentElement.classList.add("dark");
      localStorage.setItem("theme", "dark");
    }

    updateToggleIcon();
  });

  // Initialize toggle icon
  updateToggleIcon();

  // Listen for system theme changes
  window
    .matchMedia("(prefers-color-scheme: dark)")
    .addEventListener("change", function (e) {
      // Only apply system preference if user hasn't set a preference
      if (!localStorage.getItem("theme")) {
        if (e.matches) {
          document.documentElement.classList.add("dark");
        } else {
          document.documentElement.classList.remove("dark");
        }
        updateToggleIcon();
      }
    });
}

// Make toggleDarkMode globally available
window.toggleDarkMode = function () {
  const isDark = document.documentElement.classList.contains("dark");
  if (isDark) {
    document.documentElement.classList.remove("dark");
    localStorage.setItem("theme", "light");
  } else {
    document.documentElement.classList.add("dark");
    localStorage.setItem("theme", "dark");
  }
};

/**
 * Password Strength Meter
 *
 * Uses zxcvbn-ts to evaluate password strength in real-time.
 * Libraries are lazy-loaded only when a password strength meter is present on the page.
 * The locale is read from the <html lang="..."> attribute to match the application language.
 *
 * Usage in templates:
 *   <div data-password-strength data-password-strength-strong-label="Great password!">
 *     <input type="password" data-password-strength-input />
 *     <div data-password-strength-meter>
 *       <div data-password-strength-segment></div>  (x4)
 *     </div>
 *     <p data-password-strength-feedback></p>
 *   </div>
 */
function initializePasswordStrength() {
  var container = document.querySelector("[data-password-strength]");
  if (!container) return;

  var input = container.querySelector("[data-password-strength-input]");
  var segments = container.querySelectorAll(
    "[data-password-strength-segment]"
  );
  var feedbackEl = container.querySelector(
    "[data-password-strength-feedback]"
  );

  if (!input || segments.length === 0) return;

  var strongLabel = container.getAttribute("data-password-strength-strong-label") || "";
  var minLength = parseInt(container.getAttribute("data-password-strength-min-length") || "0", 10);
  var minLengthLabel = container.getAttribute("data-password-strength-min-length-label") || "";
  var zxcvbnFn = null;
  var debounceTimer = null;

  // Lazy-load zxcvbn-ts and dictionaries, using the page locale for translations
  function loadZxcvbn() {
    var locale = (document.documentElement.lang || "en").split("-")[0].toLowerCase();

    return Promise.all([
      import("@zxcvbn-ts/core"),
      import("@zxcvbn-ts/language-common"),
      import("@zxcvbn-ts/language-en"),
      import("@zxcvbn-ts/language-de"),
    ]).then(function (modules) {
      var core = modules[0];
      var common = modules[1];
      var en = modules[2];
      var de = modules[3];

      // Use German translations for de and gsw (Swiss German), English for everything else
      var translations = (locale === "de" || locale === "gsw")
        ? de.translations
        : en.translations;

      core.zxcvbnOptions.setOptions({
        dictionary: Object.assign(
          {},
          common.dictionary,
          en.dictionary,
          de.dictionary
        ),
        graphs: common.adjacencyGraphs,
        translations: translations,
        useLevenshteinDistance: true,
      });

      zxcvbnFn = core.zxcvbn;
    });
  }

  // Score-to-color mapping for the strength segments
  var scoreColors = [
    { active: ["bg-red-500", "dark:bg-red-400"], count: 1 },
    { active: ["bg-orange-500", "dark:bg-orange-400"], count: 2 },
    { active: ["bg-yellow-500", "dark:bg-yellow-400"], count: 3 },
    { active: ["bg-green-400", "dark:bg-green-500"], count: 4 },
    { active: ["bg-green-600", "dark:bg-green-400"], count: 4 },
  ];

  var inactiveClasses = ["bg-gray-200", "dark:bg-gray-600"];
  var allColorClasses = [
    "bg-red-500",
    "dark:bg-red-400",
    "bg-orange-500",
    "dark:bg-orange-400",
    "bg-yellow-500",
    "dark:bg-yellow-400",
    "bg-green-400",
    "dark:bg-green-500",
    "bg-green-600",
    "dark:bg-green-400",
    "bg-gray-200",
    "dark:bg-gray-600",
  ];

  function updateMeter(score) {
    var config = scoreColors[score] || scoreColors[0];

    segments.forEach(function (segment, index) {
      allColorClasses.forEach(function (cls) {
        segment.classList.remove(cls);
      });

      if (index < config.count) {
        config.active.forEach(function (cls) {
          segment.classList.add(cls);
        });
      } else {
        inactiveClasses.forEach(function (cls) {
          segment.classList.add(cls);
        });
      }
    });
  }

  function resetMeter() {
    segments.forEach(function (segment) {
      allColorClasses.forEach(function (cls) {
        segment.classList.remove(cls);
      });
      inactiveClasses.forEach(function (cls) {
        segment.classList.add(cls);
      });
    });
    if (feedbackEl) {
      feedbackEl.textContent = "";
      feedbackEl.classList.add("hidden");
      feedbackEl.classList.remove("text-green-600", "dark:text-green-400");
      feedbackEl.classList.add("text-gray-500", "dark:text-gray-400");
    }
  }

  function evaluate() {
    var password = input.value;

    if (!password) {
      resetMeter();
      return;
    }

    // Show minimum length hint if password is too short
    if (minLength > 0 && password.length < minLength) {
      updateMeter(0);
      if (feedbackEl && minLengthLabel) {
        feedbackEl.textContent = minLengthLabel;
        feedbackEl.classList.remove("hidden", "text-green-600", "dark:text-green-400");
        feedbackEl.classList.add("text-gray-500", "dark:text-gray-400");
      }
      return;
    }

    if (!zxcvbnFn) return;

    var result = zxcvbnFn(password);
    updateMeter(result.score);

    if (feedbackEl) {
      var text;
      if (result.score >= 3 && strongLabel) {
        // Show positive feedback for strong passwords
        text = strongLabel;
        feedbackEl.classList.remove("text-gray-500", "dark:text-gray-400");
        feedbackEl.classList.add("text-green-600", "dark:text-green-400");
      } else {
        text =
          result.feedback.warning ||
          result.feedback.suggestions.join(" ");
        feedbackEl.classList.remove("text-green-600", "dark:text-green-400");
        feedbackEl.classList.add("text-gray-500", "dark:text-gray-400");
      }
      feedbackEl.textContent = text;
      if (text) {
        feedbackEl.classList.remove("hidden");
      } else {
        feedbackEl.classList.add("hidden");
      }
    }
  }

  // Load zxcvbn-ts when the input is first focused
  input.addEventListener(
    "focus",
    function () {
      if (!zxcvbnFn) {
        loadZxcvbn().catch(function (err) {
          console.error("Failed to load password strength library:", err);
        });
      }
    },
    { once: true }
  );

  // Evaluate on input with debounce
  input.addEventListener("input", function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(evaluate, 150);
  });

  // Initialize with inactive state
  resetMeter();
}

document.addEventListener("DOMContentLoaded", function () {
  // Initialize dark mode toggle
  initializeDarkMode();

  // Initialize password strength meter
  initializePasswordStrength();

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
