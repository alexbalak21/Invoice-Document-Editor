<?php
use App\Core\Csrf;
use App\Services\UserService;

// Show validation errors only under the form that was submitted
$pe = $section === 'profile'  ? $errors : [];
$we = $section === 'password' ? $errors : [];
$fieldError = fn(array $errs, string $key) =>
    isset($errs[$key]) ? '<div class="field-error">' . e($errs[$key]) . '</div>' : '';
$name  = $old['name']  ?? $user->name;
$email = $old['email'] ?? $user->email;
?>
<style>html,body{height:auto;overflow:auto}</style>

<div class="history-layout profile-layout">
  <div class="history-topbar"><h1>Your profile</h1></div>

  <?php if ($success): ?>
    <div class="alert alert-success" role="status"><?= e($success) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/profile" class="item-form-card" autocomplete="off">
    <?= Csrf::field() ?>
    <h2><i class="fa-solid fa-user"></i> Account details</h2>

    <div class="profile-grid">
      <div class="field">
        <label for="p-name">Name</label>
        <input id="p-name" type="text" name="name" value="<?= e($name) ?>" required maxlength="100" autocomplete="name">
        <?= $fieldError($pe, 'name') ?>
      </div>
      <div class="field">
        <label for="p-email">Email (used to sign in)</label>
        <input id="p-email" type="email" name="email" value="<?= e($email) ?>" required maxlength="190" autocomplete="email">
        <?= $fieldError($pe, 'email') ?>
      </div>
      <div class="field full">
        <label for="p-cur">Current password <span class="muted">— only needed when you change your email</span></label>
        <input id="p-cur" type="password" name="current_password" autocomplete="current-password">
        <?= $fieldError($pe, 'current_password') ?>
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
  </form>

  <form method="post" action="<?= BASE_URL ?>/profile/password" class="item-form-card" autocomplete="off">
    <?= Csrf::field() ?>
    <h2><i class="fa-solid fa-key"></i> Change password</h2>

    <div class="profile-grid">
      <div class="field full">
        <label for="w-cur">Current password</label>
        <input id="w-cur" type="password" name="current_password" required autocomplete="current-password">
        <?= $fieldError($we, 'current_password') ?>
      </div>
      <div class="field">
        <label for="w-new">New password <span class="muted">— <?= UserService::PASSWORD_MIN ?> to <?= UserService::PASSWORD_MAX ?> characters</span></label>
        <input id="w-new" type="password" name="new_password" required minlength="<?= UserService::PASSWORD_MIN ?>" maxlength="<?= UserService::PASSWORD_MAX ?>" autocomplete="new-password">
        <?= $fieldError($we, 'new_password') ?>
      </div>
      <div class="field">
        <label for="w-conf">Repeat new password</label>
        <input id="w-conf" type="password" name="confirm_password" required autocomplete="new-password">
        <?= $fieldError($we, 'confirm_password') ?>
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-key"></i> Change password</button>
    <p class="profile-meta">Changing your password signs you out on every other device.</p>
  </form>

  <p class="profile-meta">Last sign-in: <?= e($user->lastLoginAt ?? '—') ?></p>
</div>
