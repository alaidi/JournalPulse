#!/bin/bash

# The plugin's own tests inside PKP's continuous integration (sourced by
# pkp-github-actions from the OJS root): the browser tests first, then the unit
# tests. Neither may be skipped because the other failed, so the status of both
# is kept and the worst one is returned.

set +e
status=0

# pkp-github-actions installs the theme as plugins/themes/<repository name>
# (JournalMetrics), but its code and version.xml call it journalMetrics, and on a
# case-sensitive file system OJS only finds it there. Move it where OJS's
# "Upload A New Plugin" would put it, then register its version.
mv plugins/themes/JournalMetrics plugins/themes/journalMetrics
plugin=plugins/themes/journalMetrics
php lib/pkp/tools/installPluginVersion.php $plugin/version.xml

npx cypress run  --headless --browser chrome  --config '{"specPattern":["plugins/themes/journalMetrics/cypress/tests/functional/*.cy.js"]}'
cypress=$?
[ $cypress -ne 0 ] && status=$cypress

php lib/pkp/lib/vendor/bin/phpunit --configuration lib/pkp/tests/phpunit.xml --no-coverage $plugin/tests
phpunit=$?
[ $phpunit -ne 0 ] && status=$phpunit

echo "tests.sh: cypress=$cypress phpunit=$phpunit"
return $status
