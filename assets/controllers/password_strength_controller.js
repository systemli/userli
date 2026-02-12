import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */

/**
 * Password strength meter controller.
 *
 * Uses zxcvbn-ts to evaluate password strength in real-time.
 * Dictionaries are lazy-loaded on first focus to keep the initial
 * bundle small. The page locale (from <html lang>) selects the
 * zxcvbn translation language.
 *
 * Usage:
 *   <div data-controller="password-strength"
 *        data-password-strength-strong-label-value="Great password!"
 *        data-password-strength-min-length-value="12"
 *        data-password-strength-min-length-label-value="At least 12 characters">
 *     <input type="password" data-password-strength-target="input" />
 *     <div>
 *       <div data-password-strength-target="segment"></div> (x4)
 *     </div>
 *     <p data-password-strength-target="feedback"></p>
 *   </div>
 */
export default class extends Controller {
  static targets = ["input", "segment", "feedback"];

  static values = {
    strongLabel: { type: String, default: "" },
    minLength: { type: Number, default: 0 },
    minLengthLabel: { type: String, default: "" },
  };

  // Score-to-color mapping for the strength segments
  static SCORE_COLORS = [
    { active: ["bg-red-500", "dark:bg-red-400"], count: 1 },
    { active: ["bg-orange-500", "dark:bg-orange-400"], count: 2 },
    { active: ["bg-yellow-500", "dark:bg-yellow-400"], count: 3 },
    { active: ["bg-green-400", "dark:bg-green-500"], count: 4 },
    { active: ["bg-green-600", "dark:bg-green-400"], count: 4 },
  ];

  static INACTIVE_CLASSES = ["bg-gray-200", "dark:bg-gray-600"];

  static ALL_COLOR_CLASSES = [
    "bg-red-500",
    "dark:bg-red-400",
    "bg-orange-500",
    "dark:bg-orange-400",
    "bg-yellow-500",
    "dark:bg-yellow-400",
    "bg-green-400",
    "dark:bg-green-500",
    "bg-green-600",
    "dark:bg-green-400",
    "bg-gray-200",
    "dark:bg-gray-600",
  ];

  connect() {
    this._zxcvbn = null;
    this._debounceTimer = null;

    this._resetMeter();
  }

  disconnect() {
    if (this._debounceTimer) {
      clearTimeout(this._debounceTimer);
      this._debounceTimer = null;
    }
  }

  /**
   * Lazy-load zxcvbn-ts on first focus, then evaluate.
   * Bound via data-action="focus->password-strength#loadAndEvaluate:once"
   */
  loadAndEvaluate() {
    if (!this._zxcvbn) {
      this._loadZxcvbn().catch((err) => {
        console.error("Failed to load password strength library:", err);
      });
    }
  }

  /**
   * Evaluate password strength on input (debounced).
   * Bound via data-action="input->password-strength#evaluate"
   */
  evaluate() {
    clearTimeout(this._debounceTimer);
    this._debounceTimer = setTimeout(() => this._doEvaluate(), 150);
  }

  // -- Private --

  _loadZxcvbn() {
    const locale = (document.documentElement.lang || "en")
      .split("-")[0]
      .toLowerCase();

    return Promise.all([
      import("@zxcvbn-ts/core"),
      import("@zxcvbn-ts/language-common"),
      import("@zxcvbn-ts/language-en"),
      import("@zxcvbn-ts/language-de"),
    ]).then(([core, common, en, de]) => {
      // Use German translations for de and gsw (Swiss German), English for everything else
      const translations =
        locale === "de" || locale === "gsw" ? de.translations : en.translations;

      core.zxcvbnOptions.setOptions({
        dictionary: { ...common.dictionary, ...en.dictionary, ...de.dictionary },
        graphs: common.adjacencyGraphs,
        translations,
        useLevenshteinDistance: true,
      });

      this._zxcvbn = core.zxcvbn;
    });
  }

  _doEvaluate() {
    const password = this.inputTarget.value;

    if (!password) {
      this._resetMeter();
      return;
    }

    // Show minimum length hint if password is too short
    if (this.minLengthValue > 0 && password.length < this.minLengthValue) {
      this._updateMeter(0);
      if (this.hasFeedbackTarget && this.minLengthLabelValue) {
        this._showFeedback(this.minLengthLabelValue, false);
      }
      return;
    }

    if (!this._zxcvbn) return;

    const result = this._zxcvbn(password);
    this._updateMeter(result.score);

    if (this.hasFeedbackTarget) {
      if (result.score >= 3 && this.strongLabelValue) {
        this._showFeedback(this.strongLabelValue, true);
      } else {
        const text =
          result.feedback.warning || result.feedback.suggestions.join(" ");
        this._showFeedback(text, false);
      }
    }
  }

  _updateMeter(score) {
    const config =
      this.constructor.SCORE_COLORS[score] || this.constructor.SCORE_COLORS[0];

    this.segmentTargets.forEach((segment, index) => {
      this.constructor.ALL_COLOR_CLASSES.forEach((cls) =>
        segment.classList.remove(cls),
      );

      if (index < config.count) {
        config.active.forEach((cls) => segment.classList.add(cls));
      } else {
        this.constructor.INACTIVE_CLASSES.forEach((cls) =>
          segment.classList.add(cls),
        );
      }
    });
  }

  _resetMeter() {
    this.segmentTargets.forEach((segment) => {
      this.constructor.ALL_COLOR_CLASSES.forEach((cls) =>
        segment.classList.remove(cls),
      );
      this.constructor.INACTIVE_CLASSES.forEach((cls) =>
        segment.classList.add(cls),
      );
    });

    if (this.hasFeedbackTarget) {
      this.feedbackTarget.textContent = "";
      this.feedbackTarget.classList.add("hidden");
      this.feedbackTarget.classList.remove("text-green-600", "dark:text-green-400");
      this.feedbackTarget.classList.add("text-gray-500", "dark:text-gray-400");
    }
  }

  _showFeedback(text, isStrong) {
    const el = this.feedbackTarget;
    el.textContent = text;

    if (isStrong) {
      el.classList.remove("text-gray-500", "dark:text-gray-400");
      el.classList.add("text-green-600", "dark:text-green-400");
    } else {
      el.classList.remove("text-green-600", "dark:text-green-400");
      el.classList.add("text-gray-500", "dark:text-gray-400");
    }

    if (text) {
      el.classList.remove("hidden");
    } else {
      el.classList.add("hidden");
    }
  }
}
