import { describe, it, expect } from "vitest";
import DropdownController from "../../../assets/controllers/dropdown_controller";
import { startController } from "../helpers/stimulus";

const ANIMATED_HTML = `
  <div data-controller="dropdown">
    <button data-dropdown-target="button"
            data-action="dropdown#toggle">Menu</button>
    <div data-dropdown-target="menu"
         class="opacity-0 scale-95 pointer-events-none">
      <a href="/a">Item A</a>
      <a href="/b">Item B</a>
    </div>
    <svg data-dropdown-target="arrow"></svg>
  </div>`;

const SIMPLE_HTML = `
  <div data-controller="dropdown" data-dropdown-mode-value="simple">
    <button data-dropdown-target="button"
            data-action="dropdown#toggle">Menu</button>
    <div data-dropdown-target="menu" class="hidden">
      <a href="/a">Item A</a>
    </div>
    <svg data-dropdown-target="iconOpen"></svg>
    <svg data-dropdown-target="iconClose" class="hidden"></svg>
  </div>`;

describe("DropdownController", () => {
  describe("animated mode (default)", () => {
    it("opens the menu with opacity and scale transitions", async () => {
      const { element } = await startController(
        DropdownController,
        ANIMATED_HTML,
        "dropdown",
      );

      const menu = element.querySelector("[data-dropdown-target='menu']")!;
      const button = element.querySelector("button")!;

      button.click();

      expect(menu.classList.contains("opacity-100")).toBe(true);
      expect(menu.classList.contains("scale-100")).toBe(true);
      expect(menu.classList.contains("opacity-0")).toBe(false);
      expect(menu.classList.contains("scale-95")).toBe(false);
      expect(menu.classList.contains("pointer-events-none")).toBe(false);
    });

    it("closes the menu restoring initial classes", async () => {
      const { element } = await startController(
        DropdownController,
        ANIMATED_HTML,
        "dropdown",
      );

      const menu = element.querySelector("[data-dropdown-target='menu']")!;
      const button = element.querySelector("button")!;

      // Open then close
      button.click();
      button.click();

      expect(menu.classList.contains("opacity-0")).toBe(true);
      expect(menu.classList.contains("scale-95")).toBe(true);
      expect(menu.classList.contains("pointer-events-none")).toBe(true);
      expect(menu.classList.contains("opacity-100")).toBe(false);
      expect(menu.classList.contains("scale-100")).toBe(false);
    });

    it("rotates the arrow target when open", async () => {
      const { element } = await startController(
        DropdownController,
        ANIMATED_HTML,
        "dropdown",
      );

      const arrow = element.querySelector("[data-dropdown-target='arrow']")!;
      const button = element.querySelector("button")!;

      button.click();
      expect(arrow.classList.contains("rotate-180")).toBe(true);

      button.click();
      expect(arrow.classList.contains("rotate-180")).toBe(false);
    });

    it("sets aria-expanded on the button target", async () => {
      const { element } = await startController(
        DropdownController,
        ANIMATED_HTML,
        "dropdown",
      );

      const button = element.querySelector("button")!;

      button.click();
      expect(button.getAttribute("aria-expanded")).toBe("true");

      button.click();
      expect(button.getAttribute("aria-expanded")).toBe("false");
    });
  });

  describe("simple mode", () => {
    it("toggles hidden class on the menu", async () => {
      const { element } = await startController(
        DropdownController,
        SIMPLE_HTML,
        "dropdown",
      );

      const menu = element.querySelector("[data-dropdown-target='menu']")!;
      const button = element.querySelector("button")!;

      button.click();
      expect(menu.classList.contains("hidden")).toBe(false);

      button.click();
      expect(menu.classList.contains("hidden")).toBe(true);
    });

    it("swaps iconOpen and iconClose targets", async () => {
      const { element } = await startController(
        DropdownController,
        SIMPLE_HTML,
        "dropdown",
      );

      const iconOpen = element.querySelector(
        "[data-dropdown-target='iconOpen']",
      )!;
      const iconClose = element.querySelector(
        "[data-dropdown-target='iconClose']",
      )!;
      const button = element.querySelector("button")!;

      // Open: iconOpen hidden, iconClose visible
      button.click();
      expect(iconOpen.classList.contains("hidden")).toBe(true);
      expect(iconClose.classList.contains("hidden")).toBe(false);

      // Close: iconOpen visible, iconClose hidden
      button.click();
      expect(iconOpen.classList.contains("hidden")).toBe(false);
      expect(iconClose.classList.contains("hidden")).toBe(true);
    });
  });

  describe("keyboard and outside click", () => {
    it("closes on Escape and focuses the button", async () => {
      const { element } = await startController(
        DropdownController,
        ANIMATED_HTML,
        "dropdown",
      );

      const menu = element.querySelector("[data-dropdown-target='menu']")!;
      const button = element.querySelector("button")!;

      button.click();
      expect(menu.classList.contains("opacity-100")).toBe(true);

      document.dispatchEvent(
        new KeyboardEvent("keydown", { key: "Escape", bubbles: true }),
      );

      expect(menu.classList.contains("opacity-0")).toBe(true);
      expect(document.activeElement).toBe(button);
    });

    it("closes on outside click", async () => {
      const { element } = await startController(
        DropdownController,
        ANIMATED_HTML,
        "dropdown",
      );

      const menu = element.querySelector("[data-dropdown-target='menu']")!;
      const button = element.querySelector("button")!;

      button.click();
      expect(menu.classList.contains("opacity-100")).toBe(true);

      // Click outside
      document.dispatchEvent(new Event("click", { bubbles: true }));

      expect(menu.classList.contains("opacity-0")).toBe(true);
    });

    it("does not close on click inside the element", async () => {
      const { element } = await startController(
        DropdownController,
        ANIMATED_HTML,
        "dropdown",
      );

      const menu = element.querySelector("[data-dropdown-target='menu']")!;
      const button = element.querySelector("button")!;

      button.click();
      expect(menu.classList.contains("opacity-100")).toBe(true);

      // Click inside the dropdown element
      const link = element.querySelector("a")!;
      link.dispatchEvent(new Event("click", { bubbles: true }));

      expect(menu.classList.contains("opacity-100")).toBe(true);
    });
  });
});
