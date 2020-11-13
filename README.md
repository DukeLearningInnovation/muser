# Muser

Muser is a Drupal installation profile that allows you to quickly set up a site to connect undergraduate students with mentors (faculty, postdoctoral researchers, lab technicians/managers/research affiliates, and graduate students) that have projects with open research positions. Mentors can post their projects while students can browse and search for opportunities that interest them and apply directly through the site. Mentors then review applications online and accept or reject them.

## Blind Review

To help reduce unconscious bias in the initial review of applications, mentors do not see the names of the students applying when they first read their application-- they only see an essay written by the applicant. After mentors complete this initial review, they can view the full information (name, major, transcript, resume, etc.).

## Automated Emails

The Muser site can be configured to send out emails automatically to:

- Inform mentors that they can begin posting projects and let them know when the project-posting period is ending.
- Let mentors know when it's time to start reviewing applications and remind them to complete their reviews before the review period ends.
- Notify students when their applications have been accepted or rejected.

## Customizable Colors

Muser uses a custom theme that allows you to select one of various pre-set color schemes or to choose the exact colors to match your school's color palette.

## Cron jobs
Set up the following cron jobs (update paths as needed):
```
#
# Crontab entries for muser
#

# Check and set the current Round.
* * * * * cd /app/web && drush muser_system:set-current-round > /dev/null 2>&1

# Check for and send scheduled emails.
* * * * * cd /app/web && drush muser_system:send-scheduled-emails > /dev/null 2>&1

# Run queue_mail queue worker.
*/5 * * * * cd /app/web && drush queue:run queue_mail > /dev/null 2>&1
```
