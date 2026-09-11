<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/helpers/log_action.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

$reportName = trim($_POST['report_name'] ?? 'Report');

if ($reportName === '' || strlen($reportName) > 120) {
  http_response_code(400);
  exit;
}

$reportName = preg_replace('/[\x00-\x1F\x7F]/', '', $reportName);

logAction($conn, $_SESSION['user'] ?? 'unknown', 'PRINT', 'Printed ' . $reportName);

http_response_code(204);
