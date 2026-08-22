<?php
    require_once("./connect.php");
    require_once("./auxfuncs.php");
    /* Web path to app root (empty when installed at domain root, e.g. "/choosology" in a subfolder). */
    $choosology_web_base = '';
    if (!empty($_SERVER['SCRIPT_NAME'])) {
        $sd = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($sd !== '/' && $sd !== '.' && $sd !== '') {
            $choosology_web_base = rtrim($sd, '/');
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php
    
   // require_once("./authent.php");
    /*
     * <div class='chalkmenu'>
    <a class='chalkoption blue' onclick="return showMenuOption('home');" >HomE</a><br>
    <a class='chalkoption orange' onclick="return showMenuOption('news');" >News</a><br>
    <a class='chalkoption red' onclick="return showMenuOption('browse')" >ExperimeNts</a><br>
    <a class='chalkoption green' onclick="return showMenuOption('mystuff')" >My Stuff</a>
</div>

<script src="scripts/jcanvas.js"></script>
     */
    ?>
    <meta charset="utf-8" />
    <title>Choosology</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-ui@1.10.4/themes/base/jquery-ui.css" />
    <link rel="stylesheet" href="style/choosology.css" />
    <link rel="stylesheet" href="style/jquery.minicolors.css" />
    <script src="scripts/jquery.js"></script>
    <script>
    window.CHOOSOLOGY_BASE = <?php echo json_encode($choosology_web_base, JSON_UNESCAPED_SLASHES); ?>;
    function choosologyUrl(path) {
        path = String(path || '').replace(/^\//, '');
        var b = typeof window.CHOOSOLOGY_BASE === 'string' ? window.CHOOSOLOGY_BASE : '';
        if (!path) {
            return b ? (b + '/') : '/';
        }
        if (!b) {
            return '/' + path;
        }
        return b + '/' + path;
    }
    </script>
    <!-- jquery-ui: use code.jquery.com JS (browser IIFE). jsDelivr npm build uses require("jquery") and breaks without a bundler. -->
    <script src="https://code.jquery.com/ui/1.10.4/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.cycle/3.0.3/jquery.cycle.all.min.js"></script>
    <script src="scripts/jquery-dateFormat.js"></script>
    <script src="scripts/jquery-minicolors/jquery.minicolors.min.js"></script>
    <script src="scripts/choosology.js"></script>
    <!-- Konva 10.3.0 UMD (official npm konva.min.js). jsDelivr mirrors npm; cdnjs /ajax/libs/konva/10.x/konva.min.js was 404 when checked — swap to cdnjs when they publish this version. -->
    <script src="https://cdn.jsdelivr.net/npm/konva@10.3.0/konva.min.js" crossorigin="anonymous"></script>
    <script src="scripts/sammy.js"></script>
    
    <script src='scripts/index.js'></script>
    <script src='scripts/routes.js'></script>


</head>
<body<?php echo !empty($_SESSION['user']) ? ' data-logged-in="1"' : ''; ?>>
<div class='fakebackground' id = 'fakebackground'>
<img src='images/bg_default.jpg' id='bg-blue' />
<img src='images/bg_green.jpg' id='bg-green' />
<img src='images/bg_red.jpg' id='bg-red' />
<img src='images/bg_orange.jpg' id='bg-orange' />

</div>
<div class='fakebackground' id = 'backgrounddark'>

</div>

<div class='navbutton red navdisabled' id='home_nav' onclick="location.href='#/home'">
    <?php echo icon("world", "64px"); ?><br />
    Home
</div>
<div class='navbutton green navdisabled' id="news_nav" onclick="location.href='#/news'">
    <?php echo icon("news", "64px"); ?><br />
    News
</div>
<div class='navbutton purple navdisabled' id="browse_nav" onclick="location.href='#/search'">
    <?php echo icon("beaker", "64px"); ?><br />
    Browse
</div>
<?php if (!empty($_SESSION['user'])) { ?>
<div class='navbutton orange navdisabled' id="mystuff_nav" onclick="location.href='#/mystuff'" >
    <?php echo icon("person", "64px"); ?><br />
    My&nbsp;Stuff
</div>
<?php } ?>
<div class="header" style='cursor: pointer;' onclick="location.href='#/home'">
<img src="images/logo_horizontal_sm.png" />
</div>

<div class="header" id='contextlink'>
</div>
<div class="header" id='alertbox'>
</div>

<div class="header" id='topbox'>
    <?php
    if (empty($_SESSION['user']))
    {
        echo "User Name: <input type='text' name='logname' id='loginuser' autocomplete='username' /><br />\n";
        echo "Password: <input type='password' name='logpass' id='loginpass' autocomplete='current-password' /><br />\n";
        echo "<div class='rememberme'><input type='checkbox' name='rememberlogin' id='rememberlogin'> <label for='rememberlogin'>Remember me?</label></div>";
        echo "<button type='button' id='loginsubmit'>Submit</button>";
        echo "<div class='login-signup-row'><button type='button' class='login-signup-link' id='opensignup'>Apply for lab access</button></div>";
    }
    else
    {
        echo "Logged in as ".$_SESSION['user'];
        echo "<br/> <span id='logoutsubmit'> <a href='#'> log out </a> </span>";
    }
    ?>
</div>
<div class='contentcontainer'>
<?php if (!empty($_SESSION['user'])) { ?>
<div id="tabswindow">
    <ul>
        
        <li><a href="mystuff/experiments.php" id='mystuff-experiments' class='tabsa' data-loc='experiments'>Experiments</a></li>
        <li><a href="mystuff/office.php" id='mystuff-office' class='tabsa' data-loc='office'>My Office</a></li>
        <li><a href="mystuff/messages.php" id='mystuff-messages' class='tabsa' data-loc='messages'>Messages (1)</a></li>
        <li><a href="mystuff/resources.php" id='mystuff-resources' class='tabsa' data-loc='resources'>Resources</a></li>
        <li><a href="mystuff/degrees.php" id='mystuff-degrees' class='tabsa' data-loc='degrees'>Degrees</a></li>
        <li><a href="mystuff/account.php" id='mystuff-account' class='tabsa' data-loc='account'>My Information</a></li>
    </ul>
</div>
<?php } ?>
<div class='genericwindow' id="homewindow">
    <div class='ajaxloader'></div>
</div>
<div class='genericwindow' id="newswindow">

    <div class='ajaxloader'></div>
</div>
<div class='genericwindow' id="browsewindow">
    <div class='ajaxloader'></div>
</div>

<div class='genericwindow' id="viewwindow">
    <div class='ajaxloader'></div>
</div>
<div class='genericwindow' id="editadvwindow">
    <div class='ajaxloader'></div>
</div>
<div class='editscreenclosed' id="editscreenwindow">
    <div class='ajaxloader' id='screenal'></div>
    <div id = 'editscreenwindowcontents'></div>
</div>

</div>

<?php /* Signup intake modal: kept in DOM even when logged in so logout → apply still works. */ ?>
<div id="signupmodal" class="signup-modal signup-modal--hidden" aria-hidden="true">
	<div class="signup-modal-backdrop" tabindex="-1"></div>
	<div class="signup-modal-panel" role="dialog" aria-modal="true" aria-labelledby="signupmodal_title">
		<div class="signup-modal-header">
			<div class="signup-modal-heading">
				<p class="signup-modal-eyebrow">Personnel intake <span class="signup-modal-eyebrow-tag">new researcher</span></p>
				<h2 class="signup-modal-title" id="signupmodal_title">Lab application</h2>
			</div>
			<button type="button" class="signup-modal-x" id="signupmodal_close" aria-label="Close">&times;</button>
		</div>
		<form id="signup-form" class="signup-form" action="ajax/signup.php" method="post" novalidate>
			<p class="signup-lede">Submit an application for Choosology Lab access. Approved applicants receive access immediately.</p>
			<p class="signup-privacy"><strong>Email policy:</strong> we will never send mail you do not ask for.</p>

			<label class="signup-label" for="signup-name">Lab handle</label>
			<input type="text" id="signup-name" name="name" class="signup-input" maxlength="45" autocomplete="username" required>

			<label class="signup-label" for="signup-email">Contact address</label>
			<input type="email" id="signup-email" name="email" class="signup-input" maxlength="45" autocomplete="email" required>

			<label class="signup-label" for="signup-pass1">Password</label>
			<input type="password" id="signup-pass1" name="pass1" class="signup-input" maxlength="72" autocomplete="new-password" required>

			<label class="signup-label" for="signup-pass2">Confirm password</label>
			<input type="password" id="signup-pass2" name="pass2" class="signup-input" maxlength="72" autocomplete="new-password" required>

			<label class="signup-label" for="signup-human" id="signup-human-label">Human verification</label>
			<input type="text" id="signup-human" name="human_check" class="signup-input" inputmode="numeric" autocomplete="off" required>

			<!-- Honeypot: leave blank -->
			<div class="signup-honeypot" aria-hidden="true">
				<label for="signup-fax">Lab fax</label>
				<input type="text" id="signup-fax" name="lab_fax" tabindex="-1" autocomplete="off">
			</div>

			<input type="hidden" id="signup-nonce" name="nonce" value="">

			<label class="signup-check">
				<input type="checkbox" id="signup-welcome" name="welcome_email" checked>
				<span>Send a single confirmation to this address.</span>
			</label>

			<label class="signup-check">
				<input type="checkbox" id="signup-newsletter" name="newsletter">
				<span>Send periodic Lab bulletins (about 1–3 per month) with application updates and recent adventures I might like.</span>
			</label>

			<div class="signup-actions">
				<button type="submit" id="signup-submit">File application</button>
				<button type="button" id="signup-cancel" class="signup-cancel">Cancel</button>
				<span id="signup-status" class="signup-status" aria-live="polite"></span>
			</div>
		</form>
	</div>
</div>

<div class='footer' id='footer'>
    Copyright &copy; 2015&ndash;<?php echo date('Y'); ?> The Grasssmith &amp; Grasssmithery Worldwide
</div>

</body>
</html>