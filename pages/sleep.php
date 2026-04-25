<?php

require_once '../includes/auth_check.php';
require_once '../db.php';

$active_page = 'sleep';
$errors = [];
$edit_item = null;

// Mood config
$moods = [
    'Great' => ['emoji' => '😄', 'color' => '#10b981', 'bg' => '#d1fae5'],
    'Good' => ['emoji' => '🙂', 'color' => '#06b6d4', 'bg' => '#cffafe'],
    'Okay' => ['emoji' => '😐', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'Tired' => ['emoji' => '😴', 'color' => '#8b5cf6', 'bg' => '#ede9fe'],
    'Exhausted' => ['emoji' => '😫', 'color' => '#ef4444', 'bg' => '#fee2e2'],
];

// Handle DELETE
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM sleep_log WHERE id = ? AND user_id = ?");
    $stmt->execute([$del_id, $current_user_id]);
    header('Location: sleep.php?msg=deleted');
    exit;
}

// Load log for editing
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM sleep_log WHERE id = ? AND user_id = ?");
    $stmt->execute([$edit_id, $current_user_id]);
    $edit_item = $stmt->fetch();
}

require_once '../includes/header.php';
?>

<!-- Flash messages -->
<?php if (isset($_GET['msg'])): ?>
    <?php $msgs = [
        'added' => ['success', 'Sleep log saved!'],
        'updated' => ['success', 'Sleep log updated!'],
        'deleted' => ['danger', 'Log entry deleted.'],
    ]; ?>
    <?php if (isset($msgs[$_GET['msg']])):
        [$type, $text] = $msgs[$_GET['msg']]; ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show custom-alert" role="alert">
            <i class="bi bi-<?= $type === 'success' ? 'check-circle' : 'trash3' ?> me-2"></i>
            <?= $text ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="page-title mb-1">Sleep & Routine</h4>
        <p class="text-muted mb-0" style="font-size:0.85rem;">Track your rest and daily energy levels.</p>
    </div>
    <button class="btn-dash-action" id="toggleSleepBtn">
        <i class="bi bi-plus-lg"></i> Log Sleep
    </button>
</div>

<!-- ADD / EDIT FORM -->
<div class="form-panel <?= ($edit_item || !empty($errors)) ? 'open' : '' ?>" id="sleepForm">
  <div class="form-panel-inner">
    <h6 class="form-panel-title">
      <i class="bi bi-<?= isset($edit_item['id']) && $edit_item['id'] ? 'pencil' : 'moon-stars' ?> me-2"></i>
      <?= isset($edit_item['id']) && $edit_item['id'] ? 'Edit Sleep Log' : 'Log Your Sleep' ?>
    </h6>
 
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger py-2 mb-3">
        <?php foreach ($errors as $e): ?>
          <div><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
 
    <form method="POST" action="sleep.php">
      <input type="hidden" name="edit_id" value="<?= $edit_item['id'] ?? 0 ?>"/>
 
      <div class="row g-3">
 
        <!-- Date -->
        <div class="col-sm-4">
          <label class="field-label">Date *</label>
          <input type="date" name="log_date" class="field-input"
                 value="<?= htmlspecialchars($edit_item['log_date'] ?? date('Y-m-d')) ?>"
                 max="<?= date('Y-m-d') ?>" required/>
        </div>
 
        <!-- Sleep time -->
        <div class="col-sm-4">
          <label class="field-label">Went to Bed *</label>
          <input type="time" name="sleep_time" class="field-input"
                 id="sleepTimeInput"
                 value="<?= htmlspecialchars($edit_item['sleep_time'] ?? '23:00') ?>" required/>
          <small class="text-muted">e.g. 11:00 PM</small>
        </div>
 
        <!-- Wake time -->
        <div class="col-sm-4">
          <label class="field-label">Woke Up *</label>
          <input type="time" name="wake_time" class="field-input"
                 id="wakeTimeInput"
                 value="<?= htmlspecialchars($edit_item['wake_time'] ?? '07:00') ?>" required/>
          <small class="text-muted">e.g. 7:00 AM</small>
        </div>
 
        <!-- Live hours preview -->
        <div class="col-12">
          <div class="hours-preview" id="hoursPreview">
            <i class="bi bi-moon-stars"></i>
            <span id="hoursText">Set times above to see your sleep duration</span>
          </div>
        </div>
 
        <!-- Mood picker -->
        <div class="col-12">
          <label class="field-label">How did you feel when you woke up?</label>
          <div class="mood-picker">
            <?php foreach ($moods as $mood_val => $cfg): ?>
              <label class="mood-option">
                <input type="radio" name="mood" value="<?= $mood_val ?>"
                       <?= ($edit_item['mood'] ?? 'Good') === $mood_val ? 'checked' : '' ?>/>
                <span class="mood-pill"
                      style="--mood-color:<?= $cfg['color'] ?>;--mood-bg:<?= $cfg['bg'] ?>">
                  <span class="mood-emoji"><?= $cfg['emoji'] ?></span>
                  <span class="mood-label"><?= $mood_val ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
 
        <!-- Notes -->
        <div class="col-12">
          <label class="field-label">Notes <span class="text-muted fw-normal" style="text-transform:none">(optional)</span></label>
          <input type="text" name="notes" class="field-input"
                 placeholder="e.g. Had trouble falling asleep, drank coffee late…"
                 value="<?= htmlspecialchars($edit_item['notes'] ?? '') ?>"/>
        </div>
 
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn-submit-sm">
            <i class="bi bi-<?= isset($edit_item['id']) && $edit_item['id'] ? 'check-lg' : 'moon-stars' ?> me-1"></i>
            <?= isset($edit_item['id']) && $edit_item['id'] ? 'Save Changes' : 'Save Log' ?>
          </button>
          <a href="sleep.php" class="btn-cancel-sm">Cancel</a>
        </div>
 
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>