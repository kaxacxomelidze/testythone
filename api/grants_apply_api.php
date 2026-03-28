<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config.php';
$pdo = db();
security_headers(true);
enforce_http_method(['POST'], true);
enforce_same_origin_post(true);
enforce_content_length(20 * 1024 * 1024, true);
enforce_rate_limit('grants_apply_api', 40, 300, true);

function audit_grant_apply(string $result, array $details = []): void {
  if (!function_exists('log_admin_safe')) return;
  log_admin_safe('grant_apply_' . $result, 'grant_applications', null, $details + [
    'grant_id' => (int)($_POST['grant_id'] ?? 0),
    'method' => (string)($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    'ip' => client_ip(),
  ]);
}

function json_out(array $d, int $code=200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  if (!empty($d['ok'])) {
    audit_grant_apply('ok', ['status_code' => $code]);
  } else {
    audit_grant_apply('fail', ['status_code' => $code, 'error' => (string)($d['error'] ?? '')]);
  }
  echo json_encode($d, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

try{
  $grantId = (int)($_POST['grant_id'] ?? 0);
  if($grantId<=0) json_out(['ok'=>false,'error'=>'Bad grant'], 400);

  $st = $pdo->prepare("SELECT id,status,is_active FROM grants WHERE id=? AND is_active=1 LIMIT 1");
  $st->execute([$grantId]);
  $g = $st->fetch(PDO::FETCH_ASSOC);
  if(!$g) json_out(['ok'=>false,'error'=>'Grant not found'], 404);
  if(($g['status'] ?? 'current') === 'closed') json_out(['ok'=>false,'error'=>'Grant closed'], 403);

  // Load builder fields to validate required fields
  $stF = $pdo->prepare("SELECT id,label,type,is_required FROM grant_form_fields WHERE grant_id=?");
  $stF->execute([$grantId]);
  $fields = $stF->fetchAll(PDO::FETCH_ASSOC);

  $data = [];
  foreach($fields as $f){
    $key = 'f_' . (int)$f['id'];
    $type = (string)($f['type'] ?? 'text');

    if($type === 'file'){
      // (optional) file uploads later - for now store filename placeholder
      if(isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]['tmp_name'])){
        // minimal safe store (you can upgrade later to per-grant folders)
        $upDir = __DIR__ . '/../uploads/grant_apps';
        if(!is_dir($upDir)) @mkdir($upDir, 0775, true);

        $name = basename((string)$_FILES[$key]['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $safeExt = preg_match('/^(pdf|doc|docx|jpg|jpeg|png|webp|gif)$/', $ext) ? $ext : 'bin';
        $base = 'g'.$grantId.'_'.time().'_' . bin2hex(random_bytes(4));
        $new = $base . '.' . $safeExt;
        $dest = $upDir . '/' . $new;

        $isImage = in_array($safeExt, ['jpg','jpeg','png','webp','gif'], true);
        if ($isImage) {
          $new = $base . '.webp';
          $dest = $upDir . '/' . $new;
          if (!convert_image_to_webp($_FILES[$key]['tmp_name'], $dest, 90)) {
            $new = $base . '.' . $safeExt;
            $dest = $upDir . '/' . $new;
            if(!move_uploaded_file($_FILES[$key]['tmp_name'], $dest)){
              json_out(['ok'=>false,'error'=>'File upload failed'], 400);
            }
          }
        } else {
          if(!move_uploaded_file($_FILES[$key]['tmp_name'], $dest)){
            json_out(['ok'=>false,'error'=>'File upload failed'], 400);
          }
        }

        $data[$key] = '/uploads/grant_apps/' . $new;
      } else {
        $data[$key] = '';
      }

      if((int)$f['is_required']===1 && $data[$key]===''){
        json_out(['ok'=>false,'error'=>'Missing required file: '.$f['label']], 400);
      }
      continue;
    }

    $val = trim((string)($_POST[$key] ?? ''));
    if((int)$f['is_required']===1 && $val===''){
      json_out(['ok'=>false,'error'=>'Missing required: '.$f['label']], 400);
    }
    $data[$key] = $val;
  }

  // try to extract applicant meta (best-effort)
  $applicant_name = '';
  $email = '';
  $phone = '';

  foreach($data as $k=>$v){
    $lv = mb_strtolower((string)$v, 'UTF-8');
    if($email==='' && str_contains($lv,'@')) $email = (string)$v;
    if($phone==='' && preg_match('/^\+?[0-9][0-9 \-\(\)]{6,}$/', (string)$v)) $phone=(string)$v;
  }

  // naive name guess: first non-empty text value
  foreach($data as $v){
    if(trim((string)$v)!=='' && mb_strlen((string)$v,'UTF-8')<=190){
      $applicant_name = (string)$v;
      break;
    }
  }

  $pdo->prepare("
    INSERT INTO grant_applications(grant_id, applicant_name, email, phone, status, rating, form_data_json, created_at)
    VALUES(?,?,?,?, 'submitted', 0, ?, NOW())
  ")->execute([$grantId, $applicant_name ?: null, $email ?: null, $phone ?: null, json_encode($data, JSON_UNESCAPED_UNICODE)]);

  $appId = (int)$pdo->lastInsertId();
  json_out(['ok'=>true,'app_id'=>$appId]);

}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
