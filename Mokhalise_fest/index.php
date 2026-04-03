<?php
declare(strict_types=1);

/*
 |------------------------------------------------------------
 | ფაილების ჩამოტვირთვა
 |------------------------------------------------------------
 | ფაილები უნდა იდოს აქ:
 | /public_html/downloads/grants/grants1/
 */

$downloadFiles = [
    'annex1' => [
        'label' => 'დანართი N 1 - სასკოლო მოხალისეობის ფესტივალი -აღწერა',
        'file'  => 'დანართი N 1 - სასკოლო მოხალისეობის ფესტივალი -აღწერა (1).pdf',
    ],
    'annex2' => [
        'label' => 'დანართი 2 - განაცხადის ფორმა',
        'file'  => 'დანართი 2 - განაცხადის ფორმა (1).docx',
    ],
    'annex3' => [
        'label' => 'დანართი N3 - მშობლის თანხმობის ფორმა',
        'file'  => 'დანართი N3 - მშობლის თანხმობის ფორმა (3).docx',
    ],
    'annex4' => [
        'label' => 'დანართი N 4 - მშობლის თანხმობის ფორმა(16 წლის ან მეტი ასაკის არასრულწლოვნისთვის)',
        'file'  => 'დანართი N 4 - მშობლის თანხმობის ფორმა(16 წლის ან მეტი ასაკის არასრულწლოვნისთვის) (1).docx',
    ],
];

$documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$baseDir = $documentRoot . '/downloads/grants/grants1/';

function findExistingPath(array $paths): ?string
{
    foreach ($paths as $path) {
        if ($path && is_file($path)) {
            return $path;
        }
    }
    return null;
}

$headerPath = findExistingPath([
    __DIR__ . '/../../header.php',
    __DIR__ . '/../header.php',
    $documentRoot . '/header.php',
]);

$footerPath = findExistingPath([
    __DIR__ . '/../../footer.php',
    __DIR__ . '/../footer.php',
    $documentRoot . '/footer.php',
]);

if (isset($_GET['download'])) {
    $key = (string) $_GET['download'];

    if (!isset($downloadFiles[$key])) {
        http_response_code(404);
        exit('ფაილი ვერ მოიძებნა.');
    }

    $fileName = $downloadFiles[$key]['file'];
    $filePath = $baseDir . $fileName;

    if (!is_file($filePath)) {
        http_response_code(404);
        exit('ფაილი სერვერზე არ არსებობს.');
    }

    if (ob_get_length()) {
        ob_end_clean();
    }

    $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));

    $mimeTypes = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($fileName));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . (string) filesize($filePath));

    readfile($filePath);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>სასკოლო მოხალისეობის ფესტივალი</title>
    <meta name="description" content="სასკოლო მოხალისეობის ფესტივალის დოკუმენტები და დანართები ჩამოსატვირთად.">
    <link rel="canonical" href="https://youthagency.gov.ge/sagranto_konkursebi/">
    <link rel="stylesheet" href="/assets.css?v=2">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, "Noto Sans Georgian", sans-serif;
            background: #f8fafc;
            color: #1f2937;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 50px 20px;
        }

        .files-box {
            background: #ffffff;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        }

        .top-logo {
            text-align: center;
            margin-bottom: 24px;
        }

        .top-logo img {
            max-width: 460px;
            width: 100%;
            height: auto;
            display: inline-block;
        }

        .files-title {
            margin: 0 0 25px 0;
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            text-align: center;
        }

        .files-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 14px;
        }

        .files-list li {
            margin: 0;
        }

        .files-list a {
            display: block;
            padding: 16px 18px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            text-decoration: none;
            font-size: 18px;
            font-weight: 600;
            color: #0f766e;
            background: #ffffff;
            transition: all 0.2s ease;
            word-break: break-word;
        }

        .files-list a:hover {
            background: #f0fdfa;
            border-color: #0f766e;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 15px;
            }

            .files-box {
                padding: 20px;
            }

            .top-logo {
                margin-bottom: 18px;
            }

            .top-logo img {
                max-width: 320px;
            }

            .files-title {
                font-size: 22px;
            }

            .files-list a {
                font-size: 16px;
                padding: 14px 15px;
            }
        }
    </style>
</head>
<body>

<?php if ($headerPath) require_once $headerPath; ?>

<div class="container">
    <div class="files-box">

        <div class="top-logo">
            <img src="/imgs/saskolo-mokhaliseoba-logo.png" alt="სასკოლო მოხალისეობა">
        </div>

        <h1 class="files-title">სასკოლო მოხალისეობის ფესტივალი</h1>

        <ul class="files-list">
            <?php foreach ($downloadFiles as $key => $item): ?>
                <li>
                    <a href="?download=<?php echo urlencode($key); ?>">
                        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

    </div>
</div>

<?php if ($footerPath) require_once $footerPath; ?>
<script src="/app.js?v=2" defer></script>
<script>
window.addEventListener("DOMContentLoaded", () => {
    if (typeof window.initHeader === "function") window.initHeader();
    if (typeof window.initFooterAccordion === "function") window.initFooterAccordion();
}, { once: true });
</script>

</body>
</html>