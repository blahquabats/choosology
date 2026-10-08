/**
 * Guard DataScrip / Ledger surface wiring in Classic shell scripts.
 */
const fs = require("fs");
const path = require("path");

describe("DataScrip UI wiring", () => {
  test("index.js loadTab includes ledger", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/index.js"),
      "utf8"
    );
    expect(src).toContain('loc =="ledger"');
    expect(src).toContain("mystuff/ledger.php");
  });

  test("clipboard.js renders Ledger section", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/clipboard.js"),
      "utf8"
    );
    expect(src).toContain("renderLedger");
    expect(src).toContain("#/mystuff/ledger");
    expect(src).toContain("datascrip");
    expect(src).toContain("images/datascrip.png");
  });

  test("messages.js supports award_scrip", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/messages.js"),
      "utf8"
    );
    expect(src).toContain("award_scrip");
    expect(src).toContain("msg_scrip_send");
  });

  test("Classic Lite switch sits outside #topbox as a bevel button", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../index.php"),
      "utf8"
    );
    expect(src).toContain('class="classic-top-cluster"');
    expect(src).toContain('id="litebox"');
    expect(src).toContain("classic-lite-btn");
    expect(src).toContain("Instrument Panel terminal UI");
    expect(src).not.toContain("classic-lite-link");
    const liteIdx = src.indexOf('id="litebox"');
    const topIdx = src.indexOf('id="topbox"');
    expect(liteIdx).toBeGreaterThan(-1);
    expect(topIdx).toBeGreaterThan(liteIdx);
    const topboxChunk = src.slice(topIdx, topIdx + 900);
    expect(topboxChunk).not.toContain("classic-lite-btn");
  });
});
