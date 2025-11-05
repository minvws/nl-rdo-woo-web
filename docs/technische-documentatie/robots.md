# Robots

## What is it

robots.txt is the filename used for implementing the Robots Exclusion Protocol, a standard used by websites to indicate to visiting
web crawlers and other web robots which portions of the website they are allowed to visit. The robots.txt-file should be available at the
root of the website, e.g. <https://open.minvws.nl/robots.txt>

## Sections

Our sitemap contains 3 sections:

- rules, a list of permissions for crawlers
- sitemaps, a list of sitemap-locations
- crawl delay, can be used to throttle crawl-speed

Each of these sections have a default configuration, found by their corresponding names in `templates/public/robots`.

To override the configration of a section, place a new file within the same folder. If a override file is found, it will be used instead of
the default. For example, if you want to override the rules, create the file `templates/public/robots/rules.override.html.twig` and place
the new rules there.
Note: the override-files are ignored by git.
