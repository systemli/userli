import { Controller } from "@hotwired/stimulus";

/**
 * AJAX-based autocomplete controller for searching entities server-side.
 *
 * Renders a text input with a dropdown that fetches results from a JSON
 * endpoint as the user types (debounced). Supports single-select and
 * multi-select (with tag pills) modes.
 *
 * The server must return an array of objects. Each object must have an `id`
 * field. The display label is read from the field specified by `labelField`
 * (default: "email"). Role badges are rendered when a `roles` array is
 * present on the result object.
 *
 * Values:
 *   - url:         The search endpoint URL (required).
 *   - minChars:    Minimum characters before searching (default: 2).
 *   - labelField:  The JSON field to use as display label (default: "email").
 *   - multiple:    Enable multi-select mode with tag pills (default: false).
 *   - filterParam: When set, selecting a value navigates to the current URL
 *                  with this query parameter set to the selected ID. Used for
 *                  filter dropdowns that navigate instead of storing a value.
 *
 * Targets:
 *   - hidden: The hidden <input> that stores the selected entity ID(s).
 *             For multi-select, stores comma-separated IDs.
 *
 * Data attributes on hidden input:
 *   - data-label:    Pre-selected display label (single-select).
 *   - data-selected: JSON array of pre-selected items for multi-select,
 *                    e.g. [{"id":1,"name":"example.org"}].
 *
 * Usage (single-select):
 *   <div data-controller="ajax-autocomplete"
 *        data-ajax-autocomplete-url-value="/settings/users/search">
 *     <input type="hidden" data-ajax-autocomplete-target="hidden"
 *            name="voucher[user]" value="">
 *   </div>
 *
 * Usage (multi-select):
 *   <div data-controller="ajax-autocomplete"
 *        data-ajax-autocomplete-url-value="/settings/domains/search"
 *        data-ajax-autocomplete-label-field-value="name"
 *        data-ajax-autocomplete-multiple-value="true">
 *     <input type="hidden" data-ajax-autocomplete-target="hidden"
 *            name="webhook_endpoint[domains]" value=""
 *            data-selected='[{"id":1,"name":"example.org"}]'>
 *   </div>
 *
 * Usage (filter / navigation):
 *   <div data-controller="ajax-autocomplete"
 *        data-ajax-autocomplete-url-value="/settings/domains/search"
 *        data-ajax-autocomplete-label-field-value="name"
 *        data-ajax-autocomplete-filter-param-value="domain">
 *     <input type="hidden" data-ajax-autocomplete-target="hidden" value="">
 *   </div>
 */
export default class extends Controller {
  static targets = ["hidden"];
  static values = {
    url: String,
    minChars: { type: Number, default: 2 },
    labelField: { type: String, default: "email" },
    multiple: { type: Boolean, default: false },
    filterParam: { type: String, default: "" },
    compact: { type: Boolean, default: false },
  };

  declare hiddenTarget: HTMLInputElement;
  declare urlValue: string;
  declare minCharsValue: number;
  declare labelFieldValue: string;
  declare multipleValue: boolean;
  declare filterParamValue: string;
  declare compactValue: boolean;

  private wrapper!: HTMLDivElement;
  private input!: HTMLInputElement;
  private dropdown!: HTMLUListElement;
  private tagContainer!: HTMLDivElement;
  private isOpen = false;
  private activeIndex = -1;
  private debounceTimer: ReturnType<typeof setTimeout> | null = null;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  private results: Record<string, any>[] = [];
  private selectedLabel = "";
  private abortController: AbortController | null = null;
  // Multi-select: tracked selected items [{id, label}]
  private selectedItems: { id: number; label: string }[] = [];

  connect(): void {
    this.buildUI();
    this._onOutsideClick = this._onOutsideClick.bind(this);
    document.addEventListener("click", this._onOutsideClick);
  }

  disconnect(): void {
    document.removeEventListener("click", this._onOutsideClick);
    if (this.debounceTimer) clearTimeout(this.debounceTimer);
    if (this.abortController) this.abortController.abort();
  }

