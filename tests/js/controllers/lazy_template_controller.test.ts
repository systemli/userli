import { describe, it, expect } from "vitest";
import LazyTemplateController from "../../../assets/controllers/lazy_template_controller";
import { startController } from "../helpers/stimulus";

const html = `
  <details data-controller="lazy-template" data-action="toggle->lazy-template#reveal">
    <summary>Use a backup code</summary>
    <template data-lazy-template-target="template">
      <form><input type="text" name="_auth_code"><input type="hidden" name="_csrf_token" value="x"></form>
    </template>
    <div data-lazy-template-target="container"></div>
  </details>`;

describe("LazyTemplateController", () => {
  it("does not render the template content initially", async () => {
    const { element } = await startController(LazyTemplateController, html, "lazy-template");

    expect(element.querySelector('input[name="_auth_code"]')).toBeNull();
  });

  it("renders the template content on toggle and focuses the input", async () => {
    const { element } = await startController(LazyTemplateController, html, "lazy-template");

    element.dispatchEvent(new Event("toggle"));

    const input = element.querySelector<HTMLInputElement>('input[name="_auth_code"]');
    expect(input).not.toBeNull();
    expect(document.activeElement).toBe(input);
  });

  it("renders the template content only once", async () => {
    const { element } = await startController(LazyTemplateController, html, "lazy-template");

    element.dispatchEvent(new Event("toggle"));
    element.dispatchEvent(new Event("toggle"));

    expect(element.querySelectorAll('input[name="_auth_code"]')).toHaveLength(1);
  });
});
