import { Application } from "@hotwired/stimulus";
import { describe, it, expect, beforeEach, afterEach } from "vitest";
import InvitationSettingsController from "../../../assets/controllers/invitation_settings_controller";
import { startController } from "../helpers/stimulus";

/**
 * Helper to build the DOM for the invitation-settings controller.
 *
 * @param invitationsChecked - initial state of the "Enable Invitations" toggle
 * @param limitValue - initial value of the invitationLimit hidden input
 */
function html(invitationsChecked: boolean, limitValue: number): string {
  return `
    <div data-controller="invitation-settings">
      <input type="checkbox"
             data-invitation-settings-target="invitationsToggle"
             data-action="change->invitation-settings#invitationsChanged"
             ${invitationsChecked ? "checked" : ""} />

      <div data-invitation-settings-target="autoSection">
        <input type="checkbox"
               data-invitation-settings-target="autoToggle"
               data-action="change->invitation-settings#autoChanged" />
      </div>

      <div class="hidden" data-invitation-settings-target="limitSection">
        <input type="number"
               value="${limitValue}"
               data-invitation-settings-target="limitInput"
               data-action="change->invitation-settings#limitChanged" />
      </div>
    </div>
  `;
}

function getTargets(element: HTMLElement) {
  return {
    invitationsToggle: element.querySelector<HTMLInputElement>(
      "[data-invitation-settings-target='invitationsToggle']",
    )!,
    autoToggle: element.querySelector<HTMLInputElement>(
      "[data-invitation-settings-target='autoToggle']",
    )!,
    autoSection: element.querySelector<HTMLElement>(
      "[data-invitation-settings-target='autoSection']",
    )!,
    limitInput: element.querySelector<HTMLInputElement>(
      "[data-invitation-settings-target='limitInput']",
    )!,
    limitSection: element.querySelector<HTMLElement>(
      "[data-invitation-settings-target='limitSection']",
    )!,
  };
}

