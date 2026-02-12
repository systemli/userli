require("../css/app.css");
import "../bootstrap.js";
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
  initializeSafeHtml();
});
