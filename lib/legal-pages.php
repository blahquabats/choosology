<?php
/**
 * Terms and privacy wording shared by Classic and Lite.
 * Edited from the live Choosology pages. Both surfaces call these functions.
 */

function choosology_terms_body_html(): string
{
	$items = array(
		'Lawful adult material is allowed. An experiment with adult material needs a content warning, and readers see it only after they log in and opt in. Users choose what they read. Otherwise Choosology is as permissive as the law allows. Illegal content is not allowed. The administrator may remove illegal content or content that is missing a required warning.',
		'<b>Sexual content involving minors.</b> Sexual content involving anyone under 18 is prohibited, including photographs, drawings, and other depictions that sexually exploit a minor. That material will be removed and reported as required by law.',
		'An author can mark an experiment private so it is not listed and is shared only with people they give access, which limits ordinary dissemination. Marking it private does not hide it from legal process or a valid complaint, and it does not protect the author if the material breaks the law. The submitting user is responsible for any consequences, legal or otherwise, of the material they upload to the site.',
		'In comments, excessive obscenity, harassment ("trolling"), spam, or malicious actions are strictly prohibited and may result in suspension of registration, at the discretion of site administration.',
		'All user-submitted material must be a legitimate use of the material. In general, that is to say, it is <b>original</b> material, <b>short excerpts</b> of copyrighted material with proper attribution, obvious <b>parody or satire</b>, or used with <b>permission</b> of the content\'s originator.',
		'<b>Copyright takedown.</b> Notices go to admin@choosology.com and should include a signature, the copyrighted work, the URL of the material, the sender\'s contact information, a good-faith statement that the use is not authorized, and a statement under penalty of perjury that the notice is accurate and the sender is the owner or authorized to act. A counter-notice can be sent to the same address. Repeat infringers may lose their accounts.',
		'All legitimate user-submitted material is the sole intellectual and creative property of that user. There is virtually no way to easily enforce a copyright over a particular image or block of text once it has been shared. Consider using "watermarks" on images.',
		'If any material on this site appears to be in violation of these terms or an applicable law, any user may send an email to admin@choosology.com. We guarantee a response within 48 hours, and probably within 12. We\'d offer you your money back if the guarantee is not met, but the site is free anyway.',
		'<b>Liability.</b> The site is provided as is. To the fullest extent California law allows, Choosology is not liable for user-submitted material or for indirect or consequential damages arising from use of the site. This does not limit any responsibility California law does not allow to be limited.',
		'<b>Governing law.</b> These terms are governed by California law. Disputes are brought in the state or federal courts located in California.',
		'<b>Licenses.</b> By default an experiment is all rights reserved to its author. The author grants Choosology a non-exclusive license to host, display, and back up the work at the availability the author chose. An author may also apply a Creative Commons license or CC0. Other people may use the work only as that license, or the author\'s permission, allows.',
	);
	$html = '<ol class="legal-list">';
	foreach ($items as $item) {
		$html .= '<li>' . $item . '</li>';
	}
	$html .= '</ol>';
	return $html;
}

function choosology_privacy_body_html(string $termsHref): string
{
	$href = htmlspecialchars($termsHref, ENT_QUOTES, 'UTF-8');
	$parts = array(
		'Choosology respects the privacy of its customers and visitors. If you wish to be removed from our database, or have any questions about the operation of our website, please write to admin@choosology.com.',
		'<b>Personal Data:</b> Our website collects names, email addresses, and other personal information from customers and visitors only when voluntarily submitted via one of our online forms, such as our registration form. This data is used by Choosology to run accounts and reset passwords. Choosology does not sell, rent, or package personal information to marketers or data brokers. Information is shared only with service providers who run the site, including the web host DreamHost, when the law requires it, to protect the site or its users, or when the user asks. Customers can edit their personal information from My Office, then profile. A user can remove their profile by re-entering their password. This cannot be undone. It removes the account, login, uploaded images, ratings, and experiments from being findable. Their comments stay visible as "[deleted]" by "[deleted user]". An unfindable placeholder remains so other people\'s activity, and later messages, still have a target. Registrants can also un-register by writing to the email address above.',
		'<b>Users Under 13:</b> Users under the age of 13 may not register without parental consent. Parents who wish to give consent should be aware that this site contains user-submitted content which may be inappropriate. While under-13 users may not view adventures marked as "Restricted," there is no guarantee that this measure will hide all objectionable material. To give consent, a parent or guardian should send an email to admin@choosology.com with the username and email address desired for the account. The account will be marked as an "under-13" account, limiting the user\'s ability to see Restricted adventures. Allow up to two days for account creation. Once created, parents or guardians may request for the account to be deleted at any time. If the user turns 13, the parent or guardian may request to remove the "under-13" marker from the account.',
		'<b>User-Submitted Content:</b> Due to the nature of the site, Choosology stores user-submitted creative content. We make no claim of ownership or editorial rights over this material, <b>except</b> the right to remove or hide material insofar as it violates the <a href="' . $href . '">Terms and Conditions</a> agreed to when registering. An author can mark an experiment private so it is not listed and is shared only with people they give access, which limits ordinary dissemination. Marking it private does not hide it from legal process or a valid complaint, and it does not protect the author if the material breaks the law. The user is responsible for legal trouble, personal issues, book deals, or any other consequences, positive or negative, arising from the material\'s publication on this site, private or not.',
		'<b>Non-Personal Data:</b> Our website also collects non-personal data through the use of server-based "session variables," which we use to achieve important site functionality, including the recognition of logged-in users and the temporary access of visitors to private experiments. In order to identify users as they proceed through the site, our website also uses "cookies" (small files stored by your browser on your hard drive); thus, logging in or reading a private experiment requires a browser that accepts "cookies."',
		'Our web server also records IP addresses from all visitors to our site. An IP address is a number that is automatically assigned to your computer when you use the Internet. An IP address can be connected to an account when the person is logged in, and is also used to operate and troubleshoot the site.',
		'By using Choosology, you signify your agreement to our privacy policy as stated above. Note that this policy may be revised periodically, though this will always be noted on the front page as an update or news item. Please re-read this policy prior to submitting any personal information if you have concerns about how your information is being collected and used.',
	);
	$html = '';
	foreach ($parts as $part) {
		$html .= '<p>' . $part . '</p>';
	}
	return $html;
}
