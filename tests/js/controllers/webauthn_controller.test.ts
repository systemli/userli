import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import type { Application } from "@hotwired/stimulus";
import WebauthnController, {
  base64UrlToBuffer,
  bufferToBase64Url,
} from "../../../assets/controllers/webauthn_controller";
import { startController as start } from "../helpers/stimulus";

let application: Application | undefined;

async function startController(...args: Parameters<typeof start>) {
  const result = await start(...args);
  application = result.application;
  return result;
}

class FakePublicKeyCredential {
  type = "public-key";
  constructor(
    public id: string,
    public rawId: ArrayBuffer,
    public response: object,
  ) {}
}

class FakeAssertionResponse {
  clientDataJSON = new Uint8Array([1]).buffer;
  authenticatorData = new Uint8Array([2]).buffer;
  signature = new Uint8Array([3]).buffer;
  userHandle = null;
}

const options = JSON.stringify({
  challenge: "AQID",
  rpId: "localhost",
  allowCredentials: [{ type: "public-key", id: "BAUG" }],
});

const html = `
  <form data-controller="webauthn" data-webauthn-options-value='${options}' data-webauthn-ceremony-value="get">
    <input type="hidden" name="_auth_code" data-webauthn-target="response">
    <p hidden data-webauthn-target="error">Error</p>
    <button type="button" data-action="webauthn#start">Use security key</button>
  </form>`;

function deferred<T>() {
  let resolve!: (value: T) => void;
  let reject!: (reason: unknown) => void;
  const promise = new Promise<T>((res, rej) => {
    resolve = res;
    reject = rej;
  });
  return { promise, resolve, reject };
}

describe("WebauthnController", () => {
  let get: ReturnType<typeof vi.fn>;
  let requestSubmit: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    vi.stubGlobal("PublicKeyCredential", FakePublicKeyCredential);
    vi.stubGlobal("AuthenticatorAssertionResponse", FakeAssertionResponse);
    vi.stubGlobal("AuthenticatorAttestationResponse", class {});
    get = vi.fn();
    Object.defineProperty(navigator, "credentials", { value: { get }, configurable: true });
    requestSubmit = vi.fn();
    HTMLFormElement.prototype.requestSubmit = requestSubmit;
  });

  afterEach(() => {
    application?.stop();
    application = undefined;
  });

  it("does not start the ceremony without a click", async () => {
    await startController(WebauthnController, html, "webauthn");

    expect(get).not.toHaveBeenCalled();
  });

  it("submits the serialized assertion after a click", async () => {
    get.mockResolvedValue(
      new FakePublicKeyCredential("BAUG", new Uint8Array([4, 5, 6]).buffer, new FakeAssertionResponse()),
    );
    const { element } = await startController(WebauthnController, html, "webauthn");

    element.querySelector("button")!.click();
    await vi.waitFor(() => expect(requestSubmit).toHaveBeenCalledTimes(1));

    const publicKey = get.mock.calls[0][0].publicKey;
    expect(bufferToBase64Url(publicKey.challenge)).toBe("AQID");
    expect(bufferToBase64Url(publicKey.allowCredentials[0].id)).toBe("BAUG");

    const response = JSON.parse(element.querySelector<HTMLInputElement>("input")!.value);
    expect(response).toEqual({
      id: "BAUG",
      rawId: "BAUG",
      type: "public-key",
      response: { clientDataJSON: "AQ", authenticatorData: "Ag", signature: "Aw" },
    });
  });

  it("cancels a pending ceremony when clicked again", async () => {
    const first = deferred<unknown>();
    get.mockImplementationOnce(({ signal }: { signal: AbortSignal }) => {
      signal.addEventListener("abort", () => first.reject(new DOMException("Aborted", "AbortError")));
      return first.promise;
    });
    get.mockImplementationOnce(() => new Promise(() => {}));
    const { element } = await startController(WebauthnController, html, "webauthn");
    const button = element.querySelector("button")!;

    button.click();
    button.click();
    await vi.waitFor(() => expect(get).toHaveBeenCalledTimes(2));

    expect(get.mock.calls[0][0].signal.aborted).toBe(true);
    expect(element.querySelector<HTMLElement>("[data-webauthn-target=error]")!.hidden).toBe(true);
  });

  it("shows an error when the ceremony fails", async () => {
    get.mockRejectedValue(new DOMException("Not allowed", "NotAllowedError"));
    const { element } = await startController(WebauthnController, html, "webauthn");

    element.querySelector("button")!.click();

    await vi.waitFor(() =>
      expect(element.querySelector<HTMLElement>("[data-webauthn-target=error]")!.hidden).toBe(false),
    );
    expect(requestSubmit).not.toHaveBeenCalled();
  });

  it("converts between base64url and buffers", () => {
    expect(bufferToBase64Url(base64UrlToBuffer("-_8"))).toBe("-_8");
  });
});
