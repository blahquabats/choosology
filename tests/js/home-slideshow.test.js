/**
 * Guards for the home page featured slideshow layout and Experiment notes styling.
 */
const fs = require("fs");
const path = require("path");

describe("Home page featured slideshow layout", () => {
  test("buildadvflag.php places byline and publishing date in one row and separates Experiment notes header/body", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../buildadvflag.php"),
      "utf8"
    );
    expect(src).toContain("slideinfo-byline-row");
    expect(src).toContain("slideinfo-byline");
    expect(src).toContain("slideinfo-date");
    expect(src).toContain("slideinfo--no-icon");
    expect(src).toContain("expnotes-header");
    expect(src).toContain("expnotes-body");
    expect(src).not.toContain("<b>Experiment&nbsp;notes:</b>");
  });

  test("choosology.css sizes the featured card to its notes and lets Recent Updates match", () => {
    const css = fs.readFileSync(
      path.join(__dirname, "../../style/choosology.css"),
      "utf8"
    );
    expect(css).toMatch(
      /\.home-copy-card--featured\s+\.featuredslides-cycle[\s\S]*?height:\s*auto;/
    );
    expect(css).toMatch(
      /\.home-copy-card--featured\s+\.featuredslides-cycle[\s\S]*?max-height:\s*none;/
    );
    expect(css).toMatch(
      /\.home-copy-card--featured\s+\.featuredslides\s+\.slidefolder[\s\S]*?height:\s*auto;/
    );
    expect(css).toMatch(
      /\.home-copy-card--feed[\s\S]*?align-self:\s*stretch;[\s\S]*?max-height:\s*none;[\s\S]*?overflow:\s*visible;/
    );
    expect(css).toMatch(
      /\.slideinfo-byline-row[\s\S]*?display:\s*flex;[\s\S]*?justify-content:\s*space-between;/
    );
    expect(css).toMatch(
      /\.slideinfo-date[\s\S]*?margin-left:\s*auto;[\s\S]*?text-align:\s*right;/
    );
    expect(css).toMatch(
      /\.expnotes[\s\S]*?text-transform:\s*uppercase;[\s\S]*?border:\s*1px solid #2874a6;/
    );
    expect(css).toMatch(
      /\.expnotes-body[\s\S]*?overflow:\s*visible;/
    );
    expect(css).toMatch(
      /\.home-copy-card--featured #featadvs\.home-featured-mount[\s\S]*?width:\s*auto;[\s\S]*?max-width:\s*100%;/
    );
    expect(css).toMatch(
      /\.home-copy-card--featured\s+\.featuredslides\s+\.slidenav-right[\s\S]*?right:\s*2px;/
    );
    expect(css).toMatch(/@media\s*\(max-width:\s*560px\),\s*\(max-height:\s*520px\)/);
  });

  test("home.php fits the slideshow to the tallest slide and only caps it on very small viewports", () => {
    const src = fs.readFileSync(
      path.join(__dirname, "../../home.php"),
      "utf8"
    );
    expect(src).toContain("function fitFeaturedSlides()");
    expect(src).toContain("offsetHeight");
    expect(src).toContain("resize.featuredSlides");
    expect(src).toContain("containerResize: 0");
    expect(src).toContain("slideResize: 0");
    expect(src).toContain("(max-width: 560px), (max-height: 520px)");
  });
});
