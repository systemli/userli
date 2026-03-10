import { describe, it, expect } from "vitest";
import TooltipController from "../../../assets/controllers/tooltip_controller";
import { startController } from "../helpers/stimulus";

describe("TooltipController", () => {
  it("creates a tooltip element on mouseenter", async () => {
    const { element } = await startController(
      TooltipController,
      `<button data-controller="tooltip" title="Copy to clipboard">
        Click me
      </button>`,
      "tooltip",
    );

    element.dispatchEvent(new Event("mouseenter"));

    const tooltip = document.querySelector("[role='tooltip']");
    expect(tooltip).not.toBeNull();
    expect(tooltip!.querySelector(".tooltip-inner")!.textContent).toBe(
      "Copy to clipboard",
    );
  });

  it("removes the tooltip element on mouseleave", async () => {
    const { element } = await startController(
      TooltipController,
      `<button data-controller="tooltip" title="Copy to clipboard">
        Click me
      </button>`,
      "tooltip",
    );

    element.dispatchEvent(new Event("mouseenter"));
    expect(document.querySelector("[role='tooltip']")).not.toBeNull();

    element.dispatchEvent(new Event("mouseleave"));
    expect(document.querySelector("[role='tooltip']")).toBeNull();
  });

  it("temporarily removes the title attribute while tooltip is visible", async () => {
    const { element } = await startController(
      TooltipController,
      `<button data-controller="tooltip" title="Help text">
        Button
      </button>`,
      "tooltip",
    );

    element.dispatchEvent(new Event("mouseenter"));
    expect(element.hasAttribute("title")).toBe(false);

    element.dispatchEvent(new Event("mouseleave"));
    expect(element.getAttribute("title")).toBe("Help text");
  });

  it("uses content value over title attribute", async () => {
    const { element } = await startController(
      TooltipController,
      `<button data-controller="tooltip"
              data-tooltip-content-value="Custom content"
              title="Title text">
        Button
      </button>`,
      "tooltip",
    );

    element.dispatchEvent(new Event("mouseenter"));

    const tooltip = document.querySelector("[role='tooltip']");
    expect(tooltip!.querySelector(".tooltip-inner")!.textContent).toBe(
      "Custom content",
    );
  });

  it("escapes HTML in tooltip text", async () => {
    const { element } = await startController(
      TooltipController,
      `<button data-controller="tooltip" title="<script>alert('xss')</script>">
        Button
      </button>`,
      "tooltip",
    );

    element.dispatchEvent(new Event("mouseenter"));

    const inner = document.querySelector(".tooltip-inner")!;
    expect(inner.innerHTML).not.toContain("<script>");
    expect(inner.innerHTML).toContain("&lt;script&gt;");
  });

  it("does not create a tooltip when no title or content value", async () => {
    const { element } = await startController(
      TooltipController,
      `<button data-controller="tooltip">Button</button>`,
      "tooltip",
    );

    element.dispatchEvent(new Event("mouseenter"));
    expect(document.querySelector("[role='tooltip']")).toBeNull();
  });

  it("shows tooltip on focus and hides on blur", async () => {
    const { element } = await startController(
      TooltipController,
      `<button data-controller="tooltip" title="Focus tooltip">
        Button
      </button>`,
      "tooltip",
    );

    element.dispatchEvent(new Event("focus"));
    expect(document.querySelector("[role='tooltip']")).not.toBeNull();

    element.dispatchEvent(new Event("blur"));
    expect(document.querySelector("[role='tooltip']")).toBeNull();
  });

  it("cleans up tooltip on disconnect", async () => {
    const { element } = await startController(
      TooltipController,
      `<button data-controller="tooltip" title="Will be removed">
        Button
      </button>`,
      "tooltip",
    );

    element.dispatchEvent(new Event("mouseenter"));
    expect(document.querySelector("[role='tooltip']")).not.toBeNull();

    // Removing the element triggers disconnect()
    element.remove();
    await new Promise<void>((resolve) => queueMicrotask(resolve));

    expect(document.querySelector("[role='tooltip']")).toBeNull();
  });
});
