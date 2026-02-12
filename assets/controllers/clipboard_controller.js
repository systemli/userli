import Clipboard from "@stimulus-components/clipboard";

/**
 * Extended clipboard controller based on @stimulus-components/clipboard.
 *
 * Additions over the base controller:
 *   - Supports a `content` value as an alternative to the `source` target,
 *     so the text to copy can be specified as a data attribute directly.
 *   - Provides a default success content (checkmark SVG) when no custom
 *     `successContent` value is set.
 *
 * Usage with content value (most common in this app):
 *   <div data-controller="clipboard"
 *        data-clipboard-content-value="text to copy">
 *     <button data-action="clipboard#copy" data-clipboard-target="button">
 *       <svg>...</svg>
 *     </button>
 *   </div>
 *
 * Usage with source target (library default):
 *   <div data-controller="clipboard">
 *     <input data-clipboard-target="source" value="text to copy" />
 *     <button data-action="clipboard#copy" data-clipboard-target="button">
 *       Copy
 *     </button>
 *   </div>
 */
export default class extends Clipboard {
  static values = {
    ...Clipboard.values,
    content: String,
  };

  copy(event) {
    event.preventDefault();

    let text;

    if (this.hasSourceTarget) {
      text = this.sourceTarget.innerHTML || this.sourceTarget.value;
    } else if (this.hasContentValue) {
      text = this.contentValue;
    } else {
      return;
    }

    navigator.clipboard.writeText(text).then(() => this.copied());
  }

  get _feedbackElement() {
    return this.hasButtonTarget ? this.buttonTarget : this.element;
  }

  connect() {
    this.originalContent = this._feedbackElement.innerHTML;
  }

  copied() {
    const el = this._feedbackElement;

    if (this.timeout) {
      clearTimeout(this.timeout);
    }

    const content = this.hasSuccessContentValue
      ? this.successContentValue
      : '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';

    el.innerHTML = content;

    this.timeout = setTimeout(() => {
      el.innerHTML = this.originalContent;
    }, this.successDurationValue);
  }
}
