/**
 * Guard Classic notification dropdown ("New" list + Mark all as read) wiring.
 */
const fs = require("fs");
const path = require("path");

function read(rel) {
  return fs.readFileSync(path.join(__dirname, "../..", rel), "utf8");
}

describe("Classic notification dropdown", () => {
  test("messages.js renders unread-only New list, event click dismissal, and mark all as read", () => {
    const src = read("scripts/messages.js");
    expect(src).toContain("unread_only: 1");
    expect(src).toContain('id="msg_quick_mark_all"');
    expect(src).toContain("Mark all as read");
    expect(src).toContain("No new notifications.");
    expect(src).toContain('post("mark_seen", { id: targetId })');
    expect(src).toContain('post("mark_seen", {})');
    expect(src).toContain("dismissQuickItem");
    expect(src).toContain("markAllQuickSeen");
  });

  test("office.js strips query string from hotspot names so message deep links open CLIC", () => {
    const officeJs = read("scripts/office.js");
    expect(officeJs).toContain('String(rawName).split("?")[0].split("/")[0]');
  });

  test("choosology.css styles mark-all control including dark slate header theme", () => {
    const css = read("style/choosology.css");
    expect(css).toContain(".msg-quick-mark-all");
    expect(css).toContain('.msg-quick[data-clic-theme="slate"] .msg-quick-mark-all');
    expect(css).toContain(".msg-quick-item.is-unread");
  });
});
