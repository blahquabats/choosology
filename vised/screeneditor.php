<?php
require_once("../connect.php");
require_once("../auxfuncs.php");
$sid = $_GET['screenid'];
$res = runquery_assoc("Select * from advscreens where id = '$sid'");
if(!$res) echo "<div class='error'>Screen could not be found!</div>";
$screen = $res[0];
$advid = $screen['advused'];
$advres = runquery_assoc("Select advs.bg as advbg, advscreens.id as screenid, advscreens.name as screenname from advs join advscreens on advscreens.advused=advs.id where advs.id = '$advid'");
$allscreens = array();
foreach ($advres as $v)
{
    $allscreens[$v['screenid']] = $v;
}
$adv = $advres[0];
// Match ajax/screenajax.php: DB may store entity-encoded HTML (once or twice from legacy saves).
$editor_body_html = htmlspecialchars_decode(htmlspecialchars_decode((string)($screen['text'] ?? '')));
$editor_screen_name = htmlspecialchars_decode(htmlspecialchars_decode((string)($screen['name'] ?? '')));
//echoPre($advres);
echo "<style>
#editscreenwindow
{
    background-color: ".preg_replace('/\s+/', ' ', (string)($adv['advbg'] ?? '')).";
}
#editscreenwindow.editscreenopen
{
    overflow-x: hidden;
    overflow-y: auto;
    box-sizing: border-box;
}
.screeneditor-maintext
{
    width: 90%;
    max-width: 100%;
    display: inline-block;
    margin-left: 50px;
    margin-right: auto;
    cursor: text;
    border-radius: 4px;
    border: 1px solid transparent;
    vertical-align: top;
    box-sizing: border-box;
}
.screeneditor-maintext .tox-tinymce
{
    box-sizing: border-box;
    max-width: 100%;
}
.screeneditor-name
{
    width:30%;
    display: inline-block;
    margin: 50px 0 10px 5px;
    padding: 2px;
    cursor: text;
    border-radius: 4px;
    border: 1px solid #aaaaaa;
}

.screeneditor-name:focus
{
    background-color: #eeeeff;
    border-color: #666666;
    
}
.screeneditor-label
{
    display:inline-block;
    clear:left;
    margin-left: 50px;
    width:140px;
    font-weight: bold;
    text-align: right;
    overflow: visible;
    white-space: nowrap;
}
.choicecontainer
{
    width:22%;
    display: inline-block;
}
.choicetext
{
    margin: 10px 0 10px 5px;
    padding: 5px;
    cursor: text;
    border-radius: 4px;
    border: 1px solid #aaaaaa;   
    text-align: center;
}
.choiceinfo
{
font-size:small;
    text-align:center;
}
.choicetext:focus
{
    background-color: #eeeeff;
    border-color: #666666;
    
}

