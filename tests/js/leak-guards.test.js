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

  test("a My Office hotspot link is loaded once, after the window has opened", () => {
    const shell = fs.readFileSync(
      path.join(__dirname, "../../scripts/index.js"),
      "utf8"
    );
    const routes = fs.readFileSync(
      path.join(__dirname, "../../scripts/routes.js"),
      "utf8"
    );
    /* The route must leave the first load to the show-complete handler... */
    expect(shell).toContain('mystuffOpening = which === "mystuff"');
    expect(routes).toContain('activemenu === "mystuff" && !mystuffOpening');
    /* ...and the retry must not reload a room that already has a hotspot open. */
    expect(shell).toMatch(
      /retryToken !== window\.__choosologyLoadTabRetryToken[\s\S]{0,400}find\("\.ms-room-page"\)\.length\) return;/
    );
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
