/**
 * Guard theater mode enter/exit animation wiring.
 */
const fs = require("fs");
const path = require("path");

describe("Theater mode animation", () => {
  test("view.js animates enter/exit and respects reduced motion", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../scripts/view.js"),
      "utf8"
    );
    expect(src).toContain("choosology-theater-anim-in");
    expect(src).toContain("choosology-theater-anim-out");
    expect(src).toContain("prefers-reduced-motion");
    expect(src).toContain("instant: true");
  });

  test("view.css defines theater enter/exit keyframes", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../style/view.css"),
      "utf8"
    );
    expect(src).toContain("@keyframes choosology-theater-in");
    expect(src).toContain("@keyframes choosology-theater-out");
    expect(src).toContain("choosology-theater-anim-in");
    expect(src).toContain("prefers-reduced-motion: no-preference");
  });
});
