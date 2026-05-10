import { Application } from "@hotwired/stimulus";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import OpenpgpReplaceController from "../../../assets/controllers/openpgp_replace_controller";
import { startController } from "../helpers/stimulus";

const HTML = `
  <form id="openpgp-upload-form">
    <input name="upload_openpgp_key[email]" type="hidden" value="alice@example.org">
    <textarea name="upload_openpgp_key[keyText]">dummy</textarea>
    <button type="submit" id="upload-submit">Publish OpenPGP key</button>
  </form>

  <div data-controller="openpgp-replace">
    <button id="arm-btn"
            data-action="openpgp-replace#arm"
            data-openpgp-replace-email-param="alice@example.org">Replace</button>
    <button id="disarm-btn" data-action="openpgp-replace#disarm">Disarm</button>

    <div data-openpgp-replace-target="overlay" class="hidden opacity-0">
      <div data-openpgp-replace-target="dialog" class="opacity-0 scale-95">
        <input data-openpgp-replace-target="autofocus"
               name="upload_openpgp_key[password]"
               form="openpgp-upload-form"
               type="password">
        <button id="modal-close" data-action="openpgp-replace#close">Back</button>
        <button type="submit" form="openpgp-upload-form" id="modal-submit">Publish</button>
      </div>
    </div>
  </div>`;

describe("OpenpgpReplaceController", () => {
  let app: Application | null = null;

  async function start() {
    const result = await startController(
      OpenpgpReplaceController,
      HTML,
      "openpgp-replace",
    );
    app = result.application;
    return result;
  }

  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    app?.stop();
    app = null;
    vi.useRealTimers();
  });

  it("does not intercept upload-form submits when not armed", async () => {
    await start();
    const form = document.getElementById(
      "openpgp-upload-form",
    ) as HTMLFormElement;
    const submitEvent = new Event("submit", { cancelable: true, bubbles: true });

    form.dispatchEvent(submitEvent);

    expect(submitEvent.defaultPrevented).toBe(false);
  });

  it("intercepts the first submit after arm and opens the password modal", async () => {
    await start();
    const form = document.getElementById(
      "openpgp-upload-form",
    ) as HTMLFormElement;
    const armBtn = document.getElementById("arm-btn") as HTMLButtonElement;
    const overlay = document.querySelector<HTMLElement>(
      "[data-openpgp-replace-target='overlay']",
    )!;

    armBtn.click();
    const submitEvent = new Event("submit", { cancelable: true, bubbles: true });
    form.dispatchEvent(submitEvent);

    expect(submitEvent.defaultPrevented).toBe(true);
    expect(overlay.classList.contains("hidden")).toBe(false);
  });

  it("lets the submit through once the password modal is visible", async () => {
    await start();
    const form = document.getElementById(
      "openpgp-upload-form",
    ) as HTMLFormElement;
    const armBtn = document.getElementById("arm-btn") as HTMLButtonElement;

    armBtn.click();
    form.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
    const secondSubmit = new Event("submit", { cancelable: true, bubbles: true });
    form.dispatchEvent(secondSubmit);

    expect(secondSubmit.defaultPrevented).toBe(false);
  });

  it("clicking the Upload button (disarm) removes the replace flag", async () => {
    await start();
    const form = document.getElementById(
      "openpgp-upload-form",
    ) as HTMLFormElement;
    const armBtn = document.getElementById("arm-btn") as HTMLButtonElement;
    const disarmBtn = document.getElementById("disarm-btn") as HTMLButtonElement;

    armBtn.click();
    disarmBtn.click();
    const submitEvent = new Event("submit", { cancelable: true, bubbles: true });
    form.dispatchEvent(submitEvent);

    expect(submitEvent.defaultPrevented).toBe(false);
  });

  it("closing the modal clears the armed state", async () => {
    await start();
    const form = document.getElementById(
      "openpgp-upload-form",
    ) as HTMLFormElement;
    const armBtn = document.getElementById("arm-btn") as HTMLButtonElement;
    const closeBtn = document.getElementById("modal-close") as HTMLButtonElement;

    armBtn.click();
    form.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
    closeBtn.click();
    vi.runAllTimers();

    const subsequentSubmit = new Event("submit", {
      cancelable: true,
      bubbles: true,
    });
    form.dispatchEvent(subsequentSubmit);

    expect(subsequentSubmit.defaultPrevented).toBe(false);
  });
});
