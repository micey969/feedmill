<?php

const APP_PATH = __DIR__;

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$publicPath = str_replace('\\', '/', dirname(__DIR__) . '/public');
$relativeScript = str_starts_with($scriptFilename, $publicPath)
	? substr($scriptFilename, strlen($publicPath))
	: '';
$publicUrl = $relativeScript !== '' && str_ends_with($scriptName, $relativeScript)
	? substr($scriptName, 0, -strlen($relativeScript))
	: '';
define('PUBLIC_URL', rtrim($publicUrl, '/'));

function publicUrl(string $path = ''): string {
	return PUBLIC_URL . '/' . ltrim($path, '/');
}

require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/helpers/log_action.php';

