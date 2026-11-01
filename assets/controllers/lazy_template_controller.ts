import { Controller } from "@hotwired/stimulus";

/**
 * Lazy template controller — inserts the content of a <template> on demand, e.g. when a
 * <details> element is opened. Password managers then cannot fill in fields before.
 *
 * Usage:
 *   <details data-controller="lazy-template" data-action="toggle->lazy-template#reveal">
 *     <summary>Show</summary>
 *     <template data-lazy-template-target="template">…</template>
 *     <div data-lazy-template-target="container"></div>
 *   </details>
 */
export default class extends Controller {
  static targets = ["template", "container"];

  declare readonly templateTarget: HTMLTemplateElement;
  declare readonly containerTarget: HTMLElement;

  reveal(): void {
    if (this.containerTarget.childElementCount > 0) {
      return;
    }

    this.containerTarget.append(this.templateTarget.content.cloneNode(true));
    this.containerTarget.querySelector<HTMLElement>("input:not([type=hidden])")?.focus();
  }
}
