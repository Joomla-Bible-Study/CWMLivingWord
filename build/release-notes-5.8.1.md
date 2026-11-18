5.8.1 fixes a PHP 8.4 deprecation in the reading-plan CSV import, and keeps your site working if its PHP is ever older than LivingWord needs. It bundles CWM Scripture 1.2.21.

## Fixes

- **No deprecation notices from the reading-plan CSV import.** On PHP 8.4 and 8.5, importing a reading plan from a CSV file raised a PHP deprecation notice for every row.
- **LivingWord stands down on a PHP older than 8.3, instead of breaking the site.** That can happen when a host moves a site to an older PHP after LivingWord was installed. None of LivingWord's code loads: administrators see a banner explaining why, and opening LivingWord shows that banner instead.

## Changes

- **Bundles CWM Scripture 1.2.21**, with library 1.1.26. It adds the same PHP safeguard to the scripture plugins.

## PHP

PHP 8.3 is supported until CWM Proclaim 11 is released. From then on, LivingWord and the other CWM extensions require PHP 8.4 or later.
