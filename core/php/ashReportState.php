<?php
if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit;
}
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
$plugin = plugin::byId('ash');
if ($plugin->isActive()) ash_reporting::flush();
