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
    var texPurple = new Image();
    texPurple.src = '/images/tex-purple.png';
    var texRed = new Image();
    texRed.src = '/images/tex-red.png';
    var texWhite = new Image();
    texWhite.src = '/images/tex-white.png';
    var texGreen = new Image();
    texGreen.src = '/images/tex-green.png';
    var texTeal = new Image();
    texTeal.src = '/images/tex-teal.png';
    var texYellow = new Image();
    texYellow.src = '/images/tex-yellow.png';
    var texOrange = new Image();
    texOrange.src = '/images/tex-orange.png';
    var config = {
      std_color: "#000000",
      box_color: "#cdcdff",
      box_bg: texPurple,
      box_color_highlight: "#ddddff",
      box_bg_highlight: texWhite,
      box_border: "#7777cc",
      box_border_highlight: "#aaaaff",
      to_color: "#33dd33",
      from_color: "#ff6666",
      to_box_color: "#bbffbb",
      to_box_bg: texGreen,
      from_box_color: "#ffcdcd",
      from_box_bg: texRed,
      to_from_color: "#bbbb77",
      to_from_box_bg: texYellow,
      menu_bg: texWhite,
      menu_bg_highlight: texOrange,
      topbar_bg: "#4477bb",
      topbar_border: "darkblue",
      neutral_color: "#3333dd",
      neutral_box_color: "#bbbbff"
    };