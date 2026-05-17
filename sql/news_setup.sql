-- Dev seed rows for `news` (DDL: choosology-schema.sql — headline, body, `by`, whenposted).
-- Run only on an empty or disposable database.

INSERT INTO news (headline, body, `by`, whenposted) VALUES
(
  'Welcome to the lab notes',
  '<p>This is the <strong>News</strong> tab — site updates, release notes, and announcements will live here.</p><p>Open items in the list to read the full note.</p>',
  'Choosology Labs',
  NOW()
),
(
  'Try the experiment catalog',
  '<p>Head to <strong>Browse</strong> to see public experiments, ratings, and quick search.</p>',
  'Lab notes',
  DATE_SUB(NOW(), INTERVAL 3 DAY)
),
(
  'Sign in for your workstation',
  '<p>Choosologists can use <strong>My Stuff</strong> after signing in from the header.</p>',
  'Lab notes',
  DATE_SUB(NOW(), INTERVAL 10 DAY)
);
