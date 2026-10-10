/**
 * Guard DataScrip / Ledger surface wiring in Classic shell scripts.
 */
const fs = require("fs");
const path = require("path");

describe("DataScrip UI wiring", () => {
  test("index.js Classic hub routes ledger via My Office", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/index.js"),
      "utf8"
    );
    expect(src).toContain("ms-room-hub-mode");
    expect(src).toContain("mystuff/ledger.php");
    expect(src).toContain("ChoosologyOffice");
  });

  test("office.js exposes shop buy and ledger hotspot", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/office.js"),
      "utf8"
    );
    expect(src).toContain("mystuff/ledger.php");
    expect(src).toContain('action: action');
    expect(src).toContain("ajax/office.php");
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
    const topboxChunk = src.slice(topIdx, topIdx + 1400);
    expect(topboxChunk).not.toContain("classic-lite-btn");
  });

  test("Classic nav labels My Office (not My Stuff)", () => {
    const indexSrc = fs.readFileSync(
      path.join(__dirname, "../../index.php"),
      "utf8"
    );
    const jsSrc = fs.readFileSync(
      path.join(__dirname, "../../scripts/index.js"),
      "utf8"
    );
    expect(indexSrc).toContain("My&nbsp;Office");
    expect(indexSrc).not.toContain("My&nbsp;Stuff");
    expect(jsSrc).toContain("name: 'My Office'");
    expect(jsSrc).not.toContain("name: 'My Stuff'");
  });

  test("comment Submit uses shared choosology-btn (not legacy fakebutton)", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../comments.php"),
      "utf8"
    );
    expect(src).toContain("choosology-btn choosology-btn--primary");
    expect(src).toContain("id='subcombutton'");
    expect(src).not.toContain("makeFakeButton(\"subcombutton\"");
  });

  test("logged-in header is compact icon chrome without DataScrip word", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../index.php"),
      "utf8"
    );
    const topIdx = src.indexOf('id="topbox"');
    const clusterEnd = src.indexOf("classic-top-cluster", topIdx);
    const end = clusterEnd > topIdx ? src.indexOf("</div>", src.indexOf("contentcontainer", topIdx)) : topIdx + 4000;
    const topboxChunk = src.slice(topIdx, Math.max(end, topIdx + 3500));
    expect(topboxChunk).not.toContain("DataScrip");
    expect(topboxChunk).not.toContain("msg-login-scrip-label");
    expect(topboxChunk).not.toContain("msg-login-notify-label");
    expect(topboxChunk).toContain("msg-login-row--compact");
    expect(topboxChunk).toContain("msg-login-icon-btn");
    expect(topboxChunk).toContain("images/icons/linecons-black/clip.png");
    expect(topboxChunk).toContain("images/icons/linecons-black/mail.png");
    expect(topboxChunk).toContain("msg_unread_badge");
  });
});
