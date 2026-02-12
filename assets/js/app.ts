import "../css/app.css";
import "../bootstrap.js";
import { initializeSafeHtml } from "./sanitize";

document.addEventListener("DOMContentLoaded", () => {
  initializeSafeHtml();
});