  private buildUI(): void {
    this.wrapper = document.createElement("div");
    this.wrapper.className = "relative";

    // Multi-select: tag container
    if (this.multipleValue) {
      this.tagContainer = document.createElement("div");
      this.tagContainer.className = "flex flex-wrap gap-1.5 mb-2";
      this.wrapper.appendChild(this.tagContainer);
      this.restoreMultiSelection();
      this.renderTags();
    }

    // Search input
    this.input = document.createElement("input");
    this.input.type = "text";
    this.input.autocomplete = "off";
    this.input.className = this.compactValue
      ? "w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg " +
        "focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 " +
        "transition-colors bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 " +
        "placeholder-gray-400 dark:placeholder-gray-500"
      : "w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg " +
        "focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 " +
        "transition-colors duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 " +
        "placeholder-gray-400 dark:placeholder-gray-500";
    this.input.placeholder = "Search\u2026";
    this.input.setAttribute("role", "combobox");
    this.input.setAttribute("aria-expanded", "false");
    this.input.setAttribute("aria-autocomplete", "list");

    // Pre-selected label for single-select
    if (!this.multipleValue) {
      const preLabel = this.hiddenTarget.dataset.label;
      if (preLabel) {
        this.input.value = preLabel;
        this.selectedLabel = preLabel;
      }
    }

    this.input.addEventListener("input", () => this.onInput());
    this.input.addEventListener("keydown", (e) => this.onKeydown(e));
    this.input.addEventListener("focus", () => {
      // For minChars=0, fetch immediately on focus
      if (this.minCharsValue === 0 && this.results.length === 0 && this.input.value === "") {
        this.fetchResults("");
      } else if (this.input.value && this.results.length > 0) {
        this.open();
      }
    });

    this.wrapper.appendChild(this.input);

    // Dropdown
    this.dropdown = document.createElement("ul");
    this.dropdown.className =
      "absolute z-50 mt-1 w-full max-h-60 overflow-auto rounded-lg border border-gray-200 dark:border-gray-600 " +
      "bg-white dark:bg-gray-700 shadow-lg hidden";
    this.dropdown.setAttribute("role", "listbox");
    this.wrapper.appendChild(this.dropdown);

    // Insert wrapper after hidden input
    this.hiddenTarget.parentNode!.insertBefore(this.wrapper, this.hiddenTarget.nextSibling);
  }

  private restoreMultiSelection(): void {
    const dataSelected = this.hiddenTarget.dataset.selected;
    if (dataSelected) {
      try {
        const items: { id: number; [key: string]: unknown }[] = JSON.parse(dataSelected);
        this.selectedItems = items.map((item) => ({
          id: item.id,
          label: String(item[this.labelFieldValue] || item.id),
        }));
      } catch {
        // ignore parse errors
      }
    }
  }

  private onInput(): void {
    const query = this.input.value.trim();

    // Clear selection if user edits after selecting (single-select only)
    if (!this.multipleValue && this.selectedLabel && this.input.value !== this.selectedLabel) {
      this.hiddenTarget.value = "";
      this.selectedLabel = "";
    }

    if (query.length < this.minCharsValue) {
      // If minChars is 0, still fetch on empty
      if (this.minCharsValue === 0) {
        if (this.debounceTimer) clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => this.fetchResults(query), 300);
        return;
      }
      this.results = [];
      this.close();
      return;
    }

