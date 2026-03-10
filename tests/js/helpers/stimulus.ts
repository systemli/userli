import { Application, Controller } from "@hotwired/stimulus";

/**
 * Start a Stimulus controller in a jsdom environment for testing.
 *
 * Creates the DOM from the provided HTML, registers the controller with the
 * Stimulus application, and waits for the controller to connect.
 *
 * @param controllerClass - The Stimulus controller class to test
 * @param html - HTML string containing the controller's root element with
 *               `data-controller="<identifier>"`
 * @param identifier - The Stimulus controller identifier (e.g. "confirm",
 *                     "flash-notification"). Must match the `data-controller`
 *                     attribute in the HTML.
 * @returns The Stimulus Application instance and the controller's root element
 *
 * @example
 * ```ts
 * const { application, element } = await startController(
 *   ConfirmController,
 *   `<form data-controller="confirm">
 *      <button data-action="confirm#prompt">Submit</button>
 *    </form>`,
 *   "confirm"
 * );
 * ```
 */
export async function startController<T extends Controller>(
  controllerClass: new (...args: any[]) => T,
  html: string,
  identifier: string
): Promise<{ application: Application; element: HTMLElement }> {
  document.body.innerHTML = html;

  const element = document.querySelector(
    `[data-controller="${identifier}"]`
  ) as HTMLElement;

  if (!element) {
    throw new Error(
      `No element found with data-controller="${identifier}". ` +
        `Check that the HTML contains a matching data-controller attribute.`
    );
  }

  const application = Application.start();
  application.register(identifier, controllerClass);

  // Stimulus processes mutations asynchronously via MutationObserver.
  // We need to yield to the microtask queue so connect() is called.
  await new Promise<void>((resolve) => {
    queueMicrotask(resolve);
  });

  return { application, element };
}
