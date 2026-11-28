<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/lib/about-content.php';
require_once dirname(__DIR__) . '/lib/datascrip-helpers.php';

choosology_lite_header(array('title' => 'About', 'active' => 'home'));
?>
<div class="about-page">
<fieldset class="lite-panel about-inset">
	<legend id="about-intro-heading">How Choosology Works</legend>
	<p><?php echo htmlspecialchars(choosology_about_lead(), ENT_QUOTES, 'UTF-8'); ?></p>
</fieldset>
<div class="about-shots about-inset">
	<?php foreach (choosology_about_shots() as $shot) {
		$src = htmlspecialchars(choosology_site_url($shot['src']), ENT_QUOTES, 'UTF-8');
	?>
	<figure class="about-shot">
		<figcaption><?php echo htmlspecialchars($shot['caption'], ENT_QUOTES, 'UTF-8'); ?></figcaption>
		<img src="<?php echo $src; ?>" alt="<?php echo htmlspecialchars($shot['alt'], ENT_QUOTES, 'UTF-8'); ?>">
	</figure>
	<?php } ?>
</div>
<div class="about-pair about-inset">
<section class="browse-section browse-section--intro" aria-labelledby="about-experience-heading">
	<h2 class="browse-section-heading" id="about-experience-heading"><span class="browse-section-num" aria-hidden="true">02</span> Experiencing Experiments</h2>
	<div class="news-archive-results">
		<?php
		$expNum = 1;
		foreach (choosology_about_experience() as $i => $line) {
			$slice = $i === 0 ? 'browse' : ($i === 1 ? 'rating' : 'office');
		?>
		<article class="news-card">
			<span class="news-card-title"><?php echo sprintf('%02d', $expNum); ?></span>
			<span class="news-card-excerpt"><?php echo choosology_about_experience_excerpt_html($line); ?></span>
			<?php if ($slice === 'office') { ?>
			<div class="about-slice about-slice--empty" role="img" aria-label="Customized Office image is not available yet"></div>
			<?php } else {
				$sliceSrc = $slice === 'browse' ? 'images/about/browse-slice.png' : 'images/about/rating-slice.png';
				$sliceAlt = $slice === 'browse' ? 'Browse listing of experiments' : 'Star rating and comment count';
			?>
			<img class="about-slice" src="<?php echo htmlspecialchars(choosology_site_url($sliceSrc), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($sliceAlt, ENT_QUOTES, 'UTF-8'); ?>">
			<?php } ?>
		</article>
		<?php $expNum++; } ?>
	</div>
</section>
<section class="browse-section browse-section--intro" aria-labelledby="about-running-heading">
	<h2 class="browse-section-heading" id="about-running-heading"><span class="browse-section-num" aria-hidden="true">03</span> Running Experiments</h2>
	<div class="news-archive-results">
		<?php $stepNum = 1; foreach (choosology_about_running() as $step) { ?>
		<article class="news-card">
			<span class="news-card-title"><?php echo sprintf('%02d', $stepNum); ?></span>
			<span class="news-card-excerpt"><?php echo htmlspecialchars($step, ENT_QUOTES, 'UTF-8'); ?></span>
			<div class="about-slice about-slice--empty" role="img" aria-label="Screenshot not available yet"></div>
		</article>
		<?php $stepNum++; } ?>
	</div>
</section>
</div>
<section class="browse-section browse-section--intro about-inset" aria-labelledby="about-faq-heading">
	<h2 class="browse-section-heading" id="about-faq-heading"><span class="browse-section-num" aria-hidden="true">04</span> <?php echo htmlspecialchars(choosology_about_faq_heading(), ENT_QUOTES, 'UTF-8'); ?></h2>
	<div class="news-archive-results">
		<?php foreach (choosology_about_faq() as $item) { ?>
		<article class="news-card about-faq-card">
			<span class="news-card-title"><?php echo htmlspecialchars($item['q'], ENT_QUOTES, 'UTF-8'); ?></span>
			<span class="news-card-excerpt"><?php echo choosology_about_faq_answer_html($item['a'], choosology_lite_url('terms.php')); ?></span>
		</article>
		<?php } ?>
	</div>
</section>
</div>
<?php
choosology_lite_footer();
