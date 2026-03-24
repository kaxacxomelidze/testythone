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

function normalize_activity_payload_export($raw): ?array {
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

    $activity = trim((string)($row['activity'] ?? $row['title'] ?? $row['name'] ?? ''));
    $start = trim((string)($row['start_date'] ?? $row['start'] ?? ''));
    $end = trim((string)($row['end_date'] ?? $row['end'] ?? ''));
    $coverage = trim((string)($row['coverage'] ?? $row['description'] ?? $row['details'] ?? ''));

    if ($activity === '' && $start === '' && $end === '' && $coverage === '') continue;

    $rows[] = [
      'activity' => $activity,
      'start_date' => $start,
      'end_date' => $end,
      'coverage' => $coverage,
    ];
  }

  return $rows ? ['rows' => $rows, 'total_rows' => count($rows)] : null;
}

function detect_activity_tables_export(array $formData, array $fieldTypes = [], array $fieldLabels = []): array {
  $tables = [];
  $seen = [];

  foreach ($formData as $k => $v) {
    if ((string)$k === '__meta') continue;

    $nk = normalize_field_key_export((string)$k);
    $type = strtolower((string)($fieldTypes[$nk] ?? ''));
    $label = trim((string)($fieldLabels[$nk] ?? $fieldLabels[(string)$k] ?? (string)$k));
    $labelLower = strtolower($label);
    $keyLower = strtolower((string)$k);

    $activity = normalize_activity_payload_export($v);
    if ($activity === null) continue;

    $looksLikeActivity =
      str_contains($type, 'activity') ||
      str_contains($labelLower, 'აქტივ') ||
      str_contains($labelLower, 'action') ||
      str_contains($keyLower, 'action_plan');

    if (!$looksLikeActivity) continue;

    $signature = md5(json_encode($activity['rows'], JSON_UNESCAPED_UNICODE));
    if (isset($seen[$signature])) continue;
    $seen[$signature] = true;

    $tables[] = [
      'key' => (string)$k,
      'label' => $label !== '' ? $label : 'აქტივობების გეგმა',
      'rows' => $activity['rows'],
      'total_rows' => $activity['total_rows'],
    ];
  }

  return $tables;
}

function is_activity_row_key_export(string $key): bool {
  return (bool)preg_match('~(^|\.)rows\.\d+\.(activity|start_date|end_date|coverage)$~', $key)
    || (bool)preg_match('~(^|\.)total_rows$~', $key);
}

