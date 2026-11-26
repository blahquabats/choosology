/* setup.js: Create proto-objects, menu and background assets, stage and layer configurations */
Konva.pixelRatio = 1;
Konva.legacyTextRendering = true;

/* PROTO-OBJECTS */

        var prototext = new Konva.Text({
            fill: config.std_color,
            x: 14,
            y: 21,
            fontSize: 12,
            fontFamily: 'Consolas, "Courier New", monospace',
            fontStyle: 'bold',
            width: 154,
            height: 24,
            align: 'left',
            verticalAlign: 'middle',
            wrap: 'none',
            ellipsis: true,
            listening: false
        });
        var protobox = new Konva.Rect({
            stroke: config.box_border,
            strokeWidth: 2,
            fill: config.box_color,
            fillPatternImage: config.box_bg,
            cornerRadius: 7,
            x: 0,
            y: 0,
            width: 184,
            height: 54,
            shadowColor: '#263124',
            shadowBlur: 5,
            shadowOffset: { x: 2, y: 3 },
            shadowOpacity: 0.18,
            padding: 5
        });
        protobox.transformsEnabled = 'position';
        protobox.perfectDrawEnabled(false);
        protobox.on('click', function(evt){
            if(mode.nomenu == 1) return false;
            if(menuup) 
            {
                closeMenu();
                return false;
            }
            openMenu(this.name());

            evt.cancel=true;
            evt.returnValue=false;
            evt.cancelBubble=true;
            if (evt.stopPropagation) evt.stopPropagation();
            if (evt.preventDefault) evt.preventDefault();
            return false;
        });
        protobox.on("dblclick", function (e){
            editScreen(boxgroups[this.name()] || menubox);
        });
        var protoboxgroup = new Konva.Group({
           width: 184,
           height:54,
           draggable:true
           
        });
        protoboxgroup.dragDistance(3);
        protoboxgroup.transformsEnabled = 'position';
        
        protoboxgroup.on('mouseenter', function(e){
            boxMouseover(this);
            });
        protoboxgroup.on('mouseleave', function(e){
            boxMouseout(this);
            });
        protoboxgroup.on('dragmove', function(e){
            updateConnections(this.name());
        });
        protoboxgroup.on('dragend', function(e){
            skip=5;
            updateConnections(this.name());
            if (typeof refreshOffscreenArrows === "function") refreshOffscreenArrows();
        });
        var protolabel = new Konva.Label({
            x: -50,
            y: -50,
            name: "dummy",
            opacity: 1
        });
        protolabel.add(new Konva.Tag({
          fill: config.to_box_color,
          stroke: config.to_color,
          shadowColor: 'black',
          shadowBlur: 10,
          shadowOffset: [10, 10],
          shadowOpacity: 0.2,
          lineJoin: 'round',
          cornerRadius: 5
        }));
        protolabel.add(new Konva.Text({
          text: "dummy",
          fontSize: 10,
          lineHeight: 1.2,
          padding: 3,
          fill: 'black'
         }));
    
    /* MENU RING + STATUS PILL */

function visedIconChain(broken) {
    var g = new Konva.Group({ listening: false });
    var color = "#243044";
    function link(x, rot) {
        return new Konva.Rect({
            x: x,
            y: 0,
            width: 12,
            height: 8,
            offsetX: 6,
            offsetY: 4,
            cornerRadius: 4,
            stroke: color,
            strokeWidth: 1.6,
            rotation: rot,
            listening: false
        });
    }
    if (!broken) {
        g.add(link(-3, -32));
        g.add(link(3, 32));
    } else {
        g.add(link(-6, -32));
        g.add(link(6, 32));
        g.add(new Konva.Line({
            points: [-2, -5, 2, 5],
            stroke: color,
            strokeWidth: 1.6,
            lineCap: "round",
            listening: false
        }));
    }
    return g;
}

function visedIconPen() {
    var g = new Konva.Group({ listening: false });
    var ink = "#1c5a7a";
    g.add(new Konva.Rect({
        x: -8, y: -7, width: 11, height: 14,
        stroke: ink, strokeWidth: 1.5, cornerRadius: 1, listening: false
    }));
    g.add(new Konva.Line({ points: [-6, -3, 0, -3], stroke: ink, strokeWidth: 1, listening: false }));
    g.add(new Konva.Line({ points: [-6, 0, 0, 0], stroke: ink, strokeWidth: 1, listening: false }));
    g.add(new Konva.Line({ points: [-6, 3, -1, 3], stroke: ink, strokeWidth: 1, listening: false }));
    g.add(new Konva.Line({
        points: [1, 6, 8, -5],
        stroke: "#8a6a2f",
        strokeWidth: 1.8,
        lineCap: "round",
        listening: false
    }));
    g.add(new Konva.Circle({
        x: 8, y: -5, radius: 1.3, fill: "#8a6a2f", listening: false
    }));
    return g;
}

