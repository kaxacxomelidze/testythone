<?php
declare(strict_types=1);

$headerTemplate = __DIR__ . '/header.html';
if (is_file($headerTemplate)) {
  readfile($headerTemplate);
  return;
}

http_response_code(500);
echo '<!-- header template missing: /header.html -->';
