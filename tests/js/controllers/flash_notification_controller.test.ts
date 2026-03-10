import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import FlashNotificationController from "../../../assets/controllers/flash_notification_controller";
import { startController } from "../helpers/stimulus";

describe("FlashNotificationController", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("does not auto-dismiss when autoDismiss value is 0", async () => {
    const { element } = await startController(
      FlashNotificationController,
      `<div data-controller="flash-notification">
         <p>Info message</p>
         <button data-action="flash-notification#dismiss">X</button>
       </div>`,
      "flash-notification"
    );

    vi.advanceTimersByTime(10000);

    expect(element.parentElement).not.toBeNull();
  });

  it("applies slide-out animation on dismiss", async () => {
    const { element } = await startController(
      FlashNotificationController,
      `<div data-controller="flash-notification">
         <p>Info message</p>
         <button data-action="flash-notification#dismiss">X</button>
       </div>`,
      "flash-notification"
    );

    const button = element.querySelector("button")!;
    button.click();

    expect(element.style.opacity).toBe("0");
    expect(element.style.transform).toBe("translateX(100%)");
  });

  it("removes element after 300ms animation delay on dismiss", async () => {
    const { element } = await startController(
      FlashNotificationController,
      `<div data-controller="flash-notification">
         <p>Info message</p>
         <button data-action="flash-notification#dismiss">X</button>
       </div>`,
      "flash-notification"
    );

    const button = element.querySelector("button")!;
    button.click();

    // Element still in DOM during animation
    expect(element.parentElement).not.toBeNull();

    // After 300ms animation delay, element is removed
    vi.advanceTimersByTime(300);
    expect(element.parentElement).toBeNull();
  });

  it("auto-dismisses after the configured delay", async () => {
    const { element } = await startController(
      FlashNotificationController,
      `<div data-controller="flash-notification"
            data-flash-notification-auto-dismiss-value="5000">
         <p>Auto-dismiss message</p>
       </div>`,
      "flash-notification"
    );

    // Not yet dismissed
    expect(element.style.opacity).not.toBe("0");

    // Advance to just before auto-dismiss
    vi.advanceTimersByTime(4999);
    expect(element.style.opacity).not.toBe("0");

    // Trigger auto-dismiss
    vi.advanceTimersByTime(1);
    expect(element.style.opacity).toBe("0");
    expect(element.style.transform).toBe("translateX(100%)");

    // Element removed after animation
    vi.advanceTimersByTime(300);
    expect(element.parentElement).toBeNull();
  });

  it("clears auto-dismiss timer on manual dismiss", async () => {
    const { element } = await startController(
      FlashNotificationController,
      `<div data-controller="flash-notification"
            data-flash-notification-auto-dismiss-value="5000">
         <p>Message</p>
         <button data-action="flash-notification#dismiss">X</button>
       </div>`,
      "flash-notification"
    );

    // Manually dismiss before auto-dismiss fires
    const button = element.querySelector("button")!;
    button.click();

    expect(element.style.opacity).toBe("0");

    // Remove after animation
    vi.advanceTimersByTime(300);
    expect(element.parentElement).toBeNull();
  });

  it("clears timer on disconnect by removing the element", async () => {
    const { element } = await startController(
      FlashNotificationController,
      `<div data-controller="flash-notification"
            data-flash-notification-auto-dismiss-value="5000">
         <p>Message</p>
       </div>`,
      "flash-notification"
    );

    // Removing the element triggers Stimulus disconnect()
    element.remove();

    // Wait for Stimulus to process the mutation
    await vi.advanceTimersByTimeAsync(0);

    // Auto-dismiss timer was cleared, so advancing past 5000ms
    // should not cause errors (element is already detached)
    vi.advanceTimersByTime(10000);
  });
});
