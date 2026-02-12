import DOMPurify from "dompurify";

declare global {
  interface Window {
    sanitizeHTML: typeof sanitizeHTML;
  }
}

/**
 * Sanitize HTML with DOMPurify.
 *
 * Allows only a safe subset of tags and attributes so user-generated
 * content can be rendered without XSS risk.
 *
 * Usage in TypeScript:
 *   import { sanitizeHTML } from '../js/sanitize';
 *   element.innerHTML = sanitizeHTML(userContent);
 *
 * The function is also available globally as window.sanitizeHTML for
 * legacy callers and the SafeHtmlExtension Twig filter.
 */
export function sanitizeHTML(html: string): string {
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

// Make sanitizeHTML globally available for legacy code and safe_html Twig filter
window.sanitizeHTML = sanitizeHTML;

/**
 * Automatically sanitize all elements marked with data-safe-html.
 *
 * Called once on DOMContentLoaded. Each matching element's innerHTML is
 * passed through sanitizeHTML and the marker attribute is removed.
 */
export function initializeSafeHtml(): void {
  const elements = document.querySelectorAll("[data-safe-html]");
  elements.forEach((element) => {
    const originalContent = element.innerHTML;
    element.innerHTML = sanitizeHTML(originalContent);
    element.removeAttribute("data-safe-html");
  });
}
