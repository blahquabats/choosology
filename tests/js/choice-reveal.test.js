/**
 * Guards for click-to-reveal choices and end-screen reveal flair (Classic + Lite).
 */
const fs = require("fs");
const path = require("path");

describe("Click-to-reveal choices and ending flair", () => {
  test("Classic view.php renders a collapsed click-to-reveal button instead of hover text", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../view.php"),
      "utf8"
    );
    expect(src).not.toContain("Hover to reveal choices");
    expect(src).toContain("Click to reveal choices");
    expect(src).toContain("is-collapsed");
    expect(src).toMatch(/<button[^>]+class=['"]choicecover['"]/);
    expect(src).toContain("aria-expanded='false'");
    expect(src).toContain("aria-controls='choicemeat'");
  });

  test("view.js reveals on click only, expands cleanly, and triggers flair on ending screens", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/view.js"),
      "utf8"
    );
    expect(src).not.toContain("mouseenter.ending");
    expect(src).not.toContain("mouseleave.ending");
    expect(src).toContain("click.choiceReveal");
    expect(src).toContain("resetChoiceReveal");
    expect(src).toContain("revealChoices");
    expect(src).toContain("triggerEndingFlair");
    expect(src).toContain("clearChoiceRevealTimers");
    expect(src).toContain("is-expanding");
    expect(src).toContain("is-ending-revealed");
    expect(src).toContain("is-ending-flair");
    expect(src).toContain("ending-flair-burst");
    expect(src).toContain("prefersReducedMotion");
  });

  test("view.css enforces uniform collapsed height and defines ending flair animations", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../style/view.css"),
      "utf8"
    );
    expect(src).toContain(".choices.is-collapsed .choicemeat");
    expect(src).toContain(".choices.is-revealed .choicecover");
    expect(src).toContain("height: 68px;");
    expect(src).toContain("@keyframes choosology-ending-glow");
    expect(src).toContain("@keyframes choosology-ending-sheen");
    expect(src).toContain("@keyframes choosology-ending-stamp");
    expect(src).toContain("@keyframes choosology-ending-spark");
    expect(src).toContain("prefers-reduced-motion: no-preference");
  });

  test("Classic loads the next screen before sliding and marks the selected choice", () => {
    const js = fs.readFileSync(
      path.join(__dirname, "../../scripts/view.js"),
      "utf8"
    );
    const css = fs.readFileSync(
      path.join(__dirname, "../../style/view.css"),
      "utf8"
    );
    const view = fs.readFileSync(
      path.join(__dirname, "../../view.php"),
      "utf8"
    );
    const ajax = fs.readFileSync(
      path.join(__dirname, "../../ajax/screenajax.php"),
      "utf8"
    );
    const go = js.slice(js.indexOf("function goToScreen"), js.indexOf("function reloadScreen"));
    const ajaxAt = go.indexOf("ajax/screenajax.php");
    const slideAt = go.indexOf("slideCurrentScreen");
    expect(ajaxAt).toBeGreaterThan(-1);
    expect(slideAt).toBeGreaterThan(ajaxAt);
    expect(go).toContain("markSelectedChoicePending");
    expect(go).toContain("$.parseJSON");
    expect(js).toContain("choice-spinner");
    expect(js).toContain("is-choice-selected");
    expect(css).toContain(".choice-spinner");
    expect(css).toContain("@keyframes choosology-choice-spin");
    expect(view).toContain(", '$sid', 0, this)");
    expect(ajax).toContain(",$screenid,0,this)");
  });

  test("Lite shows a Loading placeholder whose dots travel from one to four and back", () => {
    const php = fs.readFileSync(
      path.join(__dirname, "../../lite/view.php"),
      "utf8"
    );
    const js = fs.readFileSync(
      path.join(__dirname, "../../lite/play.js"),
      "utf8"
    );
    const css = fs.readFileSync(
      path.join(__dirname, "../../lite/style.css"),
      "utf8"
    );
    expect(php).toContain("lite/play.js");
    expect(js).toContain("Loading");
    expect(js).toContain("lite-loading-dots");
    expect(js).toContain("is-choice-pending");
    expect(js).toContain("aria-disabled");
    expect(js).toContain("location.assign");
    expect(css).toContain("@keyframes lite-loading-dots");
    expect(css).toContain('content: "."');
    expect(css).toContain('content: ".."');
    expect(css).toContain('content: "..."');
    expect(css).toContain('content: "...."');
    const one = css.indexOf('content: "."');
    const four = css.indexOf('content: "...."');
    expect(four).toBeGreaterThan(one);
  });

  test("Lite view wraps choices and endings in uniform click-to-reveal details with ending flair", () => {
    const php = fs.readFileSync(
      path.join(__dirname, "../../lite/view.php"),
      "utf8"
    );
    const css = fs.readFileSync(
      path.join(__dirname, "../../lite/style.css"),
      "utf8"
    );
    expect(php).toContain("lite-choices-reveal");
    expect(php).toContain("lite-choices-reveal--ending");
    expect(php).toContain("Click to reveal choices");
    expect(css).toContain(".lite-choices-summary");
    expect(css).toContain(".lite-choices-reveal[open] > .lite-choices-summary");
    expect(css).toContain(".lite-choices-reveal--ending[open] .lite-ending");
    expect(css).toContain("@keyframes lite-ending-flair");
  });

  test("Ending panels use an END NODE pill fieldset legend without redundant headers", () => {
    const classicPhp = fs.readFileSync(
      path.join(__dirname, "../../auxfuncs.php"),
      "utf8"
    );
    const litePhp = fs.readFileSync(
      path.join(__dirname, "../../lib/lite-helpers.php"),
      "utf8"
    );
    expect(classicPhp).toContain('<fieldset class="ending-panel-notice">');
    expect(classicPhp).toContain(
      '<legend class="ending-panel-legend"><span class="ending-panel-pill">END NODE</span></legend>'
    );
    expect(classicPhp).toContain(
      "This path through the experiment terminates here."
    );
    expect(classicPhp).not.toContain("You have reached an end");
    expect(classicPhp).not.toContain("Terminal outcome");

    expect(litePhp).toContain('<fieldset class="lite-ending" role="status">');
    expect(litePhp).toContain(
      '<legend class="lite-ending-legend"><span class="lite-ending-pill">END NODE</span></legend>'
    );
    expect(litePhp).toContain(
      "This path through the experiment terminates here."
    );
    expect(litePhp).not.toContain("You have reached an end");
    expect(litePhp).not.toContain("Terminal outcome");
  });
});
