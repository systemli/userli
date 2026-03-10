import { describe, it, expect, vi, beforeEach } from "vitest";
import NavigateController from "../../../assets/controllers/navigate_controller";
import { startController } from "../helpers/stimulus";

describe("NavigateController", () => {
  beforeEach(() => {
    vi.stubGlobal(
      "location",
      Object.assign(new URL("http://localhost/current"), {
        assign: vi.fn(),
      }),
    );
  });

  it("navigates to the selected option value", async () => {
    const { element } = await startController(
      NavigateController,
      `<select data-controller="navigate"
              data-action="change->navigate#go">
        <option value="/page-a">Page A</option>
        <option value="/page-b">Page B</option>
      </select>`,
      "navigate",
    );

    const select = element as HTMLSelectElement;
    select.value = "/page-b";
    select.dispatchEvent(new Event("change", { bubbles: true }));

    expect(window.location.assign).toHaveBeenCalledWith(
      "http://localhost/page-b",
    );
  });

  it("resolves relative URLs against the current origin", async () => {
    const { element } = await startController(
      NavigateController,
      `<select data-controller="navigate"
              data-action="change->navigate#go">
        <option value="/settings/users">Users</option>
      </select>`,
      "navigate",
    );

    const select = element as HTMLSelectElement;
    select.value = "/settings/users";
    select.dispatchEvent(new Event("change", { bubbles: true }));

    expect(window.location.assign).toHaveBeenCalledWith(
      "http://localhost/settings/users",
    );
  });

  it("ignores cross-origin URLs", async () => {
    const { element } = await startController(
      NavigateController,
      `<select data-controller="navigate"
              data-action="change->navigate#go">
        <option value="https://evil.com/steal">Evil</option>
      </select>`,
      "navigate",
    );

    const select = element as HTMLSelectElement;
    select.value = "https://evil.com/steal";
    select.dispatchEvent(new Event("change", { bubbles: true }));

    expect(window.location.assign).not.toHaveBeenCalled();
  });

  it("navigates to root for empty string (relative URL)", async () => {
    const { element } = await startController(
      NavigateController,
      `<select data-controller="navigate"
              data-action="change->navigate#go">
        <option value="">--select--</option>
      </select>`,
      "navigate",
    );

    const select = element as HTMLSelectElement;
    select.value = "";
    expect(() => {
      select.dispatchEvent(new Event("change", { bubbles: true }));
    }).not.toThrow();

    // Empty string is a valid relative URL resolving to the current origin
    expect(window.location.assign).toHaveBeenCalledWith(
      "http://localhost/",
    );
  });
});
