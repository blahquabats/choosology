<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/lib/legal-pages.php';
choosology_lite_header(array('title' => 'Terms and Conditions', 'active' => 'home'));
?>
<fieldset class="lite-panel legal-copy">
	<legend>Terms and Conditions</legend>
	<?php echo choosology_terms_body_html(); ?>
</fieldset>
<?php
choosology_lite_footer();
