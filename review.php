<?php
require __DIR__ . '/config.php';
$teacher=require_role('teacher');
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: dashboard.php'); exit; }
check_csrf();
$id=$_POST['id']??''; $decision=$_POST['decision']??''; $remarks=trim($_POST['remarks']??'');
if (!$id || !in_array($decision,['approved','returned'],true)) { flash('Invalid review request.','error'); header('Location: dashboard.php'); exit; }
if ($decision==='returned' && $remarks==='') { flash('Add a remark explaining what the student needs to fix before returning the submission.','error'); header('Location: dashboard.php'); exit; }

$sub = fs_get('submissions', $id);
if (!$sub) { flash('Submission not found.','error'); header('Location: dashboard.php'); exit; }

$success = fs_update('submissions', $id, [
    'status' => $decision,
    'teacher_remarks' => $remarks !== '' ? $remarks : null,
    'reviewed_by' => $teacher['id'],
    'reviewed_at' => date('Y-m-d H:i:s')
]);

flash($success ? 'Submission review saved.' : 'Could not save review.', $success ? 'success' : 'error');
header('Location: dashboard.php'); 
exit;
