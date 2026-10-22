<?php
require __DIR__ . '/config.php';
if (current_user()) { header('Location: dashboard.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 $name=trim($_POST['full_name']??''); $email=strtolower(trim($_POST['email']??'')); $username=trim($_POST['username']??''); $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??''; $code=$_POST['teacher_code']??'';
 if (TEACHER_SIGNUP_CODE==='CHANGE-THIS-TEACHER-CODE') $error='Teacher registration is not configured yet. Ask the administrator to set TEACHER_SIGNUP_CODE in config.php.';
 elseif (!hash_equals(TEACHER_SIGNUP_CODE, $code)) $error='The teacher access code is incorrect. Ask your administrator for the correct code.';
 elseif (mb_strlen($name)<2 || mb_strlen($name)>120) $error='Enter your full name.';
 elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Enter a valid email address.';
 elseif (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/',$username)) $error='Username must be 3–60 characters and use letters, numbers, dots, underscores or hyphens.';
 elseif (strlen($password)<8) $error='Password must be at least 8 characters.';
 elseif ($password!==$confirm) $error='Passwords do not match.';
 else {
  try {
    $existing = fs_query('users');
    foreach ($existing as $u) {
        if (($u['username'] ?? '') === $username || ($u['email'] ?? '') === $email) {
            $error = 'That username or email is already registered.';
            break;
        }
    }
    if (!$error) {
        fs_insert('users', [
            'full_name' => $name,
            'student_id' => null,
            'email' => $email,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'teacher',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        flash('Teacher account created. Select Teacher on the sign-in page to log in.'); header('Location: index.php'); exit;
    }
  } catch(Throwable $e) { $error = 'Could not create the account. Check Firebase configuration and try again.'; }
 }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create Teacher Account</title><link rel="stylesheet" href="style.css"></head><body class="auth-body"><main class="auth-wrap"><section class="auth-brand"><div class="crest" aria-hidden="true">ER</div><p class="eyebrow">EL ROYALE HOTELIER TRAINING CENTER INC.</p><h1>Teacher registration</h1><p>Create a teacher account to review student documents, download requirements, approve submissions, or return them with remarks.</p><div class="auth-points"><span><b class="point-check" aria-hidden="true">✓</b> View student submissions</span><span><b class="point-check" aria-hidden="true">✓</b> Download submitted files</span><span><b class="point-check" aria-hidden="true">✓</b> Approve or return with remarks</span></div></section><section class="auth-card"><h2>Create teacher account</h2><p class="muted">A private teacher access code is required.</p><?php if ($error): ?><div class="alert error" role="alert"><?=h($error)?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><label for="full_name">Full name *</label><input id="full_name" name="full_name" required maxlength="120" autocomplete="name" value="<?=h($_POST['full_name']??'')?>"><label for="email">Work email *</label><input id="email" type="email" name="email" required maxlength="190" autocomplete="email" value="<?=h($_POST['email']??'')?>"><label for="username">Username *</label><input id="username" name="username" required minlength="3" maxlength="60" pattern="[A-Za-z0-9_.-]+" autocomplete="username" value="<?=h($_POST['username']??'')?>"><label for="teacher_code">Teacher access code *</label><input id="teacher_code" name="teacher_code" type="password" required autocomplete="off"><label for="password">Password * (minimum 8 characters)</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"><label for="confirm_password">Confirm password *</label><input id="confirm_password" type="password" name="confirm_password" required minlength="8" autocomplete="new-password"><button class="btn primary full" type="submit">Create teacher account</button></form><div class="auth-bottom">Already registered? <a href="index.php">Sign in</a></div><p class="small muted">Keep the access code private. Do not share it with students.</p></section></main></body></html>
