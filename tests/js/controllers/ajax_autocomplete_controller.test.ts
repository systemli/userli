import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import AjaxAutocompleteController from "../../../assets/controllers/ajax_autocomplete_controller";
import { startController } from "../helpers/stimulus";

// jsdom does not implement scrollIntoView
Element.prototype.scrollIntoView = vi.fn();

const SINGLE_HTML = `
  <div data-controller="ajax-autocomplete"
       data-ajax-autocomplete-url-value="/api/users/search"
       data-ajax-autocomplete-min-chars-value="2"
       data-ajax-autocomplete-label-field-value="email">
    <input type="hidden"
           data-ajax-autocomplete-target="hidden"
           name="user"
           value="">
  </div>`;

const SINGLE_PRESELECTED_HTML = `
  <div data-controller="ajax-autocomplete"
       data-ajax-autocomplete-url-value="/api/users/search"
       data-ajax-autocomplete-label-field-value="email">
    <input type="hidden"
           data-ajax-autocomplete-target="hidden"
           name="user"
           value="42"
           data-label="admin@example.org">
  </div>`;

const MULTI_HTML = `
  <div data-controller="ajax-autocomplete"
       data-ajax-autocomplete-url-value="/api/domains/search"
       data-ajax-autocomplete-label-field-value="name"
       data-ajax-autocomplete-multiple-value="true">
    <input type="hidden"
           data-ajax-autocomplete-target="hidden"
           name="domains"
           value=""
           data-selected='[{"id":1,"name":"example.org"},{"id":2,"name":"test.de"}]'>
  </div>`;

function mockFetchResponse(data: unknown): void {
  vi.stubGlobal(
    "fetch",
    vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(data),
    }),
  );
}

