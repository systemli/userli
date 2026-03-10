import { describe, it, expect, vi } from "vitest";
import ConfirmController from "../../../assets/controllers/confirm_controller";
import { startController } from "../helpers/stimulus";

describe("ConfirmController", () => {
  it("prevents default when user cancels confirmation", async () => {
    vi.spyOn(window, "confirm").mockReturnValue(false);

    const { element } = await startController(
      ConfirmController,
      `<form data-controller="confirm"
             data-confirm-message-value="Delete this item?"
             data-action="submit->confirm#prompt">
         <button type="submit">Delete</button>
       </form>`,
      "confirm"
    );

    const event = new Event("submit", { cancelable: true, bubbles: true });
    element.dispatchEvent(event);

    expect(window.confirm).toHaveBeenCalledWith("Delete this item?");
    expect(event.defaultPrevented).toBe(true);
  });

  it("allows submission when user confirms", async () => {
    vi.spyOn(window, "confirm").mockReturnValue(true);

    const { element } = await startController(
      ConfirmController,
      `<form data-controller="confirm"
             data-action="submit->confirm#prompt">
         <button type="submit">Delete</button>
       </form>`,
      "confirm"
    );

    const event = new Event("submit", { cancelable: true, bubbles: true });
    element.dispatchEvent(event);

    expect(window.confirm).toHaveBeenCalledWith("Are you sure?");
    expect(event.defaultPrevented).toBe(false);
  });

  it("uses default message when no value is set", async () => {
    vi.spyOn(window, "confirm").mockReturnValue(false);

    const { element } = await startController(
      ConfirmController,
      `<form data-controller="confirm"
             data-action="submit->confirm#prompt">
         <button type="submit">Delete</button>
       </form>`,
      "confirm"
    );

    const event = new Event("submit", { cancelable: true, bubbles: true });
    element.dispatchEvent(event);

    expect(window.confirm).toHaveBeenCalledWith("Are you sure?");
  });

  it("uses custom message from value attribute", async () => {
    vi.spyOn(window, "confirm").mockReturnValue(true);

    const { element } = await startController(
      ConfirmController,
      `<form data-controller="confirm"
             data-confirm-message-value="Really remove this alias?"
             data-action="submit->confirm#prompt">
         <button type="submit">Remove</button>
       </form>`,
      "confirm"
    );

    const event = new Event("submit", { cancelable: true, bubbles: true });
    element.dispatchEvent(event);

    expect(window.confirm).toHaveBeenCalledWith("Really remove this alias?");
  });
});
