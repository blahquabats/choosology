const fs = require("fs");
const path = require("path");

function read(rel) {
  return fs.readFileSync(path.join(__dirname, "../..", rel), "utf8");
}

describe("RESTRICTED admin hub wiring", () => {
  test("Classic shell and office expose the admin-only destination", () => {
    const index = read("index.php");
    const office = read("mystuff/office.php");
    const officeJs = read("scripts/office.js");
    expect(index).toContain("mystuff/restricted.php");
    expect(index).toContain("scripts/restricted.js");
    expect(index).toContain("$_SESSION['usertype']");
    expect(office).toContain('data-hotspot="restricted"');
    expect(office).toContain("RESTRICTED");
    expect(officeJs).toContain('restricted: "mystuff/restricted.php"');
    expect(officeJs).toContain("ms-room-overlay--full");
  });

  test("restricted client supports all requested admin tools", () => {
    const src = read("scripts/restricted.js");
    expect(src).toContain("save_mascot");
    expect(src).toContain("save_interaction");
    expect(src).toContain("preview_interaction");
    expect(src).toContain("award_degree");
    expect(src).toContain("award_scrip");
    expect(src).toContain("moderate_comment");
    expect(src).toContain("restricted_preview_mascot");
    expect(src).toContain("scan_poses");
    expect(src).toContain('.val("__new")');
    expect(src).toContain("availability");
    const page = read("mystuff/restricted.php");
    expect(page).toContain("Mascot Packs");
    expect(page).toContain("Dialog Beats");
    expect(page).toContain("restricted_mascot_availability");
    expect(page).not.toContain("restricted_interaction_mascot");
    expect(page).not.toContain("restricted_portrait_idle");
  });

  test("JSON endpoint enforces admin access", () => {
    const src = read("ajax/restricted.php");
    expect(src).toContain("choosology_restricted_is_admin");
    expect(src).toContain("Admin access required.");
    expect(src).toContain("403");
  });

  test("Lite offers a gated RESTRICTED page and Classic guide editor link", () => {
    const shell = read("lite/_bootstrap.php");
    const page = read("lite/restricted.php");
    expect(shell).toContain("restricted.php");
    expect(shell).toContain("RESTRICTED");
    expect(page).toContain("choosology_restricted_is_admin");
    expect(page).toContain("Open mascot and interaction editor in Classic");
    expect(page).toContain("Grant Degree");
    expect(page).toContain("Award DataScrip");
    expect(page).toContain("Comment review");
  });
});
