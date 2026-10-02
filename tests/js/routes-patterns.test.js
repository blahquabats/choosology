/**
 * Lightweight route map coverage for Sammy route strings used by the SPA shell.
 * Full Sammy routing is out of scope; this guards the path patterns we rely on.
 */

const fs = require("fs");
const path = require("path");

describe("routes.js path patterns", () => {
  const src = fs.readFileSync(
    path.join(__dirname, "../../scripts/routes.js"),
    "utf8"
  );

  test("defines core hash routes", () => {
    [
      "#/home",
      "#/search",
      "#/browse",
      "#/view/:id",
      "#/viewadv/:id",
      "#/edit/:id"
    ].forEach((route) => {
      expect(src).toContain(route);
    });
  });

  test("mystuff requires login gate", () => {
    expect(src).toContain('data-logged-in');
    expect(src).toMatch(/mystuff/);
  });
});