function visedIconPage() {
    var g = new Konva.Group({ listening: false });
    var c = "#2f9e44";
    g.add(new Konva.Line({
        points: [-7, -8, 2, -8, 7, -3, 7, 8, -7, 8],
        closed: true,
        stroke: c,
        strokeWidth: 1.5,
        listening: false
    }));
    g.add(new Konva.Line({
        points: [2, -8, 2, -3, 7, -3],
        stroke: c,
        strokeWidth: 1.2,
        listening: false
    }));
    g.add(new Konva.Line({ points: [-1, 1, 3, 1], stroke: c, strokeWidth: 1.6, listening: false }));
    g.add(new Konva.Line({ points: [1, -1, 1, 3], stroke: c, strokeWidth: 1.6, listening: false }));
    return g;
}

function visedIconTrash() {
    var g = new Konva.Group({ listening: false });
    var c = "#c94b4b";
    g.add(new Konva.Line({
        points: [-6, -5, 6, -5],
        stroke: c, strokeWidth: 1.5, lineCap: "round", listening: false
    }));
    g.add(new Konva.Rect({
        x: -2.2, y: -8, width: 4.4, height: 2.6,
        stroke: c, strokeWidth: 1.2, listening: false
    }));
    g.add(new Konva.Line({
        points: [-5, -3, -3.5, 7, 3.5, 7, 5, -3],
        closed: true,
        stroke: c,
        strokeWidth: 1.5,
        listening: false
    }));
    g.add(new Konva.Line({ points: [-1.2, -1, -1.2, 5], stroke: c, strokeWidth: 1, listening: false }));
    g.add(new Konva.Line({ points: [1.4, -1, 1.4, 5], stroke: c, strokeWidth: 1, listening: false }));
    return g;
}

function visedRingAction(spec) {
    var rad = spec.angle * Math.PI / 180;
    var ix = Math.cos(rad) * 106;
    var iy = Math.sin(rad) * 106;
    var g = new Konva.Group({ x: ix, y: iy, name: "ring_" + spec.key, id: "ring_" + spec.key });
    var hit = new Konva.Circle({
        radius: 28,
        fill: "#fffdf6",
        stroke: "#d6a84f",
        strokeWidth: 1.25,
        name: "ring_hit"
    });
    var icon = spec.icon();
    icon.scale({ x: 1.45, y: 1.45 });
    icon.listening(false);
    var label = new Konva.Text({
        text: spec.label,
        fontSize: 9,
        fontFamily: 'Consolas, "Courier New", monospace',
        fontStyle: "bold",
        fill: spec.labelFill || "#243044",
        width: 64,
        align: "center",
        x: -32,
        y: 12,
        listening: false
    });
    g.add(hit);
    g.add(icon);
    g.add(label);
    hit.on("mouseenter", function () {
        hit.fill(spec.hover);
        if (typeof layerScene !== "undefined" && layerScene) layerScene.batchDraw();
    });
    hit.on("mouseleave", function () {
        hit.fill("#fffdf6");
        if (typeof layerScene !== "undefined" && layerScene) layerScene.batchDraw();
    });
    g.on("click tap", function (e) {
        e.cancelBubble = true;
        spec.fn();
    });
    visedRingActions.push({ angle: spec.angle, fn: spec.fn, key: spec.key });
    return g;
}

function visedNearestRingAction(deg) {
    var best = null;
    var bestD = 1e9;
    var i;
    for (i = 0; i < visedRingActions.length; i++) {
        var d = Math.abs(deg - visedRingActions[i].angle);
        if (d > 180) d = 360 - d;
        if (d < bestD) {
            bestD = d;
            best = visedRingActions[i];
        }
    }
    return best;
}

