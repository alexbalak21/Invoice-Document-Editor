<?php
use App\Core\Auth;
use App\Core\Csrf;

$__u       = Auth::user();
$__compact = !empty($compactUserMenu);
?>
<?php if ($__u): ?>
  <div class="topbar-user<?= !empty($afterActions) ? ' after-actions' : '' ?>">
    <a href="<?= BASE_URL ?>/profile" class="topbar-user-link" title="<?= e($__u->email) ?> — your profile">
      <i class="fa-solid fa-circle-user"></i><?php if (!$__compact): ?> <span><?= e($__u->name) ?></span><?php endif; ?>
    </a>
    <form method="post" action="<?= BASE_URL ?>/logout">
      <?= Csrf::field() ?>
      <button type="submit" class="btn btn-ghost btn-sm" title="Log out">
        <i class="fa-solid fa-right-from-bracket"></i><?php if (!$__compact): ?> Log out<?php endif; ?>
      </button>
    </form>
  </div>
<?php endif; ?>
