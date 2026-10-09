<?php
/**
 * Entry point of a uCoz Server Scripts PHP module — keep it this small.
 * Location:   domains/{domain}/public_html/{name}/index.php
 * Public URL: https://{site}/apps/{name}/   (always with the trailing slash: without it the server
 *             redirects to the technical domain and the visitor loses identity)
 *
 * Why a stub: by default PHP here writes no error_log you can read (the bootstrap sets one), so a syntax
 * error in the entry file gives a blank 500 with no trace. Loading bootstrap first installs the handlers; a
 * ParseError in lib/app.php is then caught and logged to {name}_data/app.log with file:line, and fatals
 * (e.g. memory exhausted) go to {name}_data/php-error.log (both verified live).
 */
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/app.php';
