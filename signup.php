<?php
require __DIR__ . '/config.php';
if (current_user()) { header('Location: dashboard.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 $name=trim($_POST['full_name']??''); $student_id=trim($_POST['student_id']??''); $email=strtolower(trim($_POST['email']??'')); $username=trim($_POST['username']??''); $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??'';
 if (mb_strlen($name)<2 || mb_strlen($name)>120) $error='Enter your full name.';
 elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Enter a valid email address.';
 elseif (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/',$username)) $error='Username must be 3–60 characters and use letters, numbers, dots, underscores or hyphens.';
 elseif (strlen($password)<8) $error='Password must be at least 8 characters.';
 elseif ($password!==$confirm) $error='Passwords do not match.';
 else {
  try {
    $existing = fs_query('users');
    foreach ($existing as $u) {
        if (($u['username'] ?? '') === $username || ($u['email'] ?? '') === $email || ($student_id !== '' && ($u['student_id'] ?? '') === $student_id)) {
            $error = 'That username, email, or student ID is already registered.';
            break;
        }
    }
    if (!$error) {
        fs_insert('users', [
            'full_name' => $name,
            'student_id' => $student_id !== '' ? $student_id : null,
            'email' => $email,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'student',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        flash('Account created. You can now sign in.'); header('Location: index.php'); exit;
    }
  } catch(Throwable $e) { $error = 'Could not create the account. Check Firebase configuration and try again.'; }
 }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create Student Account</title><link rel="stylesheet" href="style.css"></head><body class="auth-body"><main class="auth-wrap"><section class="auth-brand"><div class="crest">ER</div><p class="eyebrow">EL ROYALE HOTELIER TRAINING CENTER INC.</p><h1>Create your account</h1><p>Register as a student to submit requirements and track teacher reviews.</p><div class="auth-points"><span>✓ Your own private account</span><span>✓ Your uploads saved securely</span><span>✓ No sample students or fake approvals</span></div></section><section class="auth-card"><h2>Student registration</h2><p class="muted">All fields marked with * are required.</p><?php if ($error): ?><div class="alert error"><?=h($error)?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><label>Full name *</label><input name="full_name" required maxlength="120" value="<?=h($_POST['full_name']??'')?>" autocomplete="name"><label>Student ID (optional)</label><input name="student_id" maxlength="60" value="<?=h($_POST['student_id']??'')?>"><label>Email *</label><input type="email" name="email" required maxlength="190" value="<?=h($_POST['email']??'')?>" autocomplete="email"><label>Username *</label><input name="username" required minlength="3" maxlength="60" pattern="[A-Za-z0-9_.-]+" value="<?=h($_POST['username']??'')?>" autocomplete="username"><label>Password * (minimum 8 characters)</label><input type="password" name="password" required minlength="8" autocomplete="new-password"><label>Confirm password *</label><input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"><button class="btn primary full" type="submit">Create account</button></form><div class="auth-bottom">Already registered? <a href="index.php">Sign in</a></div></section></main></body></html>
