-- Seed rows for `news` scraped from live choosology.com (CYOCYOA home Recent News).
-- DDL columns (choosology-schema.sql): id, headline, `text`, `by`, whenposted.
-- Live site only shows MM-DD; years below are inferred from content / chronology.
-- Safe to re-run: upserts by primary key id.

INSERT INTO news (id, headline, `text`, `by`, whenposted) VALUES
(64,
  'Oh well',
  '<p>All my hopes and dreams of resurrection came to very little, as it turns out. Too many other projects, too much going on.</p>
<p>I will continue to keep the site up and running as long as it''s not a giant cesspit of racism, sexism, antisemitism, or other toxic behavior.</p>
<p>I will occasionally continue to come back and moderate comments. I''m heartened to see stories are still being written, in multiple languages, multiple genres, ranging from stupid to impressive. I''m so glad that this site has been able to help some people channel creativity and tell a story that they might not otherwise have been able to tell.</p>
<p>Maybe I''ll be inspired to update in some form in the future. But for now... I''ll keep the lights on for ya.</p>
<p>Oh, and Black Lives Matter, dismantle capitalism, and don''t confuse political apathy with anything but the outstanding privilege of not paying attention.</p>',
  'The Grasssmith',
  '2020-06-03 12:00:00'
) ON DUPLICATE KEY UPDATE
  headline = VALUES(headline),
  `text` = VALUES(`text`),
  `by` = VALUES(`by`),
  whenposted = VALUES(whenposted);

INSERT INTO news (id, headline, `text`, `by`, whenposted) VALUES
(63,
  'Resurrection',
  '<p>Hello, all!</p>
<p>It has been a very long time since last I updated. That is only slightly changing today.</p>
<p>First off, progress will continue on Choosology; this is not a eulogy. However, it has been impossible to make time for development of late, as professional and personal responsibilities have only grown over the past couple of years. One key roadblock was a chronic, nagging digestive issue, which made it difficult to eat or drink, and therefore to focus on anything. This issue has been brought mostly under control, opening up a little more mental freedom to pursue non-essential tasks.</p>
<p>The baby will likely not stop being a major drain on time and energy, but there are at least other people that can take on that burden some of the time.</p>
<p>The point is, I don''t have anything new to show you, but I feel that it is now feasible to begin active work once more on Choosology. My commitment for this month is to come up with and begin to follow a new schedule that will reliably get me coding at least a few hours a week.</p>
<p>In the meantime, I did make one small change here and removed the forum, which was never particularly useful and only became more and more infested with spammers and robots. If anyone would like any of the data (in profiles or posts) from their user there, send an email to admin(at)cyocyoa(period)com and it can be retrieved.</p>
<p>Thanks for reading, and keep on writing!</p>',
  'The Grasssmith',
  '2018-02-05 12:00:00'
) ON DUPLICATE KEY UPDATE
  headline = VALUES(headline),
  `text` = VALUES(`text`),
  `by` = VALUES(`by`),
  whenposted = VALUES(whenposted);

INSERT INTO news (id, headline, `text`, `by`, whenposted) VALUES
(62,
  'Juuuuuuuuuly',
  '<p>Hello everyone, and happy belated Fourth of July, if that''s something relevant to you!</p>
<p>Not a great report this month; between illness, other work, travel, and a baby on the way, it''s been difficult to get any extra work done.</p>
<p>It''s likely that this slowdown will continue for at least a few more months. I will continue to work on Choosology when possible, and endeavor to continue updating once per month, but I can''t promise much more than that at the moment.</p>
<p>As far as progress this month: work is continuing on the "New Experiment" page, specifically on creating the image-picking widget. This will be necessary in other parts of the site as well, so it needs to be designed fairly well, and progress is steady on that front. With any luck, it will be a little more fancy than the current widget!</p>
<p>Time, she flies...</p>',
  'The Grasssmith',
  '2017-07-07 12:00:00'
) ON DUPLICATE KEY UPDATE
  headline = VALUES(headline),
  `text` = VALUES(`text`),
  `by` = VALUES(`by`),
  whenposted = VALUES(whenposted);

INSERT INTO news (id, headline, `text`, `by`, whenposted) VALUES
(61,
  'FORM NE-1 DRAFT PROPOSAL (WIP)',
  '<p>Just a quick check-in this month...</p>
<p>All''s well (busy... but well), and work is proceeding on the "New Experiment" window.</p>
<p>The work in progress can be seen below.</p>
<p><a href="http://i.imgur.com/XpcsiS9.png" target="_blank" rel="noopener">[image]</a></p>
<p>You''ll also notice some changes to the look of the tabs; this is still in progress (as everything, technically, is), but the old look had to go because it would not play well with different sizes of screen (like, for example, a tablet). I hope nobody is too disappointed!</p>',
  'The Grasssmith',
  '2017-06-02 12:00:00'
) ON DUPLICATE KEY UPDATE
  headline = VALUES(headline),
  `text` = VALUES(`text`),
  `by` = VALUES(`by`),
  whenposted = VALUES(whenposted);

INSERT INTO news (id, headline, `text`, `by`, whenposted) VALUES
(60,
  'Come What May',
  '<p>Happy May! As a busy life continues to swirl around me, work continues on Choosology. This month''s work accomplished the following:</p>
<p>- Fixing display issue on adventure slides on front page<br />
- Continuing to track down and fix issues resulting from switching between/reloading different screens<br />
- implementing a more responsive layout for the "My Stuff" tabs, to improve views on smaller screens<br />
- implementing the "Delete Adventure" functionality<br />
- And last, but certainly not least, adding new icons for both the main navigation and the My Stuff tabs:</p>
<p><a href="http://i.imgur.com/y9mTRLX.png" target="_blank" rel="noopener">[image]</a></p>
<p>In addition, I spent some time helping a few CYOCYOA users with their various login/adventure-saving issues.</p>
<p>I was not able to complete the adventure-creation part of the workflow. I suspect it will take longer than first thought, but it''s an extremely important part that I can''t afford to shortchange at this stage. However, I''m hoping to focus on it and finish it in the upcoming month.</p>
<p>In the meantime... Keep on choosin''!*</p>
<p>*NB: not actually a new slogan. please disregard.</p>',
  'The Grasssmith',
  '2017-05-03 12:00:00'
) ON DUPLICATE KEY UPDATE
  headline = VALUES(headline),
  `text` = VALUES(`text`),
  `by` = VALUES(`by`),
  whenposted = VALUES(whenposted);
