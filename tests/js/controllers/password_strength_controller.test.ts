import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import PasswordStrengthController from "../../../assets/controllers/password_strength_controller";
import { startController } from "../helpers/stimulus";
import type { Application } from "@hotwired/stimulus";

const METER_HTML = `
  <div data-controller="password-strength"
       data-password-strength-strong-label-value="Great password!"
       data-password-strength-min-length-value="8"
       data-password-strength-min-length-label-value="At least 8 characters">
    <input type="password"
           data-password-strength-target="input"
           data-action="input->password-strength#evaluate" />
    <div>
      <div data-password-strength-target="segment"></div>
      <div data-password-strength-target="segment"></div>
      <div data-password-strength-target="segment"></div>
      <div data-password-strength-target="segment"></div>
    </div>
    <p data-password-strength-target="feedback" class="hidden"></p>
  </div>`;

/**
 * Injects a mock zxcvbn function into the controller's private `_zxcvbn`
 * property, bypassing the async dynamic-import loading step.
 */
function injectMockZxcvbn(
  application: Application,
  mock: ReturnType<typeof vi.fn>,
): void {
  const controller = application.controllers[0] as unknown as {
    _zxcvbn: unknown;
  };
  controller._zxcvbn = mock;
}

describe("PasswordStrengthController", () => {
  let zxcvbnMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    vi.useFakeTimers();
    zxcvbnMock = vi.fn();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("resets all segments to inactive on connect", async () => {
    const { element } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    const segments = element.querySelectorAll(
      "[data-password-strength-target='segment']",
    );
    segments.forEach((segment) => {
      expect(segment.classList.contains("bg-gray-200")).toBe(true);
      expect(segment.classList.contains("dark:bg-gray-600")).toBe(true);
    });
  });

  it("hides feedback on connect", async () => {
    const { element } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    const feedback = element.querySelector(
      "[data-password-strength-target='feedback']",
    )!;
    expect(feedback.classList.contains("hidden")).toBe(true);
    expect(feedback.textContent).toBe("");
  });

  it("resets meter when password is empty", async () => {
    const { element, application } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    injectMockZxcvbn(application, zxcvbnMock);

    const input = element.querySelector("input")!;

    // Type something, then clear it
    input.value = "";
    input.dispatchEvent(new Event("input"));
    vi.advanceTimersByTime(150);

    const segments = element.querySelectorAll(
      "[data-password-strength-target='segment']",
    );
    segments.forEach((segment) => {
      expect(segment.classList.contains("bg-gray-200")).toBe(true);
    });
  });

  it("shows min-length label when password is too short", async () => {
    const { element, application } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    injectMockZxcvbn(application, zxcvbnMock);

    const input = element.querySelector("input")!;
    const feedback = element.querySelector(
      "[data-password-strength-target='feedback']",
    )!;

    // Type short password (less than 8 chars)
    input.value = "abc";
    input.dispatchEvent(new Event("input"));
    vi.advanceTimersByTime(150);

    expect(feedback.textContent).toBe("At least 8 characters");
    expect(feedback.classList.contains("hidden")).toBe(false);
  });

  it("evaluates password strength with debounce", async () => {
    const { element, application } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    injectMockZxcvbn(application, zxcvbnMock);

    zxcvbnMock.mockReturnValue({
      score: 2,
      feedback: { warning: "This is a common password", suggestions: [] },
    });

    const input = element.querySelector("input")!;
    input.value = "password1234";
    input.dispatchEvent(new Event("input"));

    // zxcvbn not called yet (debounce)
    expect(zxcvbnMock).not.toHaveBeenCalled();

    // Advance past debounce timer
    vi.advanceTimersByTime(150);

    expect(zxcvbnMock).toHaveBeenCalledWith("password1234");
  });

  it("colors segments based on score", async () => {
    const { element, application } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    injectMockZxcvbn(application, zxcvbnMock);

    const input = element.querySelector("input")!;
    const segments = element.querySelectorAll(
      "[data-password-strength-target='segment']",
    );

    // Score 2: yellow, 3 active segments
    zxcvbnMock.mockReturnValue({
      score: 2,
      feedback: { warning: "", suggestions: [] },
    });

    input.value = "medium-pass";
    input.dispatchEvent(new Event("input"));
    vi.advanceTimersByTime(150);

    // First 3 segments should have yellow color classes
    expect(segments[0].classList.contains("bg-yellow-500")).toBe(true);
    expect(segments[1].classList.contains("bg-yellow-500")).toBe(true);
    expect(segments[2].classList.contains("bg-yellow-500")).toBe(true);
    // 4th segment should be inactive
    expect(segments[3].classList.contains("bg-gray-200")).toBe(true);
  });

  it("shows strong label for score >= 3", async () => {
    const { element, application } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    injectMockZxcvbn(application, zxcvbnMock);

    const input = element.querySelector("input")!;
    const feedback = element.querySelector(
      "[data-password-strength-target='feedback']",
    )!;

    zxcvbnMock.mockReturnValue({
      score: 4,
      feedback: { warning: "", suggestions: [] },
    });

    input.value = "very-strong-password!123";
    input.dispatchEvent(new Event("input"));
    vi.advanceTimersByTime(150);

    expect(feedback.textContent).toBe("Great password!");
    expect(feedback.classList.contains("text-green-600")).toBe(true);
    expect(feedback.classList.contains("hidden")).toBe(false);
  });

  it("shows zxcvbn warning as feedback", async () => {
    const { element, application } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    injectMockZxcvbn(application, zxcvbnMock);

    const input = element.querySelector("input")!;
    const feedback = element.querySelector(
      "[data-password-strength-target='feedback']",
    )!;

    zxcvbnMock.mockReturnValue({
      score: 1,
      feedback: {
        warning: "This is similar to a commonly used password",
        suggestions: [],
      },
    });

    input.value = "password12";
    input.dispatchEvent(new Event("input"));
    vi.advanceTimersByTime(150);

    expect(feedback.textContent).toBe(
      "This is similar to a commonly used password",
    );
  });

  it("shows zxcvbn suggestions when no warning", async () => {
    const { element, application } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    injectMockZxcvbn(application, zxcvbnMock);

    const input = element.querySelector("input")!;
    const feedback = element.querySelector(
      "[data-password-strength-target='feedback']",
    )!;

    zxcvbnMock.mockReturnValue({
      score: 1,
      feedback: {
        warning: "",
        suggestions: ["Add a number", "Add a symbol"],
      },
    });

    input.value = "abcdefgh";
    input.dispatchEvent(new Event("input"));
    vi.advanceTimersByTime(150);

    expect(feedback.textContent).toBe("Add a number Add a symbol");
  });

  it("clears debounce timer on disconnect", async () => {
    const { element, application } = await startController(
      PasswordStrengthController,
      METER_HTML,
      "password-strength",
    );

    injectMockZxcvbn(application, zxcvbnMock);

    const input = element.querySelector("input")!;

    // Start an evaluation
    input.value = "test-password";
    input.dispatchEvent(new Event("input"));

    // Disconnect before debounce fires
    element.remove();
    await vi.advanceTimersByTimeAsync(0);

    // Advance past debounce — should not throw
    expect(() => vi.advanceTimersByTime(200)).not.toThrow();
  });
});
