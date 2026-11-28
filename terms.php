<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/lib/legal-pages.php';
?>
<div class="about-page">
    <article class="home-copy-card home-copy-card--intro legal-copy">
        <h2 class="home-copy-heading">Terms and Conditions</h2>
        <?php echo choosology_terms_body_html(); ?>
    </article>
</div>
