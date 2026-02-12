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
export default class extends Controller<HTMLElement> {
  declare hasSunTarget: boolean;
  declare sunTarget: HTMLElement;
  declare hasMoonTarget: boolean;
  declare moonTarget: HTMLElement;

  static targets = ["sun", "moon"];

  private _mediaQuery!: MediaQueryList;

  connect(): void {
    this._mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
    this._mediaQuery.addEventListener("change", this._onSystemChange);

    this._updateIcons();
  }

  disconnect(): void {
    this._mediaQuery.removeEventListener("change", this._onSystemChange);
  }

  toggle(): void {
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

  private _updateIcons(): void {
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

  private _onSystemChange = (event: MediaQueryListEvent): void => {
    // Only apply system preference if user hasn't set an explicit preference
    if (!localStorage.getItem("theme")) {
      if (event.matches) {
        document.documentElement.classList.add("dark");
      } else {
        document.documentElement.classList.remove("dark");
      }
      this._updateIcons();
    }
  };
}