describe("AjaxAutocompleteController", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    mockFetchResponse([]);
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  describe("UI construction", () => {
    it("builds a text input and dropdown on connect", async () => {
      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const wrapper = element.querySelector(".relative");
      expect(wrapper).not.toBeNull();

      const textInput = wrapper!.querySelector("input[type='text']");
      expect(textInput).not.toBeNull();
      expect(textInput!.getAttribute("role")).toBe("combobox");

      const dropdown = wrapper!.querySelector("ul[role='listbox']");
      expect(dropdown).not.toBeNull();
      expect(dropdown!.classList.contains("hidden")).toBe(true);
    });

    it("pre-fills the input with the label from data attribute", async () => {
      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_PRESELECTED_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      expect(textInput.value).toBe("admin@example.org");
    });

    it("restores multi-select tags from data-selected", async () => {
      const { element } = await startController(
        AjaxAutocompleteController,
        MULTI_HTML,
        "ajax-autocomplete",
      );

      // The tag container is the first child div inside the wrapper.
      // Each tag is a direct <span> child of the tag container.
      const tagContainer = element.querySelector(".relative > div:first-child")!;
      const tags = tagContainer.querySelectorAll(":scope > span");
      // Two pre-selected items should render as tag pills
      expect(tags.length).toBe(2);
    });
  });

  describe("search and fetch", () => {
    it("does not fetch when input is below minChars", async () => {
      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;

      textInput.value = "a"; // below minChars=2
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      expect(fetch).not.toHaveBeenCalled();
    });

    it("fetches results after debounce when minChars is reached", async () => {
      mockFetchResponse([
        { id: 1, email: "alice@example.org" },
        { id: 2, email: "bob@example.org" },
      ]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;

      textInput.value = "ali";
      textInput.dispatchEvent(new Event("input"));

      // Not yet fetched (debounce pending)
      expect(fetch).not.toHaveBeenCalled();

      // Advance past debounce
      await vi.advanceTimersByTimeAsync(300);

      expect(fetch).toHaveBeenCalled();
      const url = new URL(
        (fetch as ReturnType<typeof vi.fn>).mock.calls[0][0],
      );
      expect(url.pathname).toBe("/api/users/search");
      expect(url.searchParams.get("q")).toBe("ali");
    });

    it("renders results in the dropdown", async () => {
      mockFetchResponse([
        { id: 1, email: "alice@example.org" },
        { id: 2, email: "bob@example.org" },
      ]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const dropdown = element.querySelector("ul[role='listbox']")!;

      textInput.value = "test";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      const options = dropdown.querySelectorAll("[role='option']");
      expect(options.length).toBe(2);
      expect(options[0].textContent).toContain("alice@example.org");
      expect(options[1].textContent).toContain("bob@example.org");
    });

    it("shows 'No results found' when response is empty", async () => {
      mockFetchResponse([]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const dropdown = element.querySelector("ul[role='listbox']")!;

      textInput.value = "nonexistent";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      expect(dropdown.textContent).toContain("No results found");
    });
  });

  describe("single-select", () => {
    it("sets hidden input value and input label on selection", async () => {
      mockFetchResponse([{ id: 42, email: "alice@example.org" }]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const hiddenInput = element.querySelector(
        "input[type='hidden']",
      ) as HTMLInputElement;

      textInput.value = "alice";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      // Select via mousedown on first option
      const option = element.querySelector("[role='option']") as HTMLElement;
      option.dispatchEvent(
        new MouseEvent("mousedown", { bubbles: true }),
      );

      expect(hiddenInput.value).toBe("42");
      expect(textInput.value).toBe("alice@example.org");
    });

    it("clears hidden value when user edits after selecting", async () => {
      mockFetchResponse([{ id: 42, email: "alice@example.org" }]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const hiddenInput = element.querySelector(
        "input[type='hidden']",
      ) as HTMLInputElement;

      // Select a result
      textInput.value = "alice";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      const option = element.querySelector("[role='option']") as HTMLElement;
      option.dispatchEvent(
        new MouseEvent("mousedown", { bubbles: true }),
      );
      expect(hiddenInput.value).toBe("42");

      // Edit the input
      textInput.value = "alic";
      textInput.dispatchEvent(new Event("input"));

      expect(hiddenInput.value).toBe("");
    });
  });

  describe("multi-select", () => {
    it("adds tags and updates hidden input with comma-separated IDs", async () => {
      mockFetchResponse([{ id: 3, name: "new-domain.org" }]);

      const { element } = await startController(
        AjaxAutocompleteController,
        MULTI_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const hiddenInput = element.querySelector(
        "input[type='hidden']",
      ) as HTMLInputElement;

      textInput.value = "new";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      const option = element.querySelector("[role='option']") as HTMLElement;
      option.dispatchEvent(
        new MouseEvent("mousedown", { bubbles: true }),
      );

      expect(hiddenInput.value).toBe("1,2,3");
      // Input is cleared after selection
      expect(textInput.value).toBe("");
    });

    it("removes a tag when remove button is clicked", async () => {
      const { element } = await startController(
        AjaxAutocompleteController,
        MULTI_HTML,
        "ajax-autocomplete",
      );

      const hiddenInput = element.querySelector(
        "input[type='hidden']",
      ) as HTMLInputElement;

      // Initial: 2 items from data-selected
      const tagContainer = element.querySelector(
        ".relative > div:first-child",
      )!;
      let removeButtons = tagContainer.querySelectorAll("button");
      expect(removeButtons.length).toBe(2);

      // Remove the first tag
      removeButtons[0].dispatchEvent(
        new MouseEvent("mousedown", { bubbles: true }),
      );

      removeButtons = tagContainer.querySelectorAll("button");
      expect(removeButtons.length).toBe(1);
      expect(hiddenInput.value).toBe("2");
    });

    it("removes last tag on Backspace when input is empty", async () => {
      const { element } = await startController(
        AjaxAutocompleteController,
        MULTI_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const hiddenInput = element.querySelector(
        "input[type='hidden']",
      ) as HTMLInputElement;

      textInput.value = "";
      textInput.dispatchEvent(
        new KeyboardEvent("keydown", { key: "Backspace", bubbles: true }),
      );

      // Last item (id=2) should be removed
      expect(hiddenInput.value).toBe("1");
    });
  });

  describe("keyboard navigation", () => {
    it("navigates options with ArrowDown and ArrowUp", async () => {
      mockFetchResponse([
        { id: 1, email: "alice@example.org" },
        { id: 2, email: "bob@example.org" },
      ]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;

      textInput.value = "test";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      // Arrow down to first option
      textInput.dispatchEvent(
        new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true }),
      );

      const options = element.querySelectorAll("[role='option']");
      expect(
        options[0].classList.contains("bg-blue-50"),
      ).toBe(true);

      // Arrow down to second option
      textInput.dispatchEvent(
        new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true }),
      );
      expect(
        options[1].classList.contains("bg-blue-50"),
      ).toBe(true);

      // Arrow up back to first
      textInput.dispatchEvent(
        new KeyboardEvent("keydown", { key: "ArrowUp", bubbles: true }),
      );
      expect(
        options[0].classList.contains("bg-blue-50"),
      ).toBe(true);
    });

    it("selects active option on Enter", async () => {
      mockFetchResponse([{ id: 1, email: "alice@example.org" }]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const hiddenInput = element.querySelector(
        "input[type='hidden']",
      ) as HTMLInputElement;

      textInput.value = "alice";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      // Navigate to first option
      textInput.dispatchEvent(
        new KeyboardEvent("keydown", { key: "ArrowDown", bubbles: true }),
      );

      // Select with Enter
      textInput.dispatchEvent(
        new KeyboardEvent("keydown", { key: "Enter", bubbles: true }),
      );

      expect(hiddenInput.value).toBe("1");
      expect(textInput.value).toBe("alice@example.org");
    });

    it("closes dropdown on Escape", async () => {
      mockFetchResponse([{ id: 1, email: "alice@example.org" }]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const dropdown = element.querySelector("ul[role='listbox']")!;

      textInput.value = "alice";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      expect(dropdown.classList.contains("hidden")).toBe(false);

      textInput.dispatchEvent(
        new KeyboardEvent("keydown", { key: "Escape", bubbles: true }),
      );

      expect(dropdown.classList.contains("hidden")).toBe(true);
    });
  });

  describe("outside click", () => {
    it("closes dropdown on click outside", async () => {
      mockFetchResponse([{ id: 1, email: "alice@example.org" }]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const dropdown = element.querySelector("ul[role='listbox']")!;

      textInput.value = "alice";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      expect(dropdown.classList.contains("hidden")).toBe(false);

      // Click outside
      document.dispatchEvent(new Event("click", { bubbles: true }));

      expect(dropdown.classList.contains("hidden")).toBe(true);
    });

    it("restores selected label on outside click (single-select)", async () => {
      mockFetchResponse([{ id: 42, email: "alice@example.org" }]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;
      const hiddenInput = element.querySelector(
        "input[type='hidden']",
      ) as HTMLInputElement;

      // Make a selection so selectedLabel is set
      textInput.value = "alice";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      const option = element.querySelector("[role='option']") as HTMLElement;
      option.dispatchEvent(
        new MouseEvent("mousedown", { bubbles: true }),
      );

      expect(hiddenInput.value).toBe("42");
      expect(textInput.value).toBe("alice@example.org");

      // Re-fetch results to re-open the dropdown without changing the
      // input text (input value still matches selectedLabel).
      mockFetchResponse([{ id: 42, email: "alice@example.org" }]);
      textInput.dispatchEvent(new Event("focus"));
      textInput.value = "alice@example.org";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      // Click outside while dropdown is open — should keep the label
      document.dispatchEvent(new Event("click", { bubbles: true }));

      expect(textInput.value).toBe("alice@example.org");
    });
  });

  describe("role badges", () => {
    it("renders suspicious and spam badges for results with roles", async () => {
      mockFetchResponse([
        {
          id: 1,
          email: "bad@example.org",
          roles: ["ROLE_USER", "ROLE_SUSPICIOUS", "ROLE_SPAM"],
        },
      ]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;

      textInput.value = "bad";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      const option = element.querySelector("[role='option']")!;
      expect(option.innerHTML).toContain("Suspicious");
      expect(option.innerHTML).toContain("Spam");
    });
  });

  describe("HTML escaping", () => {
    it("escapes HTML in result labels", async () => {
      mockFetchResponse([
        { id: 1, email: '<script>alert("xss")</script>' },
      ]);

      const { element } = await startController(
        AjaxAutocompleteController,
        SINGLE_HTML,
        "ajax-autocomplete",
      );

      const textInput = element.querySelector(
        "input[type='text']",
      ) as HTMLInputElement;

      textInput.value = "xss";
      textInput.dispatchEvent(new Event("input"));
      await vi.advanceTimersByTimeAsync(300);

      const option = element.querySelector("[role='option']")!;
      expect(option.innerHTML).not.toContain("<script>");
      expect(option.innerHTML).toContain("&lt;script&gt;");
    });
  });
});