var visedRingActions = [];

    var stage = new Konva.Stage({
        container: 'visedcontainer',
        name: "allstage",
        width: 1800,
        height: 800
    });

    var c = $('#canvas');
    var layers = [];
    var menuup = false;
    var dirtyboxes = [];
    var ringTween = null;

    var layerScene = new Konva.Layer();
    var graphPan = new Konva.Group({ id: "graph_pan", name: "graph_pan" });
    var groupLines = new Konva.Group({ listening: false });
    var groupBoxes = new Konva.Group();
    var groupLabels = new Konva.Group({ listening: false });
    var groupMenuRoot = new Konva.Group({
        id: 'menu_root',
        name: 'menu_root',
        visible: false
    });

    layerScene.add(graphPan);
    graphPan.add(groupLines);
    graphPan.add(groupBoxes);
    graphPan.add(groupLabels);
    graphPan.add(groupMenuRoot);

    layers['bg'] = new Konva.Layer();
    layers['lines'] = groupLines;
    layers['boxes'] = groupBoxes;
    layers['labels'] = groupLabels;
    layers['menu'] = groupMenuRoot;

    var bgrect = new Konva.Rect({
            id: "bgrect",
            name: "bgrect",
            stroke: "transparent",
            fill: "rgba(0,0,0,0.001)",
            strokeWidth: 0,
            x: -4000,
            y: -4000,
            width: 12000,
            height: 10000
        });
    var visedPan = null;
    var visedPanSuppressClick = false;
    bgrect.on("mousedown", function (evt) {
        if (evt.evt && evt.evt.button !== undefined && evt.evt.button !== 0) return;
        visedPan = {
            x: graphPan.x(),
            y: graphPan.y(),
            px: evt.evt.clientX,
            py: evt.evt.clientY,
            moved: false
        };
    });
    bgrect.on('click', function(evt){
        if (visedPanSuppressClick) {
            visedPanSuppressClick = false;
            evt.cancelBubble = true;
            return;
        }
        if (menuup || modename !== "normal") {
            resetView();
        }
        evt.cancelBubble = true;
    });

    var ringGroup = new Konva.Group({ id: "icon_ring", name: "icon_ring" });
    function visedDonutPath(context) {
        context.beginPath();
        context.arc(0, 0, 120, 0, Math.PI * 2, false);
        context.moveTo(92, 0);
        context.arc(0, 0, 92, 0, Math.PI * 2, true);
        context.closePath();
    }
    var ringDonut = new Konva.Shape({
        sceneFunc: function (context, shape) {
            visedDonutPath(context);
            context.fillStyle = shape.fill();
            context.fill("evenodd");
        },
        hitFunc: function (context) {
            visedDonutPath(context);
            context.fill("evenodd");
        },
        fill: "rgba(255, 248, 220, 0.96)",
        strokeEnabled: false,
        listening: true,
        name: "icon_ring_donut"
    });
    ringDonut.on("click tap", function (e) {
        e.cancelBubble = true;
        var p = ringGroup.getRelativePointerPosition();
        if (!p) return;
        var deg = Math.atan2(p.y, p.x) * 180 / Math.PI;
        var best = visedNearestRingAction(deg);
        if (best) best.fn();
    });
    ringGroup.add(ringDonut);
    ringGroup.add(new Konva.Circle({
        radius: 120,
        stroke: "#d6a84f",
        strokeWidth: 1.5,
        listening: false,
        name: "icon_ring_outer"
    }));
    ringGroup.add(new Konva.Circle({
        radius: 92,
        stroke: "#d6a84f",
        strokeWidth: 1.5,
        listening: false,
        name: "icon_ring_inner"
    }));
    ringGroup.add(visedRingAction({
        key: "connect",
        label: "Connect",
        angle: -90,
        hover: "rgba(51, 170, 255, 0.2)",
        icon: function () { return visedIconChain(false); },
        fn: function () { startConnect(menubox); }
    }));
    ringGroup.add(visedRingAction({
        key: "new",
        label: "New",
        angle: -18,
        labelFill: "#1d6b32",
        hover: "rgba(47, 158, 68, 0.2)",
        icon: visedIconPage,
        fn: function () { createChild(menubox); }
    }));
    ringGroup.add(visedRingAction({
        key: "edit",
        label: "Edit",
        angle: 54,
        hover: "rgba(51, 170, 255, 0.2)",
        icon: visedIconPen,
        fn: function () { editScreen(menubox); }
    }));
    ringGroup.add(visedRingAction({
        key: "delete",
        label: "Delete",
        angle: 126,
        labelFill: "#8b2e2e",
        hover: "rgba(201, 75, 75, 0.22)",
        icon: visedIconTrash,
        fn: function () { deleteBox(menubox); }
    }));
    ringGroup.add(visedRingAction({
        key: "unlink",
        label: "Unlink",
        angle: 198,
        hover: "rgba(51, 170, 255, 0.2)",
        icon: function () { return visedIconChain(true); },
        fn: function () { startDisconnect(menubox); }
    }));
    groupMenuRoot.add(ringGroup);
    graphPan.add(bgrect);
    bgrect.moveToBottom();

    var statusPill = new Konva.Group({
        id: "status_pill",
        name: "status_pill",
        x: 10,
        y: 10
    });
    var statusPillBg = new Konva.Rect({
        id: "status_pill_bg",
        cornerRadius: 16,
        fill: "#fff8dc",
        stroke: "#d6a84f",
        strokeWidth: 1.5,
        width: 86,
        height: 32,
        listening: false
    });
    var statusPillText = new Konva.Text({
        id: "status_pill_text",
        text: "Ready",
        x: 14,
        y: 8,
        fontSize: 13,
        fontFamily: 'Consolas, "Courier New", monospace',
        fontStyle: "bold",
        fill: "#2a2a6a",
        listening: false
    });
    var statusPanText = new Konva.Text({
        id: "status_pan_text",
        text: "x      0   y      0",
        x: 16,
        y: 40,
        fontSize: 12,
        fontFamily: 'Consolas, "Courier New", monospace',
        fill: "#243044",
        listening: false
    });
    var statusCancel = new Konva.Group({
        id: "status_pill_cancel",
        name: "status_pill_cancel",
        x: 0,
        y: 4,
        visible: false
    });
    statusCancel.add(new Konva.Rect({
        width: 68,
        height: 24,
        cornerRadius: 12,
        fill: "rgba(255,255,255,0.9)",
        stroke: "#243044",
        strokeWidth: 1
    }));
    statusCancel.add(new Konva.Text({
        text: "Cancel",
        width: 68,
        align: "center",
        y: 5,
        fontSize: 12,
        fontFamily: 'Consolas, "Courier New", monospace',
        fontStyle: "bold",
        fill: "#243044",
        listening: false
    }));
    statusCancel.on("click tap", function (e) {
        e.cancelBubble = true;
        resetView();
    });
    statusCancel.on("mouseenter", function () {
        document.body.style.cursor = "pointer";
    });
    statusCancel.on("mouseleave", function () {
        document.body.style.cursor = (mode && mode.cursor) || "default";
    });
    statusPill.add(statusPillBg);
    statusPill.add(statusPillText);
    statusPill.add(statusPanText);
    statusPill.add(statusCancel);

    layers['bg'].add(statusPill);
    var offscreenArrows = new Konva.Group({
        id: "offscreen_arrows",
        name: "offscreen_arrows",
        listening: false
    });
    layers['bg'].add(offscreenArrows);

    stage.add(layerScene);
    stage.add(layers['bg']);
    groupMenuRoot.hide();

    stage.on("mousemove.visedpan", function (evt) {
        if (!visedPan || !evt.evt) return;
        var dx = evt.evt.clientX - visedPan.px;
        var dy = evt.evt.clientY - visedPan.py;
        if (!visedPan.moved && Math.abs(dx) + Math.abs(dy) < 4) return;
        visedPan.moved = true;
        graphPan.position({ x: visedPan.x + dx, y: visedPan.y + dy });
        if (typeof refreshPanReadout === "function") refreshPanReadout();
        layerScene.batchDraw();
    });
    stage.on("mouseup.visedpan", function () {
        if (visedPan && visedPan.moved) visedPanSuppressClick = true;
        visedPan = null;
    });

    /* TWEENS removed with the unlabeled save rectangle. */

/* Konva 10: fillPatternImage can paint black until layers redraw after PNG decode; hover called draw() which masked this. */
window.__konvaPatternImagesRedraw = function () {
    try {
        if (typeof layers === 'undefined' || typeof stage === 'undefined') return;
        if (typeof layerScene !== 'undefined' && layerScene) layerScene.batchDraw();
        if (layers['bg']) layers['bg'].batchDraw();
        stage.batchDraw();
    } catch (err) {}
};
(function () {
    var imgs = [texPurple, texRed, texWhite, texGreen, texTeal, texYellow, texOrange];
    function bump() {
        window.__konvaPatternImagesRedraw();
    }
    imgs.forEach(function (img) {
        img.addEventListener('load', bump);
        img.addEventListener('error', bump);
    });
    bump();
    requestAnimationFrame(bump);
})();
