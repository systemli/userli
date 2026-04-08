import { describe, it, expect } from "vitest";
import { sanitizeHTML, initializeSafeHtml } from "../../assets/js/sanitize";

describe("sanitizeHTML", () => {
  it("allows safe inline tags", () => {
    const input = "<b>bold</b> <i>italic</i> <em>emphasis</em> <strong>strong</strong> <u>underline</u>";
    expect(sanitizeHTML(input)).toBe(input);
  });

  it("allows block tags", () => {
    const input = "<p>paragraph</p><div>division</div><span>span</span>";
    expect(sanitizeHTML(input)).toBe(input);
  });

  it("allows br tags", () => {
    const input = "line one<br>line two";
    expect(sanitizeHTML(input)).toBe(input);
  });

  it("allows anchor tags with href, target, and class", () => {
    const input = '<a href="https://example.com" target="_blank" class="link">click</a>';
    expect(sanitizeHTML(input)).toBe(input);
  });

  it("strips script tags", () => {
    const input = '<script>alert("xss")</script>safe text';
    expect(sanitizeHTML(input)).toBe("safe text");
  });

  it("strips event handler attributes", () => {
    const input = '<b onclick="alert(1)">bold</b>';
    expect(sanitizeHTML(input)).toBe("<b>bold</b>");
  });

  it("allows heading and list tags", () => {
    const input = "<h1>heading</h1><h3>sub</h3><ul><li>item</li></ul><ol><li>first</li></ol>";
    expect(sanitizeHTML(input)).toContain("<h1>heading</h1>");
    expect(sanitizeHTML(input)).toContain("<h3>sub</h3>");
    expect(sanitizeHTML(input)).toContain("<ul><li>item</li></ul>");
    expect(sanitizeHTML(input)).toContain("<ol><li>first</li></ol>");
  });

  it("allows blockquote and hr tags", () => {
    const input = "<blockquote>quote</blockquote><hr>";
    expect(sanitizeHTML(input)).toContain("<blockquote>quote</blockquote>");
    expect(sanitizeHTML(input)).toContain("<hr>");
  });

  it("strips disallowed tags but keeps their text content", () => {
    const input = "<table><tr><td>cell</td></tr></table>";
    expect(sanitizeHTML(input)).not.toContain("<table>");
    expect(sanitizeHTML(input)).toContain("cell");
  });

  it("strips data attributes", () => {
    const input = '<div data-id="123">content</div>';
    expect(sanitizeHTML(input)).toBe("<div>content</div>");
  });

  it("strips style attributes", () => {
    const input = '<p style="color:red">text</p>';
    expect(sanitizeHTML(input)).toBe("<p>text</p>");
  });

  it("strips img tags", () => {
    const input = '<img src="x" onerror="alert(1)">';
    expect(sanitizeHTML(input)).toBe("");
  });

  it("strips iframe tags", () => {
    const input = '<iframe src="https://evil.com"></iframe>';
    expect(sanitizeHTML(input)).toBe("");
  });

  it("handles empty string", () => {
    expect(sanitizeHTML("")).toBe("");
  });

  it("handles plain text without tags", () => {
    expect(sanitizeHTML("just text")).toBe("just text");
  });

  it("is available as window.sanitizeHTML", () => {
    expect(window.sanitizeHTML).toBe(sanitizeHTML);
  });
});

describe("initializeSafeHtml", () => {
  it("sanitizes elements with data-safe-html attribute", () => {
    document.body.innerHTML = `
      <div data-safe-html><b>safe</b><script>alert(1)</script></div>
    `;

    initializeSafeHtml();

    const div = document.querySelector("div")!;
    expect(div.innerHTML).toBe("<b>safe</b>");
    expect(div.hasAttribute("data-safe-html")).toBe(false);
  });

  it("processes multiple elements", () => {
    document.body.innerHTML = `
      <p data-safe-html><em>one</em><script>x</script></p>
      <p data-safe-html><strong>two</strong><img src="x"></p>
    `;

    initializeSafeHtml();

    const paragraphs = document.querySelectorAll("p");
    expect(paragraphs[0].innerHTML).toBe("<em>one</em>");
    expect(paragraphs[1].innerHTML).toBe("<strong>two</strong>");
    expect(paragraphs[0].hasAttribute("data-safe-html")).toBe(false);
    expect(paragraphs[1].hasAttribute("data-safe-html")).toBe(false);
  });

  it("does nothing when no elements have data-safe-html", () => {
    document.body.innerHTML = "<p>untouched</p>";

    initializeSafeHtml();

    expect(document.querySelector("p")!.innerHTML).toBe("untouched");
  });
});
