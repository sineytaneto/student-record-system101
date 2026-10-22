<?php
require __DIR__ . '/config.php';
$user=require_login();
$id=$_GET['id']??''; if (!$id) { http_response_code(400); exit('Invalid file request.'); }

$s = fs_get('submissions', $id);
if (!$s || ($user['role']!=='teacher' && (string)$s['user_id']!==(string)$user['id'])) { http_response_code(404); exit('File not found.'); }

$path=__DIR__.'/uploads/'.$s['stored_filename']; if (!is_file($path)) { http_response_code(404); exit('The stored file is missing.'); }
$name=str_replace(["\r","\n",'"'], '', basename($s['original_filename']));
header('X-Content-Type-Options: nosniff'); header('Content-Type: application/octet-stream'); header('Content-Length: '.filesize($path)); header('Content-Disposition: attachment; filename="'.$name.'"'); readfile($path); exit;
