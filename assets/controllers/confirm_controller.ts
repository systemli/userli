import { Controller } from "@hotwired/stimulus";

/**
 * Confirm controller — shows a confirmation dialog before submitting a form.
 *
 * Usage:
 *   <form data-controller="confirm"
 *         data-confirm-message-value="Are you sure?"
 *         data-action="submit->confirm#prompt"
 *         method="POST" action="/delete/123">
 *     <input type="hidden" name="_token" value="...">
 *     <button type="submit">Delete</button>
 *   </form>
 */
export default class extends Controller {
  declare messageValue: string;

  static values = {
    message: { type: String, default: "Are you sure?" },
  };

  prompt(event: Event): void {
    if (!window.confirm(this.messageValue)) {
      event.preventDefault();
    }
  }
}
