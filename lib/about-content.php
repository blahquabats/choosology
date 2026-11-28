<?php
/**
 * Shared About copy for Classic and Lite.
 * Add a FAQ entry by appending one question and answer to choosology_about_faq().
 */

function choosology_about_lead(): string
{
	return 'An author builds a screen for each moment in the visual editor, connects that screen to what can happen next, and a reader plays the graph by choosing.';
}

/**
 * @return list<array{src:string,alt:string,caption:string}>
 */
function choosology_about_shots(): array
{
	return array(
		array(
			'src' => 'images/about/editor.png',
			'alt' => 'Visual editor graph with The alley connected to The ink room and The side street',
			'caption' => 'Build a branching narrative in the visual editor',
		),
		array(
			'src' => 'images/about/play.png',
			'alt' => 'The Last Lantern as a reader sees it, over the alley, with choices to follow the lantern or turn down the side street',
			'caption' => 'Readers explore your narrative with the style and atmosphere you\'ve chosen.',
		),
	);
}

function choosology_about_running(): array
{
	return array(
		'After building the experiment, make it available privately or publicly.',
		'You can choose to be notified when readers interact with your experiment and view usage data and feedback.',
		'Invite other users to collaborate on adventures and broaden your offerings.',
	);
}

/**
 * Reader-side notes for the Experiencing Experiments section.
 *
 * @return list<string>
 */
function choosology_about_experience(): array
{
	return array(
		'Browsing users can find recent or popular experiments to enjoy.',
		'Readers can rate and comment on experiments.',
		'Activity unlocks Degrees and builds up DataScrip, which lets users customize their Office.',
	);
}

function choosology_about_experience_excerpt_html(string $line): string
{
	$needle = 'DataScrip';
	$at = strpos($line, $needle);
	if ($at === false || !function_exists('choosology_datascrip_sign_html')) {
		return htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
	}
	$before = htmlspecialchars(substr($line, 0, $at), ENT_QUOTES, 'UTF-8');
	$after = htmlspecialchars(substr($line, $at + strlen($needle)), ENT_QUOTES, 'UTF-8');
	return $before . choosology_datascrip_sign_html() . $needle . $after;
}

function choosology_about_faq_answer_html(string $answer, string $termsHref = '#/terms'): string
{
	$phrase = 'Choose Your Own Adventure';
	$safe = htmlspecialchars($answer, ENT_QUOTES, 'UTF-8');
	$link = '<a href="https://www.cyoa.com/">' . $phrase . '</a>';
	$safe = str_replace($phrase, $link, $safe);
	$terms = 'See our Terms and Conditions for more details.';
	if ($termsHref !== '' && strpos($safe, $terms) !== false) {
		$href = htmlspecialchars($termsHref, ENT_QUOTES, 'UTF-8');
		$safe = str_replace($terms, '<a href="' . $href . '">' . $terms . '</a>', $safe);
	}
	return $safe;
}

function choosology_about_faq_heading(): string
{
	return 'FAQ (Frequently Assumed Questions)';
}

/**
 * @return list<array{q:string,a:string}>
 */
function choosology_about_faq(): array
{
	return array(
		array(
			'q' => 'What is Choosology?',
			'a' => 'Choosology is a tool for authors to build interactive stories, or "gamebooks", similar to the classic "Choose Your Own Adventure" series. With an intuitive interface for writing, styling, and organizing adventures, or "experiments," Choosology is meant to unlock your inner world-builder, fanfic-writer, or flowchart-developer. Do it for yourself, share it with your friends, or share it with the world. Choosology is not affiliated with ChooseCo.',
		),
		array(
			'q' => 'What does it cost?',
			'a' => 'Nothing. Choosology is now and will always be completely free.',
		),
		array(
			'q' => 'Then how do you make money?',
			'a' => 'I don\'t. There are no ads, no selling data, no industry collabs, no premium subscriptions.',
		),
		array(
			'q' => 'Can I write adult material?',
			'a' => 'Yes, to the extent permissible by law. However, content warnings are required and users need to log in and opt in to find such content without a direct link.',
		),
		array(
			'q' => 'What is your policy on generative AI?',
			'a' => 'While AI tools were helpful in building this iteration of Choosology, the art and text are human-generated. We encourage experiments and imagery to also be human-generated--this is meant to be conducive to creativity, and it\'s okay to be a little rough around the edges. Content deemed "AI slop" may be de-published in egregious cases, but otherwise the reading community will determine whether they are interested in content made with AI assistance. Choosology itself will not integrate with AI tools for text or image generation.',
		),
		array(
			'q' => 'What is your policy on using the intellectual property (IP) of others?',
			'a' => 'Fan fiction has long been an important and sometimes very high-quality art form, and I am not interested in suppressing it. Choosology is a creative, noncommercial platform with little likelihood of being mistaken for official tie-ins, so fair use applies in most cases. Choosology will only remove content which clearly violates the tenets of fair use. See our Terms and Conditions for more details.',
		),
	);
}
