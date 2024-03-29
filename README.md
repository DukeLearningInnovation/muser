# Muser

[![DOI](https://zenodo.org/badge/184466830.svg)](https://zenodo.org/badge/latestdoi/184466830)

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

## Installation

To install the site code, use the [muser-drupal-project](https://github.com/jaybeaton/muser-drupal-project) Composer project:

```
composer create-project jaybeaton/muser-drupal-project:9.x-dev some-dir --no-interaction
```

This will download the Muser code along with Drupal core and all required contributed modules and libraries, and will apply all required patches.

Then, install the Drupal Muser site by visiting your site in a browser and running through the Drupal installation steps.

See the [README](https://github.com/jaybeaton/muser-drupal-project/blob/master/README.md) for more details.
