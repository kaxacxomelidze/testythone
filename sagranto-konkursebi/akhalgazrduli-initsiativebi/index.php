<?php
declare(strict_types=1);

/*
 |------------------------------------------------------------
 | ფაილების ჩამოტვირთვა
 |------------------------------------------------------------
 | DOCX ფაილები ჩაყარე ამ მისამართზე:
 | /public_html/downloads/grants/
 */

$downloadFiles = [
    '3' => 'N3 ახალგაზრდული ინიციატივების მხარდაჭერა.docx',
    '3.1' => 'N3.1 საგრანტო პროექტი.docx',
    '3.2' => 'N3.2 სამოქმედო გეგმა.docx',
    'annex1' => 'დანართი №1. დებულება.docx',
    'annex2' => 'დანართი №2. შეფასების კრიტერიუმები.docx',
    'consent' => 'თანხმობის წერილი.docx',
];

$baseDir = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/downloads/grants/';

if (isset($_GET['download'])) {
    $key = (string)$_GET['download'];

    if (!isset($downloadFiles[$key])) {
        http_response_code(404);
        exit('ფაილი ვერ მოიძებნა.');
    }

    $fileName = $downloadFiles[$key];
    $filePath = $baseDir . $fileName;

    if (!is_file($filePath)) {
        http_response_code(404);
        exit('ფაილი სერვერზე არ არსებობს.');
    }

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($fileName));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . (string)filesize($filePath));

    readfile($filePath);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ახალგაზრდული ინიციატივები</title>
    <meta name="description" content="საგრანტო დოკუმენტები და დანართები ჩამოსატვირთად.">
    <link rel="canonical" href="https://youthagency.gov.ge/sagranto-konkursebi/akhalgazrduli-iniciativebi/">
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

        .files-title {
            margin: 0 0 25px 0;
            font-size: 28px;
            font-weight: 700;
            color: #111827;
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

<?php require_once __DIR__ . '/../../header.php'; ?>

<div class="container">
    <div class="files-box">
        <h1 class="files-title">საგრანტო კონკურსი - „ახალგაზრდული ინიციატივების მხარდაჭერა“</h1>

        <ul class="files-list">
            <li><a href="?download=3">N3 ახალგაზრდული ინიციატივების მხარდაჭერა</a></li>
            <li><a href="?download=3.1">N3.1 საგრანტო პროექტი</a></li>
            <li><a href="?download=3.2">N3.2 სამოქმედო გეგმა</a></li>
            <li><a href="?download=annex1">დანართი №1. დებულება</a></li>
            <li><a href="?download=annex2">დანართი №2. შეფასების კრიტერიუმები</a></li>
            <li><a href="?download=consent">თანხმობის წერილი</a></li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../../footer.php'; ?>
<script src="/app.js?v=2" defer></script>
<script>window.addEventListener("DOMContentLoaded",()=>{if(typeof window.initHeader==="function") window.initHeader(); if(typeof window.initFooterAccordion==="function") window.initFooterAccordion();},{once:true});</script>

</body>
</html>