#savetime
{
    clear:left;
    font-size: small;
}
.choosology-imglib-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 100050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    box-sizing: border-box;
}
.choosology-imglib-panel {
    background: #fff;
    color: #222;
    border-radius: 8px;
    max-width: 920px;
    width: 100%;
    max-height: 85vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 32px rgba(0,0,0,0.35);
}
.choosology-imglib-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid #ccc;
    font-weight: bold;
}
.choosology-imglib-close {
    cursor: pointer;
    font-size: 22px;
    line-height: 1;
    padding: 4px 8px;
}
.choosology-imglib-close:hover { color: #c00; }
.choosology-imglib-grid {
    padding: 12px;
    overflow-y: auto;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-content: flex-start;
}
.choosology-imglib-tile {
    width: 100px;
    cursor: pointer;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 4px;
    background: #fafafa;
}
.choosology-imglib-tile:hover {
    border-color: #4488cc;
    background: #eef6ff;
}
.choosology-imglib-tile img {
    max-width: 92px;
    max-height: 92px;
    display: block;
    margin: 0 auto 4px;
    object-fit: contain;
}
.choosology-imglib-caption {
    font-size: 11px;
    line-height: 1.2;
    word-break: break-word;
    max-height: 2.4em;
    overflow: hidden;
}
.choosology-imglib-msg {
    padding: 20px;
    color: #555;
}

</style>";
/*

   .choicetext {
        white-space: nowrap;
        width:200px;
        overflow: hidden;
    } 
    .choicetext br {
        display:none;

    }
    .choicetext * {
        display:inline;
        white-space:nowrap;
    }

*/
echo "
<div class='ajaxloader' id='screenal'></div>
<div class='closewindow' title='Close Screen Editor'>X</div>
<div id='editorcontents' style='display:none'>
<div class='screeneditor-label'>Label:</div>
<div class='screeneditor-name' id='screenlabel' contentEditable='true'>
";

echo $editor_screen_name;
echo "</div>
<br/>
<div class='screeneditor-label'>Page Contents:</div>
<div class='screeneditor-maintext'>
<textarea id='bodytext'>";
echo $editor_body_html."
</textarea>

<div class='screeneditor-label'>Connected Screens:</div><br/>";

for($c = 1; $c <= 8; $c++)
{
    if($screen["choice$c"] != "")
    {
        $choice = explode("|Q-D-|", $screen["choice$c"]);
        $choice_label = htmlspecialchars_decode(htmlspecialchars_decode((string)($choice[0] ?? '')));
        $targetId = isset($choice[1]) ? (string) $choice[1] : '';
        $targetMeta = ($targetId !== '' && isset($allscreens[$targetId])) ? $allscreens[$targetId] : null;
        if ($targetMeta) {
            $destLabel = strip_tags((string) ($targetMeta['screenname'] ?? ''));
        } else {
            $destLabel = $targetId !== '' ? ('(missing screen #' . $targetId . ')') : '(not connected)';
        }
        echo "<div class='choicecontainer'> 
            <div class='choicetext' id='choice$c'>".$choice_label."</div>
            <div class='choiceinfo'>Leads to \"".htmlspecialchars($destLabel, ENT_QUOTES, 'UTF-8')."\"</div>
        <input type='hidden' id = 'choice".$c."id' value='".htmlspecialchars($targetId, ENT_QUOTES, 'UTF-8')."'>
        </div>
        
        ";
    }
}

echo "<br/><div class='fakebutton fgreen' id = 'savescreen' >Save</div>
<div id='savetime'>Last Saved: ".nicedatetime($screen['edited'])."</div>


<input type='hidden' id='screenid' value='$sid' />
<input type='hidden' id='advid' value='$advid' />
";

// choices
// all in one? assign choices externally?
// or just another eight possibilities (or however many connections there are)
//(probably sitll eight but grey out the ones that don't show up yet)
// still rather have a little paragraph where words can be selected as the links
?>

</div>
</div>


<script>
(function () {
function alertready()
{
 $("#editorcontents").css("display", "block");  
 $("#screenal").hide();
}

function updateSaveTime()
{
    var nicedate = nicedatetime(new Date());
    $("#savetime").html("Last Saved: "+nicedate);
}

function choosologyUrlSafe(path) {
    if (typeof choosologyUrl === "function") {
        return choosologyUrl(path);
    }
    path = String(path || "").replace(/^\//, "");
    return path ? ("/" + path) : "/";
}

function choosologyOpenImageLibrary(callback) {
    var advid = $("#advid").val();
    if (!advid) {
        if (typeof showAlert === "function") {
            showAlert("Missing experiment id for image list.", "error");
        }
        return;
    }
    var $ov = $("<div class=\"choosology-imglib-overlay\" role=\"dialog\" aria-modal=\"true\">" +
        "<div class=\"choosology-imglib-panel\">" +
        "<div class=\"choosology-imglib-head\"><span>Your Choosology images</span>" +
        "<span class=\"choosology-imglib-close\" title=\"Close\">&times;</span></div>" +
        "<div class=\"choosology-imglib-body choosology-imglib-msg\">Loading…</div></div></div>");
    $("body").append($ov);

    function close() {
        $ov.remove();
        $(document).off("keydown.choosology-imglib");
    }
    $ov.on("click", function (e) {
        if (e.target === $ov[0]) {
            close();
        }
    });
    $ov.find(".choosology-imglib-close").on("click", close);
    $(document).on("keydown.choosology-imglib", function (e) {
        if (e.keyCode === 27) {
            close();
        }
    });

    var $body = $ov.find(".choosology-imglib-body");
    $.getJSON(choosologyUrlSafe("ajax/listuserpics.php"), { advid: advid })
        .done(function (data) {
            if (!data || !data.ok) {
                $body.removeClass("choosology-imglib-grid").addClass("choosology-imglib-msg").text(
                    (data && data.error) ? data.error : "Could not load images."
                );
                return;
            }
            var items = data.items || [];
            $body.removeClass("choosology-imglib-msg").addClass("choosology-imglib-grid").empty();
            if (!items.length) {
                $body.append($("<p/>").css({ width: "100%", textAlign: "center", color: "#666", margin: "12px 0" })
                    .text("No uploaded images yet. Add pictures elsewhere on the site, then reopen this picker."));
                return;
            }
            items.forEach(function (it) {
                var url = it.imageUrl || "";
                var title = it.title || ("Image #" + it.id);
                var thumb = it.thumbUrl || url;
                var $t = $("<div class=\"choosology-imglib-tile\"/>");
                $t.append($("<img/>").attr({ src: thumb, alt: title }));
                $t.append($("<div class=\"choosology-imglib-caption\"/>").text(title));
                $t.on("click", function () {
                    if (typeof callback === "function") {
                        callback(url, { alt: title });
                    }
                    close();
                });
                $body.append($t);
            });
        })
        .fail(function () {
            $body.removeClass("choosology-imglib-grid").addClass("choosology-imglib-msg")
                .text("Network error loading your images.");
            if (typeof showAlert === "function") {
                showAlert("Could not load image library.", "error");
            }
        });
}

if (typeof tinymce === "undefined") {
    alertready();
    return;
}

var advBg = <?php echo json_encode(preg_replace('/\s+/', ' ', (string)($adv['advbg'] ?? '')), JSON_UNESCAPED_UNICODE); ?>;
var contentStyle = advBg ? ("body { background-color: " + advBg + "; margin: 0; }") : "body { margin: 0; }";

var baseInit = {
    license_key: "gpl",
    promotion: false,
    branding: false,
    content_style: contentStyle,
    base_url: (typeof window.__choosologyTinyMceBaseUrl === "string" && window.__choosologyTinyMceBaseUrl)
        ? window.__choosologyTinyMceBaseUrl
        : "https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.1",
    suffix: ".min"
};

var bodyPromise = tinymce.init(Object.assign({}, baseInit, {
    selector: "#bodytext",
    height: 360,
    max_height: 520,
    resize: false,
    plugins: "lists link image table code wordcount",
    toolbar: "undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | outdent indent | link image table | removeformat code",
    menubar: "edit view insert format tools table help",
    file_picker_callback: function (cb, value, meta) {
        if (meta.filetype === "image") {
            choosologyOpenImageLibrary(cb);
        }
    }
}));

var choicePromise = tinymce.init(Object.assign({}, baseInit, {
    selector: ".choicetext",
    inline: true,
    plugins: "lists link wordcount",
    toolbar: "bold italic underline | removeformat",
    menubar: false,
    setup: function (editor) {
        editor.on("keydown", function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
            }
        });
    }
}));

Promise.all([bodyPromise, choicePromise]).then(function () {
    alertready();
}).catch(function (err) {
    if (window.console) console.error(err);
    alertready();
});

$(document).ready(function(){
    $(".closewindow").on("click", function(){
       closeScreenEditor("<?php echo $sid ?>"); 
    });
    
    $("#savescreen").on("click", function(){
        var err = 0;
        $(this).removeClass("fgreen");
        var sid = $("#screenid").val();
        var screenlabel = $("#screenlabel").text();
        var ed = tinymce.get("bodytext");
        var content = ed ? ed.getContent() : $("#bodytext").val();
        var temparray = {};
        
        temparray["sid"] = sid;
        temparray["content"] = content;
        temparray["screenlabel"] = screenlabel;
        
        $(".choicetext").each(function(){
            var cid = $(this).attr("id");
            var choiceEd = tinymce.get(cid);
            var ct = choiceEd ? choiceEd.getContent() : $(this).html();
            if(!$(ct).text().trim()) 
            {
                err = "Choice labels cannot be empty!";
                return false;
            }
            var cpointer = $("#"+cid+"id").val();
            
            temparray[cid] = ct;
            temparray[cid+"id"] = cpointer;
            
        });
        if(err) 
        {
            alert(err);
            $("#savescreen").addClass("fgreen");
            return false;
        }
        
        
         $.ajax({
        type: "POST",
        url: "vised/saveadvscreen.php",
        data: temparray,
        }).error(function(){
            showAlert("error", "error");
            $("#savescreen").addClass("fgreen");
        }).done(function(response){
            $("#savescreen").addClass("fgreen");
             updateSaveTime();
             loadAdv("<?php echo $advid ?>");
        });
    });
    
});
})();
</script>
