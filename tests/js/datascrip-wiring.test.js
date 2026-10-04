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
  });

  test("messages.js supports award_scrip", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/messages.js"),
      "utf8"
    );
    expect(src).toContain("award_scrip");
    expect(src).toContain("msg_scrip_send");
  });
});
