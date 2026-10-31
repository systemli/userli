import { Controller } from "@hotwired/stimulus";

type Json = Record<string, any>;

/**
 * WebAuthn controller — runs a WebAuthn ceremony with options from the server
 * and submits the browser response in a hidden input.
 *
 * Usage:
 *   <form data-controller="webauthn"
 *         data-webauthn-options-value="{{ options }}"
 *         data-webauthn-ceremony-value="get">
 *     <input type="hidden" data-webauthn-target="response" name="_auth_code">
 *     <p hidden data-webauthn-target="error"></p>
 *     <button type="button" data-action="webauthn#start">Use security key</button>
 *   </form>
 *
 * Both ceremonies start on click only: browsers and password managers expect a user gesture.
 * Another click cancels a pending ceremony and starts a new one.
 */
export default class extends Controller<HTMLFormElement> {
  static values = {
    options: String,
    ceremony: { type: String, default: "get" },
  };

  static targets = ["response", "error"];

  declare readonly optionsValue: string;
  declare readonly ceremonyValue: string;
  declare readonly responseTarget: HTMLInputElement;
  declare readonly errorTarget: HTMLElement;
  declare readonly hasErrorTarget: boolean;

  private submitted = false;

  private abortController?: AbortController;

  connect(): void {
    if (!window.PublicKeyCredential) {
      this.showError();
    }
  }

  disconnect(): void {
    this.abortController?.abort();
  }

  async start(event?: Event): Promise<void> {
    event?.preventDefault();

    if (this.submitted) {
      return;
    }

    if (this.hasErrorTarget) {
      this.errorTarget.hidden = true;
    }

    if (this.ceremonyValue === "create" && !this.element.reportValidity()) {
      return;
    }

    this.abortController?.abort();
    const abortController = new AbortController();
    this.abortController = abortController;

    try {
      const options = JSON.parse(this.optionsValue) as Json;
      const credential =
        this.ceremonyValue === "create"
          ? await navigator.credentials.create({
              publicKey: toCreationOptions(options),
              signal: abortController.signal,
            })
          : await navigator.credentials.get({
              publicKey: toRequestOptions(options),
              signal: abortController.signal,
            });

      if (!(credential instanceof PublicKeyCredential)) {
        throw new Error("No credential returned");
      }

      // Fields may have been changed during the ceremony; an invalid form would not be submitted
      if (!this.element.reportValidity()) {
        return;
      }

      this.submitted = true;
      this.responseTarget.value = JSON.stringify(serializeCredential(credential));
      this.element.requestSubmit();
    } catch {
      // A newer click replaced this ceremony
      if (!abortController.signal.aborted) {
        this.showError();
      }
    }
  }

  private showError(): void {
    if (this.hasErrorTarget) {
      this.errorTarget.hidden = false;
    }
  }
}

export function base64UrlToBuffer(value: string): ArrayBuffer {
  const base64 = value.replace(/-/g, "+").replace(/_/g, "/");
  const padded = base64.padEnd(base64.length + ((4 - (base64.length % 4)) % 4), "=");
  const binary = atob(padded);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }

  return bytes.buffer;
}

export function bufferToBase64Url(buffer: ArrayBuffer): string {
  const bytes = new Uint8Array(buffer);
  let binary = "";
  for (const byte of bytes) {
    binary += String.fromCharCode(byte);
  }

  return btoa(binary).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
}

function toDescriptors(descriptors: Json[] | undefined): PublicKeyCredentialDescriptor[] {
  return (descriptors ?? []).map((descriptor) => ({
    ...descriptor,
    type: "public-key",
    id: base64UrlToBuffer(descriptor.id),
  }));
}

export function toCreationOptions(options: Json): PublicKeyCredentialCreationOptions {
  return {
    ...options,
    challenge: base64UrlToBuffer(options.challenge),
    user: { ...options.user, id: base64UrlToBuffer(options.user.id) },
    excludeCredentials: toDescriptors(options.excludeCredentials),
  } as PublicKeyCredentialCreationOptions;
}

export function toRequestOptions(options: Json): PublicKeyCredentialRequestOptions {
  return {
    ...options,
    challenge: base64UrlToBuffer(options.challenge),
    allowCredentials: toDescriptors(options.allowCredentials),
  } as PublicKeyCredentialRequestOptions;
}

export function serializeCredential(credential: PublicKeyCredential): Json {
  const response = credential.response;
  const serialized: Json = {
    clientDataJSON: bufferToBase64Url(response.clientDataJSON),
  };

  if (response instanceof AuthenticatorAttestationResponse) {
    serialized.attestationObject = bufferToBase64Url(response.attestationObject);
    serialized.transports = response.getTransports?.() ?? [];
  } else if (response instanceof AuthenticatorAssertionResponse) {
    serialized.authenticatorData = bufferToBase64Url(response.authenticatorData);
    serialized.signature = bufferToBase64Url(response.signature);
    if (response.userHandle) {
      serialized.userHandle = bufferToBase64Url(response.userHandle);
    }
  }

  return {
    id: credential.id,
    rawId: bufferToBase64Url(credential.rawId),
    type: credential.type,
    response: serialized,
  };
}
