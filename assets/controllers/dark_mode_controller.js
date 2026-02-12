import { Controller } from "@hotwired/stimulus";

/**
 * Dark mode toggle controller.
 *
 * Toggles the "dark" class on <html> and persists the preference in
 * localStorage. Listens for system preference changes when the user
 * has not explicitly chosen a theme.
 *
 * The initial theme is applied by an inline <script> in base.html.twig
 * (and error templates) to prevent FOUC — this controller only manages
 * the toggle buttons.
 *
 * Targets:
 *   - sun:  Icon shown in dark mode (click to switch to light).
 *   - moon: Icon shown in light mode (click to switch to dark).
 *
 * Usage:
 *   <button data-controller="dark-mode" data-action="dark-mode#toggle">
 *     <svg data-dark-mode-target="sun" class="hidden">...</svg>
 *     <svg data-dark-mode-target="moon">...</svg>
 *   </button>
 */
export default class extends Controller {
  static targets = ["sun", "moon"];

  connect() {
    this._onSystemChange = this._onSystemChange.bind(this);
    this._mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
    this._mediaQuery.addEventListener("change", this._onSystemChange);

    this._updateIcons();
  }

  disconnect() {
    this._mediaQuery.removeEventListener("change", this._onSystemChange);
  }

  toggle() {
    const isDark = document.documentElement.classList.contains("dark");

    if (isDark) {
      document.documentElement.classList.remove("dark");
      localStorage.setItem("theme", "light");
    } else {
      document.documentElement.classList.add("dark");
      localStorage.setItem("theme", "dark");
    }

    this._updateIcons();
  }

  _updateIcons() {
    const isDark = document.documentElement.classList.contains("dark");

    this.element.setAttribute("aria-pressed", isDark ? "true" : "false");

    if (this.hasSunTarget && this.hasMoonTarget) {
      if (isDark) {
        this.sunTarget.classList.remove("hidden");
        this.moonTarget.classList.add("hidden");
      } else {
        this.sunTarget.classList.add("hidden");
        this.moonTarget.classList.remove("hidden");
      }
    }
  }

  _onSystemChange(event) {
    // Only apply system preference if user hasn't set an explicit preference
    if (!localStorage.getItem("theme")) {
      if (event.matches) {
        document.documentElement.classList.add("dark");
      } else {
        document.documentElement.classList.remove("dark");
      }
      this._updateIcons();
    }
  }
}