function should_skip_answer_key_export(string $key, array $skipPrefixes): bool {
  if (is_activity_row_key_export($key)) return true;

  foreach ($skipPrefixes as $prefix) {
    if ($prefix === '') continue;
    if ($key === $prefix) return true;
    if (str_starts_with($key, $prefix . '.rows.')) return true;
    if (str_starts_with($key, $prefix . '.total')) return true;
    if (str_starts_with($key, $prefix . '.total_rows')) return true;
  }
  return false;
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
$activityTables = detect_activity_tables_export($formData, $fieldTypes, $fieldLabels);

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

$skipAnswerPrefixes = [];
if ($budget) {
  foreach ($formData as $k => $v) {
    if ((string)$k === '__meta') continue;
    $candidate = normalize_budget_payload_export($v);
    if ($candidate && json_encode($candidate['rows'], JSON_UNESCAPED_UNICODE) === json_encode($budget['rows'], JSON_UNESCAPED_UNICODE)) {
      $skipAnswerPrefixes[] = (string)$k;
    }
  }
}
foreach ($activityTables as $tbl) {
  $skipAnswerPrefixes[] = (string)$tbl['key'];
}
$skipAnswerPrefixes = array_values(array_unique(array_filter($skipAnswerPrefixes)));

$prettyAnswers = [];
foreach ($answers as [$key, $value]) {
  $key = (string)$key;
  if (should_skip_answer_key_export($key, $skipAnswerPrefixes)) continue;

  $parts = explode('.', $key);
  $last = end($parts);
  $norm = normalize_field_key_export((string)$last);

  $label = $fieldLabels[$norm] ?? $fieldLabels[$key] ?? $key;

  $prettyAnswers[] = [
    'label' => (string)$label,
    'key'   => $key,
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
  color:#111827;
  font-size:12pt;
  line-height:1.5;
  margin:0;
  padding:0;
}
.page{
  max-width:860px;
  margin:0 auto;
  padding:24px 28px 32px;
}
.doc-title{
  text-align:center;
  margin-bottom:18px;
}
.doc-title h1{
  margin:0;
  font-size:20pt;
  font-weight:700;
}
.doc-title .grant{
  margin-top:8px;
  font-size:13pt;
}
.section{
  margin-top:18px;
}
.section h2{
  margin:0 0 10px 0;
  font-size:13pt;
  font-weight:700;
}
.info-table,
.details-table,
.budget-table,
.attachments-table{
  width:100%;
  border-collapse:collapse;
}
.info-table td,
.details-table td,
.budget-table th,
.budget-table td,
.attachments-table td,
.attachments-table th{
  border:1px solid #cbd5e1;
  padding:8px 10px;
  vertical-align:top;
}
.info-table .label,
.details-table .label,
.attachments-table th,
.budget-table th{
  background:#f8fafc;
  font-weight:700;
}
.info-table .label,
.details-table .label{
  width:30%;
}
.details-table .value{
  white-space:pre-wrap;
  word-break:break-word;
}
.total{
  margin-top:8px;
  text-align:right;
  font-weight:700;
}
</style>
</head>
<body>
<div class="page">
  <div class="doc-title">
    <h1>ახალგაზრდობის სააგენტო გრანტი</h1>
    <div class="grant"><b><?= esc_html_safe((string)($app['grant_title'] ?? '—')) ?></b></div>
  </div>

  <div class="section">
    <h2>განმცხადებლის ინფორმაცია</h2>
    <table class="info-table">
      <tr>
        <td class="label">სახელი და გვარი</td>
        <td><?= esc_html_safe((string)($app['applicant_name'] ?? '—')) ?></td>
      </tr>
      <tr>
        <td class="label">ელფოსტა</td>
        <td><?= esc_html_safe((string)($app['email'] ?? '—')) ?></td>
      </tr>
      <tr>
        <td class="label">ტელეფონი</td>
        <td><?= esc_html_safe((string)($app['phone'] ?? '—')) ?></td>
      </tr>
    </table>
  </div>

  <div class="section">
    <h2>განაცხადის დეტალები</h2>
    <table class="details-table">
      <tbody>
        <?php if (!$prettyAnswers): ?>
          <tr>
            <td class="label">მონაცემები</td>
            <td class="value">მონაცემები არ არის</td>
          </tr>
        <?php else: ?>
          <?php foreach ($prettyAnswers as $row): ?>
            <tr>
              <td class="label"><?= esc_html_safe($row['label']) ?></td>
              <td class="value"><?= nl2br(esc_html_safe($row['value'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($activityTables): ?>
    <?php foreach ($activityTables as $table): ?>
      <div class="section">
        <h2><?= esc_html_safe((string)$table['label']) ?></h2>
        <table class="budget-table">
          <thead>
            <tr>
              <th style="width:28%">აქტივობა</th>
              <th style="width:18%">დაწყების თარიღი</th>
              <th style="width:18%">დასრულების თარიღი</th>
              <th>აქტივობის გაშუქება</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($table['rows'] as $row): ?>
              <tr>
                <td><?= esc_html_safe((string)$row['activity']) ?></td>
                <td><?= esc_html_safe((string)$row['start_date']) ?></td>
                <td><?= esc_html_safe((string)$row['end_date']) ?></td>
                <td><?= esc_html_safe((string)$row['coverage']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($budget && !empty($budget['rows'])): ?>
    <div class="section">
      <h2>ბიუჯეტი</h2>
      <table class="budget-table">
        <thead>
          <tr>
            <th style="width:28%">კატეგორია</th>
            <th>აღწერა</th>
            <th style="width:18%">თანხა (₾)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($budget['rows'] as $row): ?>
            <tr>
              <td><?= esc_html_safe((string)$row['cat']) ?></td>
              <td><?= esc_html_safe((string)$row['desc']) ?></td>
              <td><?= esc_html_safe(format_money_export((float)$row['amount'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="total">ჯამი: <?= esc_html_safe(format_money_export((float)$budget['total'])) ?> ₾</div>
    </div>
  <?php endif; ?>

  <?php if ($uploads): ?>
    <div class="section">
      <h2>დართული ფაილები</h2>
      <table class="attachments-table">
        <thead>
          <tr>
            <th>ფაილის სახელი</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($uploads as $u): ?>
            <tr>
              <td><?= esc_html_safe((string)$u['original_name']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
