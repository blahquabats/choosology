/**
 * About and Try it yourself entry points.
 */
const fs = require("fs");
const path = require("path");

describe("Onboarding surfaces", () => {
  test("Classic and Lite home link About, and Try it yourself only when logged out", () => {
    const home = fs.readFileSync(path.join(__dirname, "../../home.php"), "utf8");
    const lite = fs.readFileSync(path.join(__dirname, "../../lite/index.php"), "utf8");
    expect(home).toContain('href="#/about"');
    expect(home).toContain('href="#/try"');
    expect(home).toContain("empty(\$_SESSION['user'])");
    expect(lite).toContain("about.php");
    expect(lite).toContain("try.php");
    expect(lite).toContain("empty(\$_SESSION['user'])");
  });

  test("Classic routes expose About and gate Try it yourself", () => {
    const routes = fs.readFileSync(path.join(__dirname, "../../scripts/routes.js"), "utf8");
    expect(routes).toContain("#/about");
    expect(routes).toContain("#/try/play");
    expect(routes).toContain("#/try");
    const tryRoute = routes.slice(routes.indexOf("#/try/play"), routes.indexOf("#/edit/:id"));
    expect(tryRoute).toContain('data-logged-in');
    expect(tryRoute).toContain("#/home");
  });

  test("Try draft uses the session editor path and does not post a real save", () => {
    const vised = fs.readFileSync(path.join(__dirname, "../../vised/vised.js"), "utf8");
    const ajax = fs.readFileSync(path.join(__dirname, "../../ajax/trydraft.php"), "utf8");
    const save = fs.readFileSync(path.join(__dirname, "../../vised/saveadv.php"), "utf8");
    expect(vised).toContain("ajax/trydraft.php");
    expect(vised).toContain("choosologyTryDraft");
    expect(vised).toContain("Apply for lab access");
    const trySave = vised.slice(vised.indexOf("function saveAdventure"), vised.indexOf("function saveAdventure") + 700);
    expect(trySave.indexOf("postTryDraft")).toBeGreaterThan(-1);
    expect(trySave.indexOf("vised/saveadv.php")).toBeGreaterThan(trySave.indexOf("postTryDraft"));
    expect(ajax).not.toContain("INSERT");
    expect(save).toContain("AND user =");
  });

  test("Play view keeps the draft on the try flag", () => {
    const viewJs = fs.readFileSync(path.join(__dirname, "../../scripts/view.js"), "utf8");
    const ajax = fs.readFileSync(path.join(__dirname, "../../ajax/screenajax.php"), "utf8");
    expect(viewJs).toContain('$("#try-draft")');
    expect(viewJs).toContain("screenAjaxData");
    expect(viewJs).toContain("$.parseJSON");
    expect(ajax).not.toContain("application/json");
    const lite = fs.readFileSync(path.join(__dirname, "../../lite/view.php"), "utf8");
    expect(lite).toContain("view.php?try=1&screen=");
  });

  test("Back to Try it yourself uses the #/try route", () => {
    const index = fs.readFileSync(path.join(__dirname, "../../scripts/index.js"), "utf8");
    const tryMenu = index.slice(index.indexOf("tryedit:"), index.indexOf("tryplay:"));
    expect(tryMenu).toContain("id: 'try'");
    expect(tryMenu).not.toContain("tryedit'");
    expect(index).toContain("location.href='#/'+backid");
    const routes = fs.readFileSync(path.join(__dirname, "../../scripts/routes.js"), "utf8");
    expect(routes).toContain("#/try");
    expect(routes).not.toContain("#/tryedit");
  });
});
