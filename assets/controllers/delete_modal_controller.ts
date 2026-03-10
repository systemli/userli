/**
 * A second instance of the modal controller, registered under a separate
 * Stimulus identifier ("delete-modal").
 *
 * This allows pages that already use `data-controller="modal"` for another
 * modal (e.g. the OpenPGP upload modal) to have a second, independent modal
 * on the same page. Targets and actions are prefixed with "delete-modal"
 * instead of "modal", for example:
 *
 *   data-delete-modal-target="overlay"
 *   data-action="delete-modal#open"
 *   data-delete-modal-action-param="/delete/123"
 */
import ModalController from "./modal_controller";

export default class extends ModalController {}