    if (this.debounceTimer) clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(() => this.fetchResults(query), 300);
  }

  private async fetchResults(query: string): Promise<void> {
    if (this.abortController) this.abortController.abort();
    this.abortController = new AbortController();

    try {
      const url = new URL(this.urlValue, window.location.origin);
      url.searchParams.set("q", query);

      const response = await fetch(url.toString(), {
        signal: this.abortController.signal,
        headers: { Accept: "application/json" },
      });

      if (!response.ok) return;

      this.results = await response.json();
      this.activeIndex = -1;
      this.renderDropdown();
      this.open();
    } catch (e) {
      if (e instanceof DOMException && e.name === "AbortError") return;
    }
  }

  private onKeydown(e: KeyboardEvent): void {
    const visibleResults = this.getVisibleResults();
    if (e.key === "ArrowDown") {
      e.preventDefault();
      if (!this.isOpen && visibleResults.length > 0) {
        this.open();
        return;
      }
      this.activeIndex = Math.min(this.activeIndex + 1, visibleResults.length - 1);
      this.updateActiveOption();
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      this.activeIndex = Math.max(this.activeIndex - 1, 0);
      this.updateActiveOption();
    } else if (e.key === "Enter") {
      e.preventDefault();
      if (this.isOpen && this.activeIndex >= 0 && this.activeIndex < visibleResults.length) {
        this.selectResult(visibleResults[this.activeIndex]);
      }
    } else if (e.key === "Escape") {
      this.close();
    } else if (e.key === "Backspace" && this.multipleValue && this.input.value === "") {
      this.removeLastTag();
    }
  }

  private open(): void {
    if (this.isOpen) return;
    this.isOpen = true;
    this.dropdown.classList.remove("hidden");
    this.input.setAttribute("aria-expanded", "true");
  }

  private close(): void {
    if (!this.isOpen) return;
    this.isOpen = false;
    this.dropdown.classList.add("hidden");
    this.input.setAttribute("aria-expanded", "false");
    this.activeIndex = -1;
  }

  /** In multi-select mode, filter out already-selected items. */
  private getVisibleResults(): Record<string, any>[] {
    if (!this.multipleValue) return this.results;
    const selectedIds = new Set(this.selectedItems.map((s) => s.id));
    return this.results.filter((r) => !selectedIds.has(r.id));
  }

  private renderDropdown(): void {
    this.dropdown.innerHTML = "";
    const visible = this.getVisibleResults();

    if (visible.length === 0) {
      const li = document.createElement("li");
      const emptyPadding = this.compactValue ? "px-3 py-1.5" : "px-4 py-3";
      li.className = `${emptyPadding} text-sm text-gray-500 dark:text-gray-400`;
      li.textContent = "No results found";
      this.dropdown.appendChild(li);
      return;
    }

    visible.forEach((result, index) => {
      const li = document.createElement("li");
      const itemPadding = this.compactValue ? "px-3 py-1.5" : "px-4 py-2.5";
      li.className =
        `${itemPadding} text-sm cursor-pointer transition-colors ` +
        "text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900/30 " +
        "hover:text-blue-700 dark:hover:text-blue-300";
      li.setAttribute("role", "option");

      li.innerHTML = this.renderResultHtml(result);

      li.addEventListener("mousedown", (e) => {
        e.preventDefault();
        this.selectResult(result);
      });

      if (index === this.activeIndex) {
        li.classList.add("bg-blue-50", "dark:bg-blue-900/30", "text-blue-700", "dark:text-blue-300");
      }

      this.dropdown.appendChild(li);
    });
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  private renderResultHtml(result: Record<string, any>): string {
    const label = this.escapeHtml(String(result[this.labelFieldValue] || result.id));
    let badges = "";

    if (Array.isArray(result.roles)) {
      if (result.roles.includes("ROLE_SUSPICIOUS")) {
        badges +=
          '<span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium ' +
          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200">Suspicious</span>';
      }
      if (result.roles.includes("ROLE_SPAM")) {
        badges +=
          '<span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium ' +
          'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">Spam</span>';
      }
    }

    return `<div class="flex items-center">${label}${badges}</div>`;
  }

  private updateActiveOption(): void {
    const items = this.dropdown.querySelectorAll("[role='option']");
    items.forEach((item, i) => {
      if (i === this.activeIndex) {
        item.classList.add("bg-blue-50", "dark:bg-blue-900/30", "text-blue-700", "dark:text-blue-300");
        (item as HTMLElement).scrollIntoView({ block: "nearest" });
      } else {
        item.classList.remove("bg-blue-50", "dark:bg-blue-900/30", "text-blue-700", "dark:text-blue-300");
      }
    });
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  private selectResult(result: Record<string, any>): void {
    const label = String(result[this.labelFieldValue] || result.id);

    // Filter mode: navigate instead of storing
    if (this.filterParamValue) {
      const url = new URL(window.location.href);
      url.searchParams.set(this.filterParamValue, String(result.id));
      // Reset to page 1 when changing filter
      url.searchParams.delete("page");
      window.location.href = url.toString();
      return;
    }

    if (this.multipleValue) {
      // Add to selected items
      if (!this.selectedItems.some((s) => s.id === result.id)) {
        this.selectedItems.push({ id: result.id, label });
      }
      this.syncHiddenMulti();
      this.renderTags();
      this.input.value = "";
      this.close();
      this.input.focus();
      // Re-render dropdown to exclude newly selected
      this.renderDropdown();
    } else {
      this.hiddenTarget.value = String(result.id);
      this.input.value = label;
      this.selectedLabel = label;
      this.close();
    }
    this.hiddenTarget.dispatchEvent(new Event("change", { bubbles: true }));
  }

  private syncHiddenMulti(): void {
    this.hiddenTarget.value = this.selectedItems.map((s) => s.id).join(",");
  }

  private renderTags(): void {
    if (!this.tagContainer) return;
    this.tagContainer.innerHTML = "";

    for (const item of this.selectedItems) {
      const tag = document.createElement("span");
      tag.className =
        "inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-sm font-medium " +
        "bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 " +
        "border border-blue-200 dark:border-blue-700";
      tag.innerHTML =
        `<span>${this.escapeHtml(item.label)}</span>` +
        `<button type="button" class="ml-0.5 inline-flex items-center justify-center w-4 h-4 rounded-full ` +
        `text-blue-400 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-200 ` +
        `hover:bg-blue-100 dark:hover:bg-blue-800 transition-colors" aria-label="Remove">` +
        `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">` +
        `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>` +
        `</svg></button>`;

      const removeBtn = tag.querySelector("button")!;
      removeBtn.addEventListener("mousedown", (e) => {
        e.preventDefault();
        this.removeTag(item.id);
      });

      this.tagContainer.appendChild(tag);
    }
  }

  private removeTag(id: number): void {
    this.selectedItems = this.selectedItems.filter((s) => s.id !== id);
    this.syncHiddenMulti();
    this.renderTags();
    this.hiddenTarget.dispatchEvent(new Event("change", { bubbles: true }));
    this.input.focus();
  }

  private removeLastTag(): void {
    if (this.selectedItems.length > 0) {
      this.removeTag(this.selectedItems[this.selectedItems.length - 1].id);
    }
  }

  private _onOutsideClick(event: Event): void {
    if (this.isOpen && !this.wrapper.contains(event.target as Node)) {
      this.close();
      // Restore label if selection exists (single-select only)
      if (!this.multipleValue && this.selectedLabel) {
        this.input.value = this.selectedLabel;
      }
    }
  }

  private escapeHtml(text: string): string {
    return text
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;");
  }
}
