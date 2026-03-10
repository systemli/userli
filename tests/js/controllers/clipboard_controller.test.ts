import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import ClipboardController from "../../../assets/controllers/clipboard_controller";
import { startController } from "../helpers/stimulus";

describe("ClipboardController", () => {
  beforeEach(() => {
    vi.useFakeTimers();

    // Mock navigator.clipboard
    Object.assign(navigator, {
      clipboard: {
        writeText: vi.fn().mockResolvedValue(undefined),
      },
    });
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("copies content value to clipboard", async () => {
    const { element } = await startController(
      ClipboardController,
      `<div data-controller="clipboard"
           data-clipboard-content-value="secret-token-123"
           data-clipboard-success-duration-value="2000">
        <button data-clipboard-target="button"
                data-action="clipboard#copy">Copy</button>
      </div>`,
      "clipboard",
    );

    const button = element.querySelector("button")!;
    button.click();

    expect(navigator.clipboard.writeText).toHaveBeenCalledWith(
      "secret-token-123",
    );
  });

  it("copies source target value to clipboard", async () => {
    const { element } = await startController(
      ClipboardController,
      `<div data-controller="clipboard"
           data-clipboard-success-duration-value="2000">
        <input data-clipboard-target="source" value="input-value" />
        <button data-clipboard-target="button"
                data-action="clipboard#copy">Copy</button>
      </div>`,
      "clipboard",
    );

    const button = element.querySelector("button")!;
    button.click();

    expect(navigator.clipboard.writeText).toHaveBeenCalledWith("input-value");
  });

  it("does nothing when no source or content is available", async () => {
    const { element } = await startController(
      ClipboardController,
      `<div data-controller="clipboard"
           data-clipboard-success-duration-value="2000">
        <button data-clipboard-target="button"
                data-action="clipboard#copy">Copy</button>
      </div>`,
      "clipboard",
    );

    const button = element.querySelector("button")!;
    button.click();

    expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
  });

  it("shows default checkmark SVG on successful copy", async () => {
    const { element } = await startController(
      ClipboardController,
      `<div data-controller="clipboard"
           data-clipboard-content-value="text"
           data-clipboard-success-duration-value="2000">
        <button data-clipboard-target="button"
                data-action="clipboard#copy">
          <span>Original</span>
        </button>
      </div>`,
      "clipboard",
    );

    const button = element.querySelector("button")!;
    button.click();

    // Wait for the clipboard promise to resolve
    await vi.advanceTimersByTimeAsync(0);

    // Button content should now be a checkmark SVG
    expect(button.innerHTML).toContain("<svg");
    expect(button.innerHTML).toContain("fill-rule");
  });

  it("restores original content after success duration", async () => {
    const { element } = await startController(
      ClipboardController,
      `<div data-controller="clipboard"
           data-clipboard-content-value="text"
           data-clipboard-success-duration-value="2000">
        <button data-clipboard-target="button"
                data-action="clipboard#copy">
          <span>Original</span>
        </button>
      </div>`,
      "clipboard",
    );

    const button = element.querySelector("button")!;
    const originalHTML = button.innerHTML;

    button.click();
    await vi.advanceTimersByTimeAsync(0);

    // Content changed to checkmark
    expect(button.innerHTML).not.toBe(originalHTML);

    // After duration, original content is restored
    vi.advanceTimersByTime(2000);
    expect(button.innerHTML).toBe(originalHTML);
  });

  it("uses custom success content when provided", async () => {
    const { element } = await startController(
      ClipboardController,
      `<div data-controller="clipboard"
           data-clipboard-content-value="text"
           data-clipboard-success-content-value="Copied!"
           data-clipboard-success-duration-value="2000">
        <button data-clipboard-target="button"
                data-action="clipboard#copy">Copy</button>
      </div>`,
      "clipboard",
    );

    const button = element.querySelector("button")!;
    button.click();
    await vi.advanceTimersByTimeAsync(0);

    expect(button.innerHTML).toBe("Copied!");
  });

  it("prevents default on the copy event", async () => {
    const { element } = await startController(
      ClipboardController,
      `<div data-controller="clipboard"
           data-clipboard-content-value="text"
           data-clipboard-success-duration-value="2000">
        <button data-clipboard-target="button"
                data-action="clipboard#copy">Copy</button>
      </div>`,
      "clipboard",
    );

    const event = new Event("click", { cancelable: true, bubbles: true });
    element.querySelector("button")!.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
  });
});
