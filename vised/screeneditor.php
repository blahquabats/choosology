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
.screeneditor-maintext
{
    width:90%;
    display: inline-block;
    margin-left:50px;
    margin-right:auto;
    cursor: text;
    border-radius: 4px;
    border: 1px solid transparent;
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
        echo "<div class='choicecontainer'> 
            <div class='choicetext' id='choice$c'>".$choice_label."</div>
            <div class='choiceinfo'>Leads to \"".strip_tags($allscreens[$choice[1]]['screenname'])."\"</div>
        <input type='hidden' id = 'choice".$c."id' value='".$choice[1]."'>
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
    height: 440,
    resize: true,
    plugins: "lists link image table code wordcount autoresize",
    toolbar: "undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | outdent indent | link image table | removeformat code",
    menubar: "edit view insert format tools table help"
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
