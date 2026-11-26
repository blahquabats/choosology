/**
 * Guards for the graph editor status pill and icon ring.
 */
const fs = require("fs");
const path = require("path");

describe("Visual editor status pill and icon ring", () => {
  const setup = fs.readFileSync(path.join(__dirname, "../../vised/setup.js"), "utf8");
  const vised = fs.readFileSync(path.join(__dirname, "../../vised/vised.js"), "utf8");

  test("one status pill replaces the NOR CON DIS bar", () => {
    expect(setup).toContain('id: "status_pill"');
    expect(setup).toContain('text: "Ready"');
    expect(setup).toContain('text: "Cancel"');
    expect(setup).not.toContain('setText("NOR")');
    expect(setup).not.toContain('setText("CON")');
    expect(setup).not.toContain('setText("DIS")');
    expect(setup).not.toContain('id: "topbar"');
    expect(vised).toContain("function statusPillCopy");
    expect(vised).toContain("Connecting from \\u201c");
    expect(vised).toContain("Removing a link from \\u201c");
    expect(vised).toContain("click the next screen");
    expect(vised).toContain("click either end");
    expect(vised).toContain("function refreshStatusPill");
    expect(vised).toContain("function applyModeOutline");
    expect(vised).toContain("keydown.visedmode");
    expect(vised).toContain("resetView()");
  });

  test("empty canvas and Cancel return to normal", () => {
    expect(setup).toMatch(/bgrect\.on\('click'[\s\S]*modename !== "normal"[\s\S]*resetView\(\)/);
    expect(setup).toMatch(/status_pill_cancel[\s\S]*resetView\(\)/);
  });

  test("icon ring has five actions and no dead wedge", () => {
    expect(setup).toContain('id: "icon_ring"');
    expect(setup).toContain("function visedIconChain");
    expect(setup).toContain("function visedIconPen");
    expect(setup).toContain("function visedIconPage");
    expect(setup).toContain("function visedIconTrash");
    expect(setup).toContain('key: "connect"');
    expect(setup).toContain('key: "unlink"');
    expect(setup).toContain('key: "edit"');
    expect(setup).toContain('key: "new"');
    expect(setup).toContain('key: "delete"');
    expect(setup).toContain("startConnect(menubox)");
    expect(setup).toContain("startDisconnect(menubox)");
    expect(setup).toContain("editScreen(menubox)");
    expect(setup).toContain("createChild(menubox)");
    expect(setup).toContain("deleteBox(menubox)");
    expect(setup).toContain("context.arc(0, 0, 92");
    expect(setup).toContain("context.arc(0, 0, 120");
    expect(setup).toContain("Math.cos(rad) * 106");
    expect(setup).toContain('fill("evenodd")');
    expect(setup).toContain("strokeEnabled: false");
    expect(setup).toContain('name: "icon_ring_outer"');
    expect(setup).toContain('name: "icon_ring_inner"');
    expect(setup).toContain("radius: 28");
    expect(setup).toContain("function visedNearestRingAction");
    expect(setup).toContain('name: "icon_ring_donut"');
    expect(setup).not.toContain('name: "icon_ring_disk"');
    expect(setup).not.toContain('id: "save"');
    expect(setup).not.toContain('id: "reload"');
    expect(setup).toContain('id: "graph_pan"');
    expect(setup).toContain('id: "status_pan_text"');
    expect(setup).toContain("mousemove.visedpan");
    expect(vised).toContain("function layoutStatusPill");
    expect(vised).toContain("function formatPanCoord");
    expect(vised).toContain("function refreshOffscreenArrows");
    expect(vised).toContain('name: "offscreen_arrow"');
    expect(vised).toContain('"#fff8dc"');
    expect(vised).toContain("statusPanText.width(168)");
    expect(vised).toContain("measureText(");
    expect(vised).toContain('stage.off("mouseup.connect")');
    expect(vised).not.toContain('stage.off("mouseup");');
    expect(setup).not.toContain("[none]");
    expect(setup).not.toContain("function pieDraw");
    expect(vised).toContain("BackEaseOut");
    expect(vised).toContain("x: 0.08");
    expect(vised).toContain("if(mode.nomenu == 1) return false;");
  });
});
