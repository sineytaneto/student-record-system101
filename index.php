<?php
require __DIR__ . '/config.php';
if (current_user()) { header('Location: dashboard.php'); exit; }
$error = '';
$selectedRole = ($_POST['role'] ?? 'student') === 'teacher' ? 'teacher' : 'student';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $login = trim($_POST['login'] ?? ''); $password = $_POST['password'] ?? '';
  try {
    $users = fs_query('users', 'role', '=', $selectedRole);
    $foundUser = null;
    foreach ($users as $u) {
      if (($u['username'] === $login || $u['email'] === $login) && $u['role'] === $selectedRole) {
        $foundUser = $u;
        break;
      }
    }

    if ($foundUser && password_verify($password, $foundUser['password_hash'])) {
      session_regenerate_id(true);
      unset($foundUser['password_hash']); $_SESSION['user'] = $foundUser;
      header('Location: dashboard.php'); exit;
    }
    $error = 'The username/email, password, or selected account type is incorrect.';
  } catch (Throwable $e) { $error = 'Database connection failed. Check your Firebase Project ID in config.php.'; }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Record System | Sign in</title><link rel="stylesheet" href="style.css"></head><body class="auth-body"><main class="auth-wrap"><section class="auth-brand"><div class="crest" aria-hidden="true">ER</div><p class="eyebrow">EL ROYALE HOTELIER TRAINING CENTER INC.</p><h1>Student Record System</h1><p>Online requirement submission and approval tracking.</p><div class="auth-points"><span><b class="point-check" aria-hidden="true">✓</b> Submit requirements online</span><span><b class="point-check" aria-hidden="true">✓</b> Track review status</span><span><b class="point-check" aria-hidden="true">✓</b> Receive teacher remarks</span></div></section><section class="auth-card"><h2>Welcome back</h2><p class="muted">Choose your account type and sign in.</p><?php if ($error): ?><div class="alert error" role="alert"><?=h($error)?></div><?php endif; ?><?php if ($f=take_flash()): ?><div class="alert <?=h($f['type'])?>"><?=h($f['message'])?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><label>Sign in as</label><div class="role-choice"><label class="role-option"><input type="radio" name="role" value="student" <?=$selectedRole==='student'?'checked':''?>><span>Student</span></label><label class="role-option"><input type="radio" name="role" value="teacher" <?=$selectedRole==='teacher'?'checked':''?>><span>Teacher</span></label></div><label for="login">Username or email</label><input id="login" name="login" required autocomplete="username" placeholder="Enter username or email" value="<?=h($_POST['login']??'')?>"><label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter password"><button class="btn primary full" type="submit">Sign in</button></form><div class="auth-bottom">Student? <a href="signup.php">Create student account</a></div><div class="auth-bottom teacher-link">Teacher? <a href="teacher_signup.php">Create teacher account</a></div><p class="small muted">Teacher registration requires the private access code provided by your system administrator.</p></section></main></body></html>
