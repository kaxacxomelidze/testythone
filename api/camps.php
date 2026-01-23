<?php
declare(strict_types=1);
require __DIR__ . "/../inc/db.php";

$pdo = db();
$action = $_GET["action"] ?? "";

function is_open(array $c): bool {
  $today = date("Y-m-d");
  if ((int)$c["manual_closed"] === 1) return false;
  if ($c["end_date"] < $today) return false;
  return true;
}

if ($action === "list") {
  $st = $pdo->query("SELECT id,name,slug,cover,card_text,start_date,end_date,manual_closed,window_days
                     FROM camps ORDER BY created_at DESC, id DESC");
  $rows = array_values(array_filter($st->fetchAll(), fn($c)=>is_open($c)));
  json_out(["ok"=>true, "camps"=>$rows]);
}

if ($action === "view") {
  $id = (int)($_GET["id"] ?? 0);
  if ($id<=0) json_out(["ok"=>false,"error"=>"Bad id"], 400);

  $st = $pdo->prepare("SELECT * FROM camps WHERE id=?");
  $st->execute([$id]);
  $camp = $st->fetch();
  if (!$camp) json_out(["ok"=>false,"error"=>"Not found"], 404);

  $f = $pdo->prepare("SELECT id, sort_order, label, type, required, options_text
                      FROM camp_fields WHERE camp_id=? ORDER BY sort_order ASC, id ASC");
  $f->execute([$id]);
  $fields = $f->fetchAll();

  $p = $pdo->prepare("SELECT id,title,body,cover,created_at
                      FROM camp_posts WHERE camp_id=? ORDER BY created_at DESC, id DESC");
  $p->execute([$id]);
  $posts = $p->fetchAll();

  json_out(["ok"=>true, "camp"=>$camp, "fields"=>$fields, "posts"=>$posts, "open"=>is_open($camp)]);
}

json_out(["ok"=>false,"error"=>"Unknown action"], 400);
