<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/lib/legal-pages.php';
choosology_lite_header(array('title' => 'Privacy Policy', 'active' => 'home'));
?>
<fieldset class="lite-panel legal-copy">
	<legend>Privacy Policy</legend>
	<?php echo choosology_privacy_body_html(choosology_lite_url('terms.php')); ?>
</fieldset>
<?php
choosology_lite_footer();
