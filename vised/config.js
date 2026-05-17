/* CONFIG */
    var connto = [];
    var connfrom = [];
    var lines = [];
    var boxes = [];
    var boxgroups = [];
    var currinfo = [];
    currinfo['adv'] = [];
    currinfo['screens'] = [];
    var skip = 0;
    var modename = "normal";
    var modes = {
        normal: {
            nomenu: 0,
            cursor: "default"
        },
        connect_to: {
            nomenu: 1,
            cursor: "e-resize"
        },
        disconnect_from: {
            nomenu: 1,
            cursor: "no-drop"
        }
    };
    var mode = modes[modename];
    var menubox;
    var stopmenuonce = 0;
    var _tex = typeof choosologyUrl === 'function' ? choosologyUrl : function (p) {
        return '/' + String(p).replace(/^\//, '');
    };
    var texPurple = new Image();
    texPurple.src = _tex('images/tex-purple.png');
    var texRed = new Image();
    texRed.src = _tex('images/tex-red.png');
    var texWhite = new Image();
    texWhite.src = _tex('images/tex-white.png');
    var texGreen = new Image();
    texGreen.src = _tex('images/tex-green.png');
    var texTeal = new Image();
    texTeal.src = _tex('images/tex-teal.png');
    var texYellow = new Image();
    texYellow.src = _tex('images/tex-yellow.png');
    var texOrange = new Image();
    texOrange.src = _tex('images/tex-orange.png');
    var config = {
      std_color: "#000000",
      box_color: "#fff8dc",
      box_bg: texWhite,
      box_color_highlight: "#fffdf2",
      box_bg_highlight: texYellow,
      box_border: "#576b55",
      box_border_highlight: "#1f7a5a",
      box_stripe: "#d6a84f",
      to_color: "#2f9e44",
      from_color: "#c94b4b",
      to_box_color: "#bbffbb",
      to_box_bg: texGreen,
      from_box_color: "#ffcdcd",
      from_box_bg: texRed,
      to_from_color: "#b58b2f",
      to_from_box_bg: texYellow,
      menu_bg: texWhite,
      menu_bg_highlight: texOrange,
      topbar_bg: "#4477bb",
      topbar_border: "darkblue",
      neutral_color: "#3333dd",
      neutral_box_color: "#bbbbff"
    };