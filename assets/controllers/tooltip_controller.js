import { Controller } from "@hotwired/stimulus";

/**
 * Lightweight tooltip controller.
 *
 * Displays a positioned tooltip on hover using the element's `title`
 * attribute (or `content` value). The `title` attribute is temporarily
 * removed while the tooltip is visible to prevent the native browser
 * tooltip from appearing alongside it.
 *
 * Usage:
 *   <button data-controller="tooltip" title="Copy to clipboard">
 *     ...
 *   </button>
 *
 * Or with explicit content:
 *   <button data-controller="tooltip" data-tooltip-content-value="Copy">
 *     ...
 *   </button>
 */
export default class extends Controller {
  static values = {
    content: String,
  };

  connect() {
    this._tooltip = null;
    this._title = "";

    this._show = this._show.bind(this);
    this._hide = this._hide.bind(this);

    this.element.addEventListener("mouseenter", this._show);
    this.element.addEventListener("mouseleave", this._hide);
    this.element.addEventListener("focus", this._show);
    this.element.addEventListener("blur", this._hide);
  }

  disconnect() {
    this._hide();
    this.element.removeEventListener("mouseenter", this._show);
    this.element.removeEventListener("mouseleave", this._hide);
    this.element.removeEventListener("focus", this._show);
    this.element.removeEventListener("blur", this._hide);
  }

  _show() {
    const text =
      this.hasContentValue && this.contentValue
        ? this.contentValue
        : this.element.getAttribute("title");

    if (!text) return;

    // Store and remove title to prevent native tooltip
    this._title = this.element.getAttribute("title") || "";
    this.element.removeAttribute("title");

    const tooltip = document.createElement("div");
    tooltip.className = "tooltip fade";
    tooltip.innerHTML = `<div class="tooltip-inner">${text.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</div>`;
    tooltip.setAttribute("role", "tooltip");

    document.body.appendChild(tooltip);

    // Position above the element, centered
    const rect = this.element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    tooltip.style.top = `${rect.top + window.scrollY - tooltipRect.height - 6}px`;
    tooltip.style.left = `${rect.left + window.scrollX + rect.width / 2 - tooltipRect.width / 2}px`;

    // Trigger transition
    requestAnimationFrame(() => tooltip.classList.add("in"));

    this._tooltip = tooltip;
  }

  _hide() {
    if (this._tooltip) {
      this._tooltip.remove();
      this._tooltip = null;
    }

    // Restore title
    if (this._title) {
      this.element.setAttribute("title", this._title);
      this._title = "";
    }
  }
}
