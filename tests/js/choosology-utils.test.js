const utils = require("../../scripts/lib/choosology-utils.js");

describe("ChoosologyUtils", () => {
  test("count counts own enumerable values", () => {
    expect(utils.count({ a: 1, b: 2, c: undefined })).toBe(2);
    expect(utils.count([])).toBe(0);
  });

  test("stripTags removes disallowed tags", () => {
    const out = utils.stripTags("<p>Hi <script>x</script><b>there</b></p>", "<b>");
    expect(out).toContain("<b>there</b>");
    expect(out).not.toContain("script");
    expect(out).not.toContain("<p>");
  });

  test("degreesToRadians", () => {
    expect(utils.degreesToRadians(180)).toBeCloseTo(Math.PI);
    expect(utils.degreesToRadians(0)).toBe(0);
  });

  test("urlSafe builds paths", () => {
    expect(utils.urlSafe("ajax/foo.php")).toBe("/ajax/foo.php");
    expect(utils.urlSafe("/ajax/foo.php")).toBe("/ajax/foo.php");
    expect(utils.urlSafe("")).toBe("/");
    expect(utils.urlSafe("x", (p) => "/base/" + p)).toBe("/base/x");
  });

  test("normalizeClicTheme", () => {
    expect(utils.normalizeClicTheme("violet")).toBe("violet");
    expect(utils.normalizeClicTheme("nope")).toBe("amber");
    expect(utils.normalizeClicTheme(null)).toBe("amber");
  });

  test("formatUnreadBadge", () => {
    expect(utils.formatUnreadBadge(0)).toEqual({
      count: 0,
      label: "CLIC",
      badgeText: "0",
      showBadge: false
    });
    expect(utils.formatUnreadBadge(3).label).toBe("CLIC (3)");
    expect(utils.formatUnreadBadge(120).badgeText).toBe("99+");
    expect(utils.formatUnreadBadge(120).showBadge).toBe(true);
  });

  test("isValidExperimentTitle", () => {
    expect(utils.isValidExperimentTitle("  My Exp ")).toBe(true);
    expect(utils.isValidExperimentTitle("   ")).toBe(false);
    expect(utils.isValidExperimentTitle("")).toBe(false);
  });

  test("filterUnreadQuickItems keeps only unseen items", () => {
    const items = [
      { id: 10, title: "New award", seen: false },
      { id: 9, title: "Already read", seen: true },
      { id: 8, title: "New comment", seen: false }
    ];
    expect(utils.filterUnreadQuickItems(items).map((x) => x.id)).toEqual([10, 8]);
    expect(utils.filterUnreadQuickItems(null)).toEqual([]);
  });

  test("removeQuickItemById removes matching event without mutating input", () => {
    const items = [
      { id: 10, title: "A", seen: false },
      { id: 9, title: "B", seen: false },
      { id: 8, title: "C", seen: false }
    ];
    const snapshot = JSON.stringify(items);
    const res = utils.removeQuickItemById(items, "9");
    expect(res.removed).toBe(true);
    expect(res.items.map((x) => x.id)).toEqual([10, 8]);
    expect(JSON.stringify(items)).toBe(snapshot);

    const miss = utils.removeQuickItemById(res.items, 999);
    expect(miss.removed).toBe(false);
    expect(miss.items.map((x) => x.id)).toEqual([10, 8]);

    const invalid = utils.removeQuickItemById(res.items, 0);
    expect(invalid.removed).toBe(false);
    expect(invalid.items).toHaveLength(2);
  });
});
