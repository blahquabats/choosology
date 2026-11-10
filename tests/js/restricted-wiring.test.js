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
    expect(officeJs).toContain('closest(".minicolors")');
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
    expect(src).toContain("minicolors");
    expect(src).toContain('setProperty("--guide-accent"');
    const css = read("style/choosology.css");
    expect(css).toContain(".restricted-btn--primary:hover");
    expect(css).toContain("color: #1c2833");
    const page = read("mystuff/restricted.php");
    expect(page).toContain("Mascot Packs");
    expect(page).toContain("Dialog Beats");
    expect(page).toContain("restricted_mascot_availability");
    expect(page).not.toContain("restricted_interaction_mascot");
    expect(page).not.toContain("restricted_portrait_idle");
  });

  test("News Desk and Degree records live in the hub", () => {
    const src = read("scripts/restricted.js");
    expect(src).toContain("save_news");
    expect(src).toContain("delete_news");
    expect(src).toContain("degree_record");
    expect(src).toContain("ChoosologyRestrictedFocus");
    const page = read("mystuff/restricted.php");
    expect(page).toContain('data-restricted-view="news"');
    expect(page).toContain("News Desk");
    expect(page).toContain("restricted_news_form");
    expect(page).toContain("restricted_degree_catalog");
    expect(page).toContain("restricted_record_form");
    const api = read("ajax/restricted.php");
    expect(api).toContain("'save_news'");
    expect(api).toContain("'delete_news'");
    expect(api).toContain("'degree_record'");
    expect(api).toContain("degree_counts");
  });

  test("admin tools outside the hub use the shared role gate, not a username", () => {
    const root = path.join(__dirname, "../..");
    ["news.php", "labfeed.php", "ajax/fetchcomments.php", "scripts/index.js", "lite/news.php"].forEach((rel) => {
      expect(read(rel)).not.toContain("Grasssmith");
    });
    expect(read("news.php")).toContain("choosology_restricted_is_admin()");
    expect(read("ajax/fetchcomments.php")).toContain("choosology_restricted_is_admin()");
    expect(fs.existsSync(path.join(root, "ajax/addnews.php"))).toBe(false);
    expect(fs.existsSync(path.join(root, "ajax/managenews.php"))).toBe(false);
    const shell = read("scripts/index.js");
    expect(shell).not.toContain("news-add-form");
    expect(shell).not.toContain("managenews.php");
    expect(shell).toContain("news-admin-desk-link");
    expect(read("news.php")).toContain('href="#/mystuff/restricted"');
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
    expect(page).toContain("News desk");
    expect(page).toContain("choosology_news_admin_save");
    expect(page).toContain("choosology_restricted_degree_record");
    expect(read("lite/news.php")).toContain("restricted.php?news=news:");
  });
});
