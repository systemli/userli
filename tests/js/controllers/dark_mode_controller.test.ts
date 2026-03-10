import { Application } from "@hotwired/stimulus";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import DarkModeController from "../../../assets/controllers/dark_mode_controller";
import { startController } from "../helpers/stimulus";

describe("DarkModeController", () => {
  let matchMediaListeners: Map<string, (event: MediaQueryListEvent) => void>;
  let darkMatches: boolean;
  let app: Application | undefined;

  beforeEach(() => {
    matchMediaListeners = new Map();
    darkMatches = false;

    vi.stubGlobal(
      "matchMedia",
      vi.fn((query: string) => ({
        matches: darkMatches,
        media: query,
        addEventListener: (_event: string, cb: (event: MediaQueryListEvent) => void) => {
          matchMediaListeners.set(query, cb);
        },
        removeEventListener: vi.fn(),
      })),
    );
  });

  afterEach(() => {
    app?.stop();
  });

  it("toggles dark class on document element", async () => {
    const { application } = await startController(
      DarkModeController,
      `<button data-controller="dark-mode"
              data-action="dark-mode#toggle">
        <svg data-dark-mode-target="sun" class="hidden"></svg>
        <svg data-dark-mode-target="moon"></svg>
      </button>`,
      "dark-mode",
    );
    app = application;

    expect(document.documentElement.classList.contains("dark")).toBe(false);

    // Toggle to dark
    document.querySelector("button")!.click();
    expect(document.documentElement.classList.contains("dark")).toBe(true);
    expect(localStorage.getItem("theme")).toBe("dark");

    // Toggle back to light
    document.querySelector("button")!.click();
    expect(document.documentElement.classList.contains("dark")).toBe(false);
    expect(localStorage.getItem("theme")).toBe("light");
  });

  it("updates sun/moon target visibility on toggle", async () => {
    const { application, element } = await startController(
      DarkModeController,
      `<button data-controller="dark-mode"
              data-action="dark-mode#toggle">
        <svg data-dark-mode-target="sun" class="hidden"></svg>
        <svg data-dark-mode-target="moon"></svg>
      </button>`,
      "dark-mode",
    );
    app = application;

    const sun = element.querySelector("[data-dark-mode-target='sun']")!;
    const moon = element.querySelector("[data-dark-mode-target='moon']")!;

    // Initial: light mode — sun hidden, moon visible
    expect(sun.classList.contains("hidden")).toBe(true);
    expect(moon.classList.contains("hidden")).toBe(false);

    // Toggle to dark
    element.click();

    expect(document.documentElement.classList.contains("dark")).toBe(true);
    expect(sun.classList.contains("hidden")).toBe(false);
    expect(moon.classList.contains("hidden")).toBe(true);

    // Toggle back to light
    element.click();

    expect(document.documentElement.classList.contains("dark")).toBe(false);
    expect(sun.classList.contains("hidden")).toBe(true);
    expect(moon.classList.contains("hidden")).toBe(false);
  });

  it("sets aria-pressed based on dark mode state", async () => {
    const { application, element } = await startController(
      DarkModeController,
      `<button data-controller="dark-mode"
              data-action="dark-mode#toggle">
        <svg data-dark-mode-target="sun" class="hidden"></svg>
        <svg data-dark-mode-target="moon"></svg>
      </button>`,
      "dark-mode",
    );
    app = application;

    expect(element.getAttribute("aria-pressed")).toBe("false");

    element.click();
    expect(element.getAttribute("aria-pressed")).toBe("true");

    element.click();
    expect(element.getAttribute("aria-pressed")).toBe("false");
  });

  it("applies system preference change when no explicit theme is set", async () => {
    const { application } = await startController(
      DarkModeController,
      `<button data-controller="dark-mode"
              data-action="dark-mode#toggle">
        <svg data-dark-mode-target="sun" class="hidden"></svg>
        <svg data-dark-mode-target="moon"></svg>
      </button>`,
      "dark-mode",
    );
    app = application;

    // Simulate system switching to dark
    const listener = matchMediaListeners.get("(prefers-color-scheme: dark)");
    expect(listener).toBeDefined();

    listener!({ matches: true } as MediaQueryListEvent);
    expect(document.documentElement.classList.contains("dark")).toBe(true);

    // Simulate system switching back to light
    listener!({ matches: false } as MediaQueryListEvent);
    expect(document.documentElement.classList.contains("dark")).toBe(false);
  });

  it("ignores system preference change when explicit theme is set", async () => {
    const { application, element } = await startController(
      DarkModeController,
      `<button data-controller="dark-mode"
              data-action="dark-mode#toggle">
        <svg data-dark-mode-target="sun" class="hidden"></svg>
        <svg data-dark-mode-target="moon"></svg>
      </button>`,
      "dark-mode",
    );
    app = application;

    // User explicitly selects light theme
    element.click(); // dark
    element.click(); // light — localStorage has "light"

    expect(localStorage.getItem("theme")).toBe("light");
    expect(document.documentElement.classList.contains("dark")).toBe(false);

    // System changes to dark — should be ignored
    const listener = matchMediaListeners.get("(prefers-color-scheme: dark)");
    listener!({ matches: true } as MediaQueryListEvent);
    expect(document.documentElement.classList.contains("dark")).toBe(false);
  });
});
