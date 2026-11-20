import { Controller } from "@hotwired/stimulus";

/**
 * Download controller — saves text content as a file on the user's device.
 *
 * The file is created in the browser, so the content never leaves the page.
 *
 * Usage:
 *   <button data-controller="download"
 *           data-download-content-value="text to save"
 *           data-download-filename-value="file.txt"
 *           data-action="download#save">
 *     Download
 *   </button>
 */
export default class extends Controller {
  static values = {
    content: String,
    filename: { type: String, default: "download.txt" },
  };

  declare readonly contentValue: string;
  declare readonly filenameValue: string;

  save(event: Event): void {
    event.preventDefault();

    const blob = new Blob([this.contentValue], {
      type: "text/plain;charset=utf-8",
    });
    const url = URL.createObjectURL(blob);

    const link = document.createElement("a");
    link.href = url;
    link.download = this.filenameValue;
    link.hidden = true;
    document.body.appendChild(link);
    link.click();
    link.remove();

    // Revoke asynchronously, some browsers start the download after click() returns
    setTimeout(() => URL.revokeObjectURL(url), 0);
  }
}
