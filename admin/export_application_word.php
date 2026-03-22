<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/config.php';
require_login();

$pdo = db();

function esc_html_safe($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function parse_json_maybe_export($v) {
  if ($v === null) return null;

  if (is_string($v)) {
    $t = trim($v);
    if ($t === '') return null;
    $j = json_decode($t, true);
    return is_array($j) ? $j : null;
  }

  return is_array($v) ? $v : null;
}

function normalize_field_key_export(string $k): string {
  $k = trim($k);
  if ($k === '') return $k;
  if (preg_match('/^\d+$/', $k)) return 'field_' . $k;
  if (preg_match('/^f_(\d+)$/i', $k, $m)) return 'field_' . $m[1];
  return $k;
}

function money_to_float_export($value): float {
  if (is_int($value) || is_float($value)) return (float)$value;

  $s = trim((string)$value);
  if ($s === '') return 0.0;

  $s = str_replace(['₾', '$', '€', ',', ' '], '', $s);
  $s = preg_replace('~[^0-9.\-]~', '', $s);

  if ($s === '' || $s === '-' || $s === '.') return 0.0;

  return is_numeric($s) ? (float)$s : 0.0;
}

function format_money_export($n): string {
  return number_format((float)$n, 2, '.', ',');
}

function normalize_budget_payload_export($raw): ?array {
  $raw = parse_json_maybe_export($raw) ?? $raw;

  if (is_string($raw)) {
    $decoded = json_decode(trim($raw), true);
    if (is_array($decoded)) $raw = $decoded;
  }

  if (!is_array($raw)) return null;

  $rowsSource = null;

  if (isset($raw['rows']) && is_array($raw['rows'])) {
    $rowsSource = $raw['rows'];
  } elseif (array_keys($raw) === range(0, count($raw) - 1)) {
    $rowsSource = $raw;
  }

  if (!is_array($rowsSource)) return null;

  $rows = [];
  foreach ($rowsSource as $row) {
    $row = parse_json_maybe_export($row) ?? $row;
    if (!is_array($row)) continue;

    $cat = trim((string)($row['cat'] ?? $row['category'] ?? $row['name'] ?? ''));
    $desc = trim((string)($row['desc'] ?? $row['description'] ?? $row['details'] ?? ''));
    $amount = money_to_float_export($row['amount'] ?? $row['sum'] ?? $row['total'] ?? 0);

    if ($cat === '' && $desc === '' && $amount <= 0) continue;

    $rows[] = [
      'cat' => $cat,
      'desc' => $desc,
      'amount' => $amount,
    ];
  }

  $total = 0.0;
  foreach ($rows as $r) {
    $total += (float)$r['amount'];
  }

  return [
    'rows' => $rows,
    'total' => $total,
  ];
}

function value_to_text_export($v): string {
  if ($v === null) return '—';
  if (is_bool($v)) return $v ? 'true' : 'false';
  if (is_scalar($v)) {
    $s = trim((string)$v);
    return $s !== '' ? $s : '—';
  }

  $parsed = parse_json_maybe_export($v);
  if (is_array($parsed)) {
    return json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  }

  return '—';
}

function flatten_answers_export($data, string $prefix = '', array &$out = []): void {
  $data = parse_json_maybe_export($data) ?? $data;

  if ($data === null) return;

  if (is_array($data)) {
    $isList = array_keys($data) === range(0, count($data) - 1);

    if ($isList) {
      foreach ($data as $i => $v) {
        $next = $prefix !== '' ? ($prefix . '.' . ($i + 1)) : (string)($i + 1);
        flatten_answers_export($v, $next, $out);
      }
      return;
    }

    foreach ($data as $k => $v) {
      if ((string)$k === '__meta') continue;
      if ((string)$k === 'budget') continue;

      $next = $prefix !== '' ? ($prefix . '.' . $k) : (string)$k;

      $pv = parse_json_maybe_export($v);
      if (is_array($pv)) {
        flatten_answers_export($pv, $next, $out);
      } else {
        $out[] = [$next, value_to_text_export($v)];
      }
    }
    return;
  }

  if ($prefix !== '') {
    $out[] = [$prefix, value_to_text_export($data)];
  }
}

function detect_budget_export(array $formData, array $fieldTypes = [], array $fieldLabels = []): ?array {
  if (isset($formData['budget'])) {
    $b = normalize_budget_payload_export($formData['budget']);
    if ($b !== null) return $b;
  }

  foreach ($formData as $k => $v) {
    if ((string)$k === '__meta') continue;

    $nk = normalize_field_key_export((string)$k);
    $type = strtolower((string)($fieldTypes[$nk] ?? ''));
    $label = strtolower((string)($fieldLabels[$nk] ?? $nk));

    if (
      $type === 'budget_table' ||
      str_contains($type, 'budget') ||
      str_contains($label, 'ბიუჯ') ||
      str_contains($label, 'budget')
    ) {
      $b = normalize_budget_payload_export($v);
      if ($b !== null) return $b;
    }
  }

  return null;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo 'Invalid application id';
  exit;
}

$st = $pdo->prepare("
  SELECT a.*, g.title AS grant_title
  FROM grant_applications a
  LEFT JOIN grants g ON g.id = a.grant_id
  WHERE a.id = ? AND a.deleted_at IS NULL
  LIMIT 1
");
$st->execute([$id]);
$app = $st->fetch(PDO::FETCH_ASSOC);

if (!$app) {
  http_response_code(404);
  echo 'Application not found';
  exit;
}

$formData = [];
if (!empty($app['form_data_json'])) {
  $tmp = json_decode((string)$app['form_data_json'], true);
  if (is_array($tmp)) $formData = $tmp;
}

$meta = parse_json_maybe_export($formData['__meta'] ?? null);
$fieldLabels = is_array($meta['field_labels'] ?? null) ? $meta['field_labels'] : [];
$fieldTypes  = is_array($meta['field_types'] ?? null) ? $meta['field_types'] : [];

try {
  $stF = $pdo->prepare("SELECT id, label, type FROM grant_fields WHERE grant_id = ?");
  $stF->execute([(int)$app['grant_id']]);
  foreach (($stF->fetchAll(PDO::FETCH_ASSOC) ?: []) as $r) {
    $key = 'field_' . (int)$r['id'];
    if (!isset($fieldLabels[$key])) $fieldLabels[$key] = (string)$r['label'];
    if (!isset($fieldTypes[$key]))  $fieldTypes[$key]  = (string)$r['type'];
  }
} catch (Throwable $e) {
}

$budget = detect_budget_export($formData, $fieldTypes, $fieldLabels);

$up = $pdo->prepare("
  SELECT original_name, file_path, size_bytes, mime_type, created_at
  FROM grant_uploads
  WHERE application_id = ? AND deleted_at IS NULL
  ORDER BY id ASC
");
$up->execute([$id]);
$uploads = $up->fetchAll(PDO::FETCH_ASSOC) ?: [];

$answers = [];
flatten_answers_export($formData, '', $answers);

$prettyAnswers = [];
foreach ($answers as [$key, $value]) {
  $parts = explode('.', (string)$key);
  $last = end($parts);
  $norm = normalize_field_key_export((string)$last);

  $label = $fieldLabels[$norm] ?? $fieldLabels[(string)$key] ?? (string)$key;

  $prettyAnswers[] = [
    'label' => (string)$label,
    'key'   => (string)$key,
    'value' => trim((string)$value) !== '' ? (string)$value : '—',
  ];
}

$filename = 'application_' . (int)$id . '.doc';

header('Content-Type: application/msword; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8">
<title>Application <?= (int)$id ?></title>
<style>
body{
  font-family: DejaVu Sans, Arial, sans-serif;
  color:#1f2937;
  font-size:12pt;
  line-height:1.45;
  margin:0;
  padding:0;
}
.page{
  max-width:920px;
  margin:0 auto;
  padding:18px;
}
.head{
  background:linear-gradient(135deg,#0f172a,#1e3a8a);
  color:#fff;
  padding:24px 26px;
  border-radius:16px;
  margin-bottom:22px;
}
.head h1{
  margin:0 0 8px 0;
  font-size:24pt;
}
.head .meta{
  font-size:11pt;
  opacity:.95;
}
.grid{
  width:100%;
  border-collapse:separate;
  border-spacing:10px;
  margin-bottom:12px;
}
.card{
  background:#f8fafc;
  border:1px solid #dbe4f0;
  border-radius:14px;
  padding:14px 16px;
}
.card .label{
  font-size:10pt;
  color:#64748b;
  margin-bottom:6px;
}
.card .value{
  font-size:14pt;
  font-weight:bold;
  color:#0f172a;
}
.section{
  margin-top:22px;
}
.section h2{
  font-size:16pt;
  color:#0f172a;
  border-bottom:2px solid #dbe4f0;
  padding-bottom:6px;
  margin:0 0 12px 0;
}
.table{
  width:100%;
  border-collapse:collapse;
}
.table th,
.table td{
  border:1px solid #dbe4f0;
  padding:10px;
  text-align:left;
  vertical-align:top;
}
.table th{
  background:#eff6ff;
  color:#1d4ed8;
  font-weight:bold;
}
.small{
  font-size:10pt;
  color:#64748b;
}
.totalBox{
  margin-top:10px;
  text-align:right;
  font-weight:bold;
  font-size:13pt;
  color:#0f172a;
}
.key{
  color:#64748b;
  font-size:10pt;
  margin-top:4px;
}
</style>
</head>
<body>
<div class="page">

  <div class="head">
    <h1>განაცხადი #<?= (int)$app['id'] ?></h1>
    <div class="meta">
      გრანტი: <b><?= esc_html_safe((string)($app['grant_title'] ?? '—')) ?></b><br>
      შექმნა: <b><?= esc_html_safe((string)($app['created_at'] ?? '—')) ?></b> •
      სტატუსი: <b><?= esc_html_safe((string)($app['status'] ?? 'submitted')) ?></b> •
      ქულა: <b><?= (int)($app['rating'] ?? 0) ?></b>
    </div>
  </div>

  <table class="grid">
    <tr>
      <td class="card" style="width:25%">
        <div class="label">განმცხადებელი</div>
        <div class="value"><?= esc_html_safe((string)($app['applicant_name'] ?? '—')) ?></div>
      </td>
      <td class="card" style="width:25%">
        <div class="label">ელ.ფოსტა</div>
        <div class="value"><?= esc_html_safe((string)($app['email'] ?? '—')) ?></div>
      </td>
      <td class="card" style="width:25%">
        <div class="label">ტელეფონი</div>
        <div class="value"><?= esc_html_safe((string)($app['phone'] ?? '—')) ?></div>
      </td>
      <td class="card" style="width:25%">
        <div class="label">ბიუჯეტი</div>
        <div class="value"><?= $budget ? esc_html_safe(format_money_export((float)$budget['total']) . ' ₾') : '—' ?></div>
      </td>
    </tr>
  </table>

  <?php if ($budget && !empty($budget['rows'])): ?>
    <div class="section">
      <h2>ბიუჯეტი</h2>
      <table class="table">
        <thead>
          <tr>
            <th style="width:24%">კატეგორია</th>
            <th>აღწერა</th>
            <th style="width:18%">თანხა (₾)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($budget['rows'] as $row): ?>
            <tr>
              <td><b><?= esc_html_safe((string)$row['cat']) ?></b></td>
              <td><?= esc_html_safe((string)$row['desc']) ?></td>
              <td><b><?= esc_html_safe(format_money_export((float)$row['amount'])) ?></b></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="totalBox">ჯამი: <?= esc_html_safe(format_money_export((float)$budget['total'])) ?> ₾</div>
    </div>
  <?php endif; ?>

  <div class="section">
    <h2>ფორმის პასუხები</h2>
    <table class="table">
      <thead>
        <tr>
          <th style="width:32%">ველი</th>
          <th>მნიშვნელობა</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$prettyAnswers): ?>
          <tr><td colspan="2">მონაცემები არ არის</td></tr>
        <?php else: ?>
          <?php foreach ($prettyAnswers as $row): ?>
            <tr>
              <td>
                <b><?= esc_html_safe($row['label']) ?></b>
                <div class="key"><?= esc_html_safe($row['key']) ?></div>
              </td>
              <td><?= nl2br(esc_html_safe($row['value'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="section">
    <h2>ატვირთული ფაილები</h2>
    <table class="table">
      <thead>
        <tr>
          <th style="width:34%">ფაილი</th>
          <th style="width:15%">ზომა</th>
          <th style="width:22%">ტიპი</th>
          <th>ბილიკი</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$uploads): ?>
          <tr><td colspan="4">ფაილები არ არის ატვირთული</td></tr>
        <?php else: ?>
          <?php foreach ($uploads as $u): ?>
            <tr>
              <td><b><?= esc_html_safe((string)$u['original_name']) ?></b></td>
              <td><?= esc_html_safe(number_format(((float)$u['size_bytes'] / 1024 / 1024), 2)) ?> MB</td>
              <td><?= esc_html_safe((string)($u['mime_type'] ?? '—')) ?></td>
              <td><?= esc_html_safe((string)($u['file_path'] ?? '—')) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($app['admin_note'])): ?>
    <div class="section">
      <h2>ადმინის შენიშვნა</h2>
      <table class="table">
        <tbody>
          <tr>
            <td><?= nl2br(esc_html_safe((string)$app['admin_note'])) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>
</body>
</html>