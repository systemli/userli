import { Controller } from "@hotwired/stimulus";

/**
 * Navigate controller — redirects the browser when a select value changes.
 *
 * Usage:
 *   <select data-controller="navigate"
 *           data-action="change->navigate#go">
 *     <option value="/path/to/page">Page</option>
 *   </select>
 */
export default class extends Controller {
  go(event: Event): void {
    const target = event.target as HTMLSelectElement;

    try {
      const parsed = new URL(target.value, window.location.origin);
      if (parsed.origin === window.location.origin) {
        window.location.assign(parsed.href);
      }
    } catch {
      // ignore invalid URLs
    }
  }
}
