/**
 * Source-level guards for SPA teardown / race fixes.
 */
const fs = require("fs");
const path = require("path");

describe("Leak and race guards", () => {
  test("index.js guards menu navigation with generation counter", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/index.js"),
      "utf8"
    );
    expect(src).toContain("menuNavGen");
    expect(src).toMatch(/var gen = \+\+menuNavGen/);
    expect(src).toContain("if (gen !== menuNavGen) return");
  });

  test("view.js clears theater via controller and guards screen AJAX", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/view.js"),
      "utf8"
    );
    expect(src).toContain("theaterCtl");
    expect(src).toContain("clearViewTimers");
    expect(src).toContain("screenNavGen");
    expect(src).toContain('setTheater(false, { instant: true })');
  });

  test("office.js namespaces and offs handlers on remount", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/office.js"),
      "utf8"
    );
    expect(src).toContain("click.choosologyOffice");
    expect(src).toContain('.off("click.choosologyOffice")');
  });

  test("messages.js namespaces scrip scope change handler", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/messages.js"),
      "utf8"
    );
    expect(src).toContain("change.msgScripScope");
    expect(src).toContain('.off("change.msgScripScope")');
  });
});