describe("InvitationSettingsController", () => {
  let app: Application | undefined;

  afterEach(() => {
    app?.stop();
  });

  describe("initial state", () => {
    it("enables auto-toggle and shows limit when invitations on and limit > 0", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(true, 3),
        "invitation-settings",
      );
      app = application;

      const { autoToggle, autoSection, limitSection } = getTargets(element);

      expect(autoToggle.checked).toBe(true);
      expect(autoToggle.disabled).toBe(false);
      expect(autoSection.classList.contains("opacity-50")).toBe(false);
      expect(limitSection.classList.contains("hidden")).toBe(false);
    });

    it("enables auto-toggle unchecked and hides limit when invitations on and limit = 0", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(true, 0),
        "invitation-settings",
      );
      app = application;

      const { autoToggle, autoSection, limitSection } = getTargets(element);

      expect(autoToggle.checked).toBe(false);
      expect(autoToggle.disabled).toBe(false);
      expect(autoSection.classList.contains("opacity-50")).toBe(false);
      expect(limitSection.classList.contains("hidden")).toBe(true);
    });

    it("disables auto-toggle when invitations are off", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(false, 3),
        "invitation-settings",
      );
      app = application;

      const { autoToggle, autoSection, limitSection } = getTargets(element);

      expect(autoToggle.disabled).toBe(true);
      expect(autoSection.classList.contains("opacity-50")).toBe(true);
      expect(autoSection.classList.contains("pointer-events-none")).toBe(true);
      expect(limitSection.classList.contains("hidden")).toBe(true);
    });
  });

  describe("invitations toggle", () => {
    it("disables auto section and sets limit to 0 when invitations turned off", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(true, 3),
        "invitation-settings",
      );
      app = application;

      const { invitationsToggle, autoToggle, autoSection, limitInput, limitSection } =
        getTargets(element);

      // Turn off invitations
      invitationsToggle.checked = false;
      invitationsToggle.dispatchEvent(new Event("change", { bubbles: true }));

      expect(autoToggle.disabled).toBe(true);
      expect(autoToggle.checked).toBe(false);
      expect(autoSection.classList.contains("opacity-50")).toBe(true);
      expect(limitSection.classList.contains("hidden")).toBe(true);
      expect(limitInput.value).toBe("0");
    });

    it("restores previous limit when invitations toggled off then on and auto re-enabled", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(true, 5),
        "invitation-settings",
      );
      app = application;

      const { invitationsToggle, autoToggle, limitInput } = getTargets(element);

      // Turn off invitations — should reset limit to 0
      invitationsToggle.checked = false;
      invitationsToggle.dispatchEvent(new Event("change", { bubbles: true }));
      expect(limitInput.value).toBe("0");

      // Turn invitations back on
      invitationsToggle.checked = true;
      invitationsToggle.dispatchEvent(new Event("change", { bubbles: true }));

      // Re-enable auto-invitations — should restore 5
      autoToggle.checked = true;
      autoToggle.dispatchEvent(new Event("change", { bubbles: true }));
      expect(limitInput.value).toBe("5");
    });

    it("re-enables auto section when invitations turned back on", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(false, 0),
        "invitation-settings",
      );
      app = application;

      const { invitationsToggle, autoToggle, autoSection } =
        getTargets(element);

      // Turn on invitations
      invitationsToggle.checked = true;
      invitationsToggle.dispatchEvent(new Event("change", { bubbles: true }));

      expect(autoToggle.disabled).toBe(false);
      expect(autoSection.classList.contains("opacity-50")).toBe(false);
    });
  });

  describe("auto-invitations toggle", () => {
    it("shows limit input and sets default when auto turned on with limit 0", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(true, 0),
        "invitation-settings",
      );
      app = application;

      const { autoToggle, limitInput, limitSection } = getTargets(element);

      // Turn on auto-invitations
      autoToggle.checked = true;
      autoToggle.dispatchEvent(new Event("change", { bubbles: true }));

      expect(limitSection.classList.contains("hidden")).toBe(false);
      // Should use default limit (3)
      expect(limitInput.value).toBe("3");
    });

    it("hides limit input and sets value to 0 when auto turned off", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(true, 5),
        "invitation-settings",
      );
      app = application;

      const { autoToggle, limitInput, limitSection } = getTargets(element);

      // Turn off auto-invitations
      autoToggle.checked = false;
      autoToggle.dispatchEvent(new Event("change", { bubbles: true }));

      expect(limitSection.classList.contains("hidden")).toBe(true);
      expect(limitInput.value).toBe("0");
      // min attribute must be removed so 0 passes HTML validation on submit
      expect(limitInput.hasAttribute("min")).toBe(false);
    });

    it("sets min=1 on limit input when auto-invitations are on", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(true, 3),
        "invitation-settings",
      );
      app = application;

      const { limitInput } = getTargets(element);

      expect(limitInput.min).toBe("1");
    });

    it("restores previous limit when auto is toggled off then on again", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(true, 7),
        "invitation-settings",
      );
      app = application;

      const { autoToggle, limitInput } = getTargets(element);

      // Turn off
      autoToggle.checked = false;
      autoToggle.dispatchEvent(new Event("change", { bubbles: true }));
      expect(limitInput.value).toBe("0");

      // Turn back on — should restore 7
      autoToggle.checked = true;
      autoToggle.dispatchEvent(new Event("change", { bubbles: true }));
      expect(limitInput.value).toBe("7");
    });
  });

  describe("limit input changes", () => {
    it("remembers manually changed limit value for restore", async () => {
      const { application, element } = await startController(
        InvitationSettingsController,
        html(true, 3),
        "invitation-settings",
      );
      app = application;

      const { autoToggle, limitInput } = getTargets(element);

      // User changes limit to 10
      limitInput.value = "10";
      limitInput.dispatchEvent(new Event("change", { bubbles: true }));

      // Toggle off and on — should restore 10
      autoToggle.checked = false;
      autoToggle.dispatchEvent(new Event("change", { bubbles: true }));

      autoToggle.checked = true;
      autoToggle.dispatchEvent(new Event("change", { bubbles: true }));

      expect(limitInput.value).toBe("10");
    });
  });
});
