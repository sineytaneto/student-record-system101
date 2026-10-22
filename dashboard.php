<?php
require __DIR__ . '/config.php';
$user=require_login();
$flash=take_flash();
$err='';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($user['role']==='student')) {
 check_csrf();
 $req=trim($_POST['requirement_name']??''); $desc=trim($_POST['description']??''); $file=$_FILES['requirement_file']??null;
 if ($req==='' || mb_strlen($req)>150) $err='Enter a requirement name (up to 150 characters).';
 elseif (!$file || $file['error']!==UPLOAD_ERR_OK) $err='Choose a file to upload (maximum 10 MB).';
 elseif ($file['size']<1 || $file['size']>MAX_UPLOAD_BYTES) $err='File must be smaller than 10 MB.';
 else {
  $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
  $allowed=['pdf','doc','docx','jpg','jpeg','png'];
  $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
  $mimeAllowed=['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/jpeg','image/png','application/octet-stream'];
  if (!in_array($ext,$allowed,true) || !in_array($mime,$mimeAllowed,true)) $err='Allowed files: PDF, DOC, DOCX, JPG, and PNG.';
  else {
   $dir=__DIR__.'/uploads'; if (!is_dir($dir)) mkdir($dir,0750,true);
   $stored=bin2hex(random_bytes(20)).'.'.$ext;
   if (move_uploaded_file($file['tmp_name'],$dir.'/'.$stored)) {
    fs_insert('submissions', [
        'user_id' => $user['id'],
        'requirement_name' => $req,
        'description' => $desc,
        'original_filename' => basename($file['name']),
        'stored_filename' => $stored,
        'mime_type' => $mime,
        'file_size' => (int)$file['size'],
        'status' => 'pending',
        'teacher_remarks' => null,
        'reviewed_by' => null,
        'reviewed_at' => null,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    flash('Requirement submitted successfully. It is now waiting for teacher review.'); header('Location: dashboard.php'); exit;
   } else $err='The server could not save the uploaded file. Check folder permissions.';
  }
 }
}

$allUsers = [];
foreach (fs_query('users') as $u) {
    $allUsers[$u['id']] = $u;
}

if ($user['role']==='teacher') {
 $allSubs = fs_query('submissions');
 usort($allSubs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
 
 $rows = [];
 foreach ($allSubs as $s) {
     $uid = $s['user_id'] ?? '';
     if (isset($allUsers[$uid])) {
         $s['full_name'] = $allUsers[$uid]['full_name'];
         $s['student_id'] = $allUsers[$uid]['student_id'] ?? '';
         $s['email'] = $allUsers[$uid]['email'];
     } else {
         $s['full_name'] = 'Unknown';
         $s['student_id'] = '';
         $s['email'] = '';
     }
     $rows[] = $s;
 }

 $stats = ['total' => count($rows), 'pending' => 0, 'approved' => 0, 'returned' => 0];
 foreach ($rows as $r) {
     if (isset($stats[$r['status']])) $stats[$r['status']]++;
 }
} else {
 $allSubs = fs_query('submissions');
 $rows = [];
 foreach ($allSubs as $s) {
     if (($s['user_id'] ?? '') === $user['id']) {
         $rows[] = $s;
     }
 }
 usort($rows, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
 $stats=['total'=>count($rows),'pending'=>0,'approved'=>0,'returned'=>0]; 
 foreach($rows as $r) if(isset($stats[$r['status']])) $stats[$r['status']]++;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard | Student Record System</title><link rel="stylesheet" href="style.css"></head><body><header class="topbar"><a class="brand-mini" href="dashboard.php"><span class="crest small-crest">ER</span><span><strong>Student Record System</strong><small>El Royale Hotelier Training Center Inc.</small></span></a><div class="top-user"><div><strong><?=h($user['full_name'])?></strong><small><?=h(ucfirst($user['role']))?></small></div><a class="btn outline" href="logout.php">Log out</a></div></header><main class="main-wrap"><section class="welcome"><div><p class="eyebrow dark">YOUR RECORDS. YOUR PROGRESS.</p><h1><?= $user['role']==='teacher' ? 'Review student requirements' : 'Your requirements dashboard' ?></h1><p><?= $user['role']==='teacher' ? 'Review real submissions, download documents, and record your decision.' : 'Submit your documents and check the status of every requirement.' ?></p></div><div class="welcome-icon">✓</div></section>
<?php if ($flash): ?><div class="alert <?=h($flash['type'])?>"><?=h($flash['message'])?></div><?php endif; ?><?php if ($err): ?><div class="alert error"><?=h($err)?></div><?php endif; ?>
<section class="stats"><div class="stat"><span>Total submissions</span><strong><?= (int)($stats['total']??0) ?></strong></div><div class="stat"><span>Pending review</span><strong><?= (int)($stats['pending']??0) ?></strong></div><div class="stat"><span>Approved</span><strong><?= (int)($stats['approved']??0) ?></strong></div><div class="stat"><span>Returned</span><strong><?= (int)($stats['returned']??0) ?></strong></div></section>
<?php if ($user['role']==='student'): ?><section class="panel"><div class="panel-head"><div><h2>Submit a requirement</h2><p class="muted">Your submission will be marked Pending until a teacher reviews it.</p></div></div><form class="submit-grid" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><div><label>Requirement name *</label><input name="requirement_name" required maxlength="150" placeholder="e.g. Clearance requirement"></div><div><label>Document *</label><input type="file" name="requirement_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required><small class="muted">PDF, DOC, DOCX, JPG or PNG · up to 10 MB</small></div><div class="wide"><label>Description / note (optional)</label><textarea name="description" rows="2" maxlength="2000" placeholder="Add a note for your teacher"></textarea></div><div class="wide"><button class="btn primary" type="submit">Submit requirement</button></div></form></section><?php endif; ?>
<section class="panel"><div class="panel-head"><div><h2><?= $user['role']==='teacher'?'Student submissions':'My submitted requirements' ?></h2><p class="muted"><?= $user['role']==='teacher'?'Only registered student submissions appear here.':'No example data is included; this list shows your actual submissions.' ?></p></div></div>
<?php if (!$rows): ?><div class="empty"><div class="empty-mark">⌁</div><h3>No submissions yet</h3><p><?= $user['role']==='teacher'?'When students submit requirements, they will appear here for review.':'Submit your first requirement using the form above.' ?></p></div><?php else: ?><div class="table-scroll"><table><thead><tr><?php if($user['role']==='teacher'): ?><th>Student</th><th>Student ID</th><?php endif; ?><th>Requirement</th><th>File</th><th>Submitted</th><th>Status</th><th>Teacher remarks</th><?php if($user['role']==='teacher'): ?><th>Review</th><?php endif; ?></tr></thead><tbody><?php foreach($rows as $r): ?><tr><?php if($user['role']==='teacher'): ?><td><strong><?=h($r['full_name'] ?? '')?></strong><small><?=h($r['email'] ?? '')?></small></td><td><?=h($r['student_id']?:'—')?></td><?php endif; ?><td><strong><?=h($r['requirement_name'])?></strong><?php if(!empty($r['description'])): ?><small><?=h($r['description'])?></small><?php endif; ?></td><td><a href="download.php?id=<?=h($r['id'])?>"><?=h($r['original_filename'])?></a><small><?=number_format(($r['file_size']??0)/1024,1)?> KB</small></td><td><?=h(date('M j, Y g:i A',strtotime($r['created_at'])))?></td><td><span class="status <?=h($r['status'])?>"><?=h(ucfirst($r['status']))?></span></td><td><?=h($r['teacher_remarks']?:'—')?></td><?php if($user['role']==='teacher'): ?><td><?php if($r['status']!=='approved'): ?><form class="review-form" method="post" action="review.php"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="id" value="<?=h($r['id'])?>"><textarea name="remarks" rows="2" maxlength="2000" placeholder="Remarks (required to return)"><?=h($r['teacher_remarks']??'')?></textarea><div class="review-actions"><button class="btn tiny success" name="decision" value="approved">Approve</button><button class="btn tiny warning" name="decision" value="returned">Return</button></div></form><?php else: ?><small>Reviewed <?=h(!empty($r['reviewed_at'])?date('M j, Y',strtotime($r['reviewed_at'])):'')?></small><?php endif; ?></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section><footer>Student Record System · Secure account access · Real submissions only</footer></main></body></html>
