<?php
require_once __DIR__ . '/admin/config.php';
security_headers(false);
header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/footer.html');
