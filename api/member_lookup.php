<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../admin/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

@ini_set('display_errors', '0');
if (ob_get_level() === 0) { ob_start(); }

function out(array $j): void {
  $garbage = ob_get_clean();
  echo json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function ok(array $extra = []): void { out(["ok"=>true] + $extra); }
function fail(string $msg, int $code = 400): void { http_response_code($code); out(["ok"=>false,"error"=>$msg]); }

if (!isset($pdo) || !($pdo instanceof PDO)) fail("DB not initialized", 500);

$pid = preg_replace('/\s+/', '', trim((string)($_GET['pid'] ?? '')));
if ($pid === '') fail("PID required");

try {
  // 1) members table (main source)
  $st = $pdo->prepare("SELECT pid, first_name, last_name, birth_date, age, phone, email, address, university, faculty, course
                       FROM members
                       WHERE pid = ?
                       LIMIT 1");
  $st->execute([$pid]);
  $m = $st->fetch(PDO::FETCH_ASSOC);

  $member = null;
  $source = null;

  if ($m) {
    $member = [
      "pid" => (string)($m["pid"] ?? ""),
      "first_name" => (string)($m["first_name"] ?? ""),
      "last_name" => (string)($m["last_name"] ?? ""),
      "birth_date" => (string)($m["birth_date"] ?? ""),
      "age" => (string)($m["age"] ?? ""),
      "phone" => (string)($m["phone"] ?? ""),
      "email" => (string)($m["email"] ?? ""),
      "address" => (string)($m["address"] ?? ""),
      "university" => (string)($m["university"] ?? ""),
      "faculty" => (string)($m["faculty"] ?? ""),
      "course" => (string)($m["course"] ?? ""),
    ];
    $source = "members";
  }

  // 2) fallback: last camp registration if not in members
  $lastReg = null;
  $st2 = $pdo->prepare("SELECT id, camp_id, created_at, values_json
                        FROM camps_registrations
                        WHERE JSON_SEARCH(values_json, 'one', ?) IS NOT NULL
                        ORDER BY id DESC
                        LIMIT 1");
  $st2->execute([$pid]);
  $r = $st2->fetch(PDO::FETCH_ASSOC);

  if ($r) {
    $vals = json_decode((string)($r["values_json"] ?? "[]"), true);
    if (!is_array($vals)) $vals = [];
    $lastReg = [
      "id" => (int)$r["id"],
      "camp_id" => (int)$r["camp_id"],
      "created_at" => (string)$r["created_at"],
      "values_map" => $vals
    ];

    // if member is missing, try to infer minimal info from last registration
    if (!$member) {
      $member = ["pid"=>$pid];
      $source = "last_registration";
    }
  }

  ok([
    "found" => ($member !== null),
    "source" => $source,
    "member" => $member,
    "last_registration" => $lastReg
  ]);

} catch (Throwable $e) {
  fail("Server error: ".$e->getMessage(), 500);
}
