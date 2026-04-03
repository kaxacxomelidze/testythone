<?php
declare(strict_types=1);

/**
 * დროებითი debug
 * როცა გვერდი ამუშავდება, ქვემოთ ეს 4 ხაზი შეგიძლია წაშალო
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/*
 |------------------------------------------------------------
 | სასკოლო მოხალისეობის ფესტივალი
 | URL:
 | https://youthagency.gov.ge/sagranto_konkursebi/
 |
 | ფაილები უნდა იდოს აქ:
 | /public_html/downloads/grants/grants1/
 |------------------------------------------------------------
 */

$downloadFiles = [
    'annex1' => [
        'label' => 'დანართი N 1 - სასკოლო მოხალისეობის ფესტივალი -აღწერა.pdf',
        'file'  => 'დანართი N 1 - სასკოლო მოხალისეობის ფესტივალი - აღწერა.pdf',
    ],
    'annex2' => [
        'label' => 'დანართი 2 - განაცხადის ფორმა.docx',
        'file'  => 'დანართი 2 - განაცხადის ფორმა (2).docx',
    ],
    'annex3' => [
        'label' => 'დანართი N3 - მშობლის თანხმობის ფორმა.docx',
        'file'  => 'დანართი N3 - მშობლის თანხმობის ფორმა (1).docx',
    ],
    'annex4' => [
        'label' => 'დანართი N 4 - მშობლის თანხმობის ფორმა(16 წლის ან მეტი ასაკის არასრულწლოვნისთვის).docx',
        'file'  => 'დანართი N 4 - მშობლის თანხმობის ფორმა(16 წლის ან მეტი ასაკის არასრულწლოვნისთვის) (1).docx',
    ],
];

$documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');

if ($documentRoot === '') {
    exit('DOCUMENT_ROOT ვერ განისაზღვრა.');
}

$baseDir = $documentRoot . '/downloads/grants/grants1/';
$headerPath = $documentRoot . '/header.php';
$footerPath = $documentRoot . '/footer.php';

/**
 * ჩამოტვირთვა
 */
if (isset($_GET['download'])) {
    $key = (string) $_GET['download'];

    if (!isset($downloadFiles[$key])) {
        http_response_code(404);
        exit('ფაილი ვერ მოიძებნა.');
    }

    $realFileName = $downloadFiles[$key]['file'];
    $filePath = $baseDir . $realFileName;

    if (!is_file($filePath)) {
        http_response_code(404);
        exit('ფაილი სერვერზე არ არსებობს: ' . htmlspecialchars($realFileName, ENT_QUOTES, 'UTF-8'));
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
    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($realFileName));
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
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="https://youthagency.gov.ge/sagranto_konkursebi/">

    <style>
        * { box-sizing: border-box; }

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

        .files-title {
            margin: 0 0 12px;
            font-size: 30px;
            font-weight: 700;
            text-align: center;
            color: #111827;
        }

        .files-subtitle {
            text-align: center;
            font-size: 16px;
            color: #4b5563;
            margin: 0 0 25px;
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

        .debug-box {
            margin-top: 28px;
            padding: 16px;
            border-radius: 12px;
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #9a3412;
            font-size: 14px;
            line-height: 1.6;
        }

        .debug-box code {
            word-break: break-all;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 15px;
            }

            .files-box {
                padding: 20px;
            }

            .files-title {
                font-size: 24px;
            }

            .files-list a {
                font-size: 16px;
                padding: 14px 15px;
            }
        }
    </style>
</head>
<body>

<?php
if (is_file($headerPath)) {
    require_once $headerPath;
}
?>

<div class="container">
    <div class="files-box">
        <h1 class="files-title">სასკოლო მოხალისეობის ფესტივალი</h1>
        <p class="files-subtitle">ქვემოთ მოცემულია კონკურსთან დაკავშირებული დანართები ჩამოსატვირთად.</p>

        <ul class="files-list">
            <?php foreach ($downloadFiles as $key => $item): ?>
                <li>
                    <a href="?download=<?php echo urlencode($key); ?>">
                        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="debug-box">
            <strong>DEBUG INFO</strong><br>
            DOCUMENT_ROOT: <code><?php echo htmlspecialchars($documentRoot, ENT_QUOTES, 'UTF-8'); ?></code><br>
            DOWNLOAD PATH: <code><?php echo htmlspecialchars($baseDir, ENT_QUOTES, 'UTF-8'); ?></code><br>
            HEADER EXISTS: <code><?php echo is_file($headerPath) ? 'YES' : 'NO'; ?></code><br>
            FOOTER EXISTS: <code><?php echo is_file($footerPath) ? 'YES' : 'NO'; ?></code><br><br>

            <strong>ფაილების შემოწმება:</strong><br>
            <?php foreach ($downloadFiles as $item): ?>
                <?php $full = $baseDir . $item['file']; ?>
                - <?php echo htmlspecialchars($item['file'], ENT_QUOTES, 'UTF-8'); ?> :
                <code><?php echo is_file($full) ? 'FOUND' : 'NOT FOUND'; ?></code><br>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
if (is_file($footerPath)) {
    require_once $footerPath;
}
?>

</body>
</html>