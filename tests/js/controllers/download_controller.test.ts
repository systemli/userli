import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import DownloadController from "../../../assets/controllers/download_controller";
import { startController } from "../helpers/stimulus";

describe("DownloadController", () => {
  let createObjectURL: ReturnType<typeof vi.fn>;
  let revokeObjectURL: ReturnType<typeof vi.fn>;
  let clickedLinks: HTMLAnchorElement[];
  const originalCreateObjectURL = URL.createObjectURL;
  const originalRevokeObjectURL = URL.revokeObjectURL;

  beforeEach(() => {
    vi.useFakeTimers();

    createObjectURL = vi.fn().mockReturnValue("blob:http://localhost/123");
    revokeObjectURL = vi.fn();
    URL.createObjectURL = createObjectURL;
    URL.revokeObjectURL = revokeObjectURL;

    clickedLinks = [];
    vi.spyOn(HTMLAnchorElement.prototype, "click").mockImplementation(
      function (this: HTMLAnchorElement) {
        clickedLinks.push(this);
      },
    );
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
    URL.createObjectURL = originalCreateObjectURL;
    URL.revokeObjectURL = originalRevokeObjectURL;
  });

  it("downloads the content as a text file", async () => {
    const { element } = await startController(
      DownloadController,
      `<button data-controller="download"
               data-download-content-value="secret-token-123"
               data-download-filename-value="recovery-token.txt"
               data-action="download#save">Download</button>`,
      "download",
    );

    element.click();

    expect(createObjectURL).toHaveBeenCalledOnce();
    const blob = createObjectURL.mock.calls[0][0] as Blob;
    expect(blob.type).toBe("text/plain;charset=utf-8");
    expect(await blob.text()).toBe("secret-token-123");

    expect(clickedLinks).toHaveLength(1);
    expect(clickedLinks[0].download).toBe("recovery-token.txt");
    expect(clickedLinks[0].href).toBe("blob:http://localhost/123");
  });

  it("removes the temporary link and revokes the object URL", async () => {
    const { element } = await startController(
      DownloadController,
      `<button data-controller="download"
               data-download-content-value="content"
               data-action="download#save">Download</button>`,
      "download",
    );

    element.click();

    expect(document.querySelectorAll("a")).toHaveLength(0);
    expect(revokeObjectURL).not.toHaveBeenCalled();

    vi.runAllTimers();

    expect(revokeObjectURL).toHaveBeenCalledWith("blob:http://localhost/123");
  });

  it("uses a default filename", async () => {
    const { element } = await startController(
      DownloadController,
      `<button data-controller="download"
               data-download-content-value="content"
               data-action="download#save">Download</button>`,
      "download",
    );

    element.click();

    expect(clickedLinks[0].download).toBe("download.txt");
  });
});
