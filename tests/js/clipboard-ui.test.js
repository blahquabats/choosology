/**
 * Guard Classic clipboard overlay UX polish.
 */
const fs = require("fs");
const path = require("path");

describe("Clipboard UI polish", () => {
  const js = fs.readFileSync(
    path.join(__dirname, "../../scripts/clipboard.js"),
    "utf8"
  );
  const css = fs.readFileSync(
    path.join(__dirname, "../../style/choosology.css"),
    "utf8"
  );

  test("shows ajaxloader while loading and expands after load", () => {
    expect(js).toContain("showLoadingState");
    expect(js).toContain("expandPanelAfterLoad");
    expect(js).toContain('class="ajaxloader"');
    expect(js).toContain("clip-modal-panel--loading");
    expect(js).toContain("MIN_LOAD_MS");
    expect(css).toContain(".clip-modal-panel--loading");
    expect(css).toContain("transition: max-height");
  });

  test("checklist has dismiss but no Done buttons", () => {
    expect(js).toContain("data-check-dismiss");
    expect(js).not.toContain("data-check-complete");
    expect(js).not.toContain(">Done</button>");
  });

  test("Save note is right-aligned and footer office/ledger links are gone", () => {
    expect(js).toContain("clip-row--pad-save");
    expect(css).toContain(".clip-row--pad-save");
    expect(js).not.toContain("clipboard_to_office");
    expect(js).not.toContain("clipboard_to_ledger");
    expect(js).not.toContain("Full experiment results");
    expect(js).not.toContain("Full DataScrip Ledger");
  });

  test("close control is enlarged", () => {
    expect(css).toMatch(/\.clip-modal-x\s*\{[^}]*font-size:\s*2\.15rem/s);
  });
});
