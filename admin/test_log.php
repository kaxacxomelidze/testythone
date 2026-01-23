<?php
require_once __DIR__ . '/config.php';
require_login();

log_admin('test_log', 'test', 1, ['hello' => 'world']);

echo "OK saved log!";
