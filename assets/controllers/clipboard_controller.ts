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

declare type ClipboardWithSourceTarget = Clipboard & {
  hasSourceTarget: boolean;
  sourceTarget: HTMLInputElement;
  hasContentValue: boolean;
  contentValue: string;
  hasSuccessContentValue: boolean;
  successContentValue: string;
  hasButtonTarget: boolean;
  buttonTarget: HTMLElement;
  originalContent: string;
  successDurationValue: number;
  timeout: ReturnType<typeof setTimeout>;
};

export default class extends Clipboard {
  static values = {
    ...Clipboard.values,
    content: String,
  };

  copy(event: Event): void {
    event.preventDefault();

    const self = this as unknown as ClipboardWithSourceTarget;
    let text: string;

    if (self.hasSourceTarget) {
      text = self.sourceTarget.innerHTML || self.sourceTarget.value;
    } else if (self.hasContentValue) {
      text = self.contentValue;
    } else {
      return;
    }

    navigator.clipboard.writeText(text).then(() => this.copied());
  }

  get _feedbackElement(): HTMLElement {
    const self = this as unknown as ClipboardWithSourceTarget;
    return self.hasButtonTarget ? self.buttonTarget : this.element as HTMLElement;
  }

  connect(): void {
    (this as unknown as ClipboardWithSourceTarget).originalContent =
      this._feedbackElement.innerHTML;
  }

  copied(): void {
    const self = this as unknown as ClipboardWithSourceTarget;
    const el = this._feedbackElement;

    if (self.timeout) {
      clearTimeout(self.timeout);
    }

    const content = self.hasSuccessContentValue
      ? self.successContentValue
      : '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';

    el.innerHTML = content;

    self.timeout = setTimeout(() => {
      el.innerHTML = self.originalContent;
    }, self.successDurationValue);
  }
}
