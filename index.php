<?php

/**
 * Fallback entry when the Apache Alias is not configured.
 * Prefer http://localhost/intranet via apache/alias.conf.
 */
require __DIR__.'/public/index.php';
