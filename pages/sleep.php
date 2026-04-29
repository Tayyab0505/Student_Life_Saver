<?php
require_once '../includes/auth_check.php';
require_once '../db.php';

$active_page = 'sleep';
$errors = [];
$edit_item = null;

//  Mood config
$moods = [
  'Great' => ['emoji' => '😄', 'color' => '#10b981', 'bg' => '#d1fae5'],
  'Good' => ['emoji' => '🙂', 'color' => '#06b6d4', 'bg' => '#cffafe'],
  'Okay' => ['emoji' => '😐', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
  'Tired' => ['emoji' => '😴', 'color' => '#8b5cf6', 'bg' => '#ede9fe'],
  'Exhausted' => ['emoji' => '😫', 'color' => '#ef4444', 'bg' => '#fee2e2'],
];

//  Handle DELETE
if (isset($_GET['delete'])) {
  $del_id = (int) $_GET['delete'];
  $stmt = $pdo->prepare("DELETE FROM sleep_log WHERE id = ? AND user_id = ?");
  $stmt->execute([$del_id, $current_user_id]);
  header('Location: sleep.php?msg=deleted');
  exit;
}

//  Load log for editing 
if (isset($_GET['edit'])) {
  $edit_id = (int) $_GET['edit'];
  $stmt = $pdo->prepare("SELECT * FROM sleep_log WHERE id = ? AND user_id = ?");
  $stmt->execute([$edit_id, $current_user_id]);
  $edit_item = $stmt->fetch();
}

//  Handle ADD / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $log_date = $_POST['log_date'] ?? '';
  $sleep_time = $_POST['sleep_time'] ?? '';
  $wake_time = $_POST['wake_time'] ?? '';
  $mood = $_POST['mood'] ?? 'Good';
  $notes = trim($_POST['notes'] ?? '');
  $post_id = (int) ($_POST['edit_id'] ?? 0);

  // Validation
  if ($log_date === '') {
    $errors[] = 'Please select the date.';
  }
  if ($sleep_time === '') {
    $errors[] = 'Sleep time is required.';
  }
  if ($wake_time === '') {
    $errors[] = 'Wake time is required.';
  }
  if (!array_key_exists($mood, $moods)) {
    $errors[] = 'Invalid mood selected.';
  }

  //  Calculate hours slept
  $hours_slept = null;
  if ($sleep_time && $wake_time) {

    // Split on ':' — take only hours & minutes, ignore seconds
    $sleep_parts = explode(':', $sleep_time);
    $wake_parts = explode(':', $wake_time);

    $sh = (int) ($sleep_parts[0] ?? 0);
    $sm = (int) ($sleep_parts[1] ?? 0);
    $wh = (int) ($wake_parts[0] ?? 0);
    $wm = (int) ($wake_parts[1] ?? 0);

    $sleep_mins = $sh * 60 + $sm;
    $wake_mins = $wh * 60 + $wm;

    if ($wake_mins < $sleep_mins) {
      $wake_mins += 24 * 60;
    }

    if ($wake_mins === $sleep_mins) {
      $errors[] = 'Sleep time and wake time cannot be the same.';
    } else {
      $hours_slept = round(($wake_mins - $sleep_mins) / 60, 2);
      if ($hours_slept > 20) {
        $errors[] = 'Calculated ' . $hours_slept . 'h seems too long. Check your times.';
        $hours_slept = null;
      } elseif ($hours_slept <= 0) {
        $errors[] = 'Could not calculate sleep hours. Please check your times.';
        $hours_slept = null;
      }
    }
  }

  if (empty($errors)) {
    if ($post_id > 0) {
      // UPDATE — replace existing log
      $stmt = $pdo->prepare("UPDATE sleep_log SET log_date=?, sleep_time=?, wake_time=?, hours_slept=?, mood=?, notes=? WHERE id=? AND user_id=?");
      $stmt->execute([$log_date, $sleep_time, $wake_time, $hours_slept, $mood, $notes, $post_id, $current_user_id]);
      header('Location: sleep.php?msg=updated');
    } else {
      $stmt = $pdo->prepare("INSERT INTO sleep_log (user_id,log_date,sleep_time,wake_time,hours_slept,mood,notes) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE sleep_time=VALUES(sleep_time), wake_time=VALUES(wake_time), hours_slept=VALUES(hours_slept), mood=VALUES(mood), notes=VALUES(notes)");
      $stmt->execute([$current_user_id, $log_date, $sleep_time, $wake_time, $hours_slept, $mood, $notes]);
      header('Location: sleep.php?msg=added');
    }
    exit;
  }

  $edit_item = compact('log_date', 'sleep_time', 'wake_time', 'mood', 'notes') + ['id' => $post_id];
}

//  Last 7 days data for chart 
$stmt = $pdo->prepare("
    SELECT log_date, hours_slept, mood
    FROM sleep_log
    WHERE user_id = ?
      AND log_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    ORDER BY log_date ASC
");
$stmt->execute([$current_user_id]);
$week_data = $stmt->fetchAll();

// Build chart-ready arrays
$chart_labels = [];
$chart_hours = [];
$chart_moods = [];
for ($i = 6; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-$i days"));
  $chart_labels[] = date('D', strtotime($d));
  // Find this date in week_data
  $found = null;
  foreach ($week_data as $row) {
    if ($row['log_date'] === $d) {
      $found = $row;
      break;
    }
  }
  $chart_hours[] = $found ? (float) $found['hours_slept'] : null;
  $chart_moods[] = $found ? $found['mood'] : null;
}

// Stats
$stmt = $pdo->prepare("SELECT AVG(hours_slept) FROM sleep_log WHERE user_id=? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stmt->execute([$current_user_id]);
$avg_sleep_7 = round((float) $stmt->fetchColumn(), 1);

$stmt = $pdo->prepare("SELECT AVG(hours_slept) FROM sleep_log WHERE user_id=?");
$stmt->execute([$current_user_id]);
$avg_sleep_all = round((float) $stmt->fetchColumn(), 1);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM sleep_log WHERE user_id=?");
$stmt->execute([$current_user_id]);
$total_logs = (int) $stmt->fetchColumn();

// Most common mood
$stmt = $pdo->prepare("SELECT mood, COUNT(*) as c FROM sleep_log WHERE user_id=? GROUP BY mood ORDER BY c DESC LIMIT 1");
$stmt->execute([$current_user_id]);
$top_mood_row = $stmt->fetch();
$top_mood = $top_mood_row ? $top_mood_row['mood'] : null;

// Full log history 
$stmt = $pdo->prepare("SELECT * FROM sleep_log WHERE user_id=? ORDER BY log_date DESC LIMIT 30");
$stmt->execute([$current_user_id]);
$logs = $stmt->fetchAll();

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
      <i class="bi bi-<?= $type === 'success' ? 'check-circle' : 'trash3' ?> me-2"></i><?= $text ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
<?php endif; ?>

<!--  Page header  -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h4 class="page-title mb-1">Sleep & Routine</h4>
    <p class="text-muted mb-0" style="font-size:0.85rem;">Track your rest and daily energy levels.</p>
  </div>
  <button class="btn-dash-action" id="toggleSleepBtn">
    <i class="bi bi-plus-lg"></i> Log Sleep
  </button>
</div>

<!--  ADD / EDIT FORM  -->
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
      <input type="hidden" name="edit_id" value="<?= $edit_item['id'] ?? 0 ?>" />

      <div class="row g-3">

        <!-- Date -->
        <div class="col-sm-4">
          <label class="field-label">Date *</label>
          <input type="date" name="log_date" class="field-input"
            value="<?= htmlspecialchars($edit_item['log_date'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>"
            required />
        </div>

        <!-- Sleep time -->
        <div class="col-sm-4">
          <label class="field-label">Went to Bed *</label>
          <input type="time" name="sleep_time" class="field-input" id="sleepTimeInput"
            value="<?= htmlspecialchars(substr($edit_item['sleep_time'] ?? '23:00', 0, 5)) ?>" required />
          <small class="text-muted">e.g. 11:00 PM</small>
        </div>

        <!-- Wake time -->
        <div class="col-sm-4">
          <label class="field-label">Woke Up *</label>
          <input type="time" name="wake_time" class="field-input" id="wakeTimeInput"
            value="<?= htmlspecialchars(substr($edit_item['wake_time'] ?? '07:00', 0, 5)) ?>" required />
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
                <input type="radio" name="mood" value="<?= $mood_val ?>" <?= ($edit_item['mood'] ?? 'Good') === $mood_val ? 'checked' : '' ?> />
                <span class="mood-pill" style="--mood-color:<?= $cfg['color'] ?>;--mood-bg:<?= $cfg['bg'] ?>">
                  <span class="mood-emoji"><?= $cfg['emoji'] ?></span>
                  <span class="mood-label"><?= $mood_val ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Notes -->
        <div class="col-12">
          <label class="field-label">Notes <span class="text-muted fw-normal"
              style="text-transform:none">(optional)</span></label>
          <input type="text" name="notes" class="field-input"
            placeholder="e.g. Had trouble falling asleep, drank coffee late…"
            value="<?= htmlspecialchars($edit_item['notes'] ?? '') ?>" />
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

<!--  STATS ROW  -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="sleep-stat-card" style="border-top:3px solid #4f46e5">
      <div class="sleep-stat-icon" style="background:#eef2ff;color:#4f46e5">
        <i class="bi bi-moon-stars-fill"></i>
      </div>
      <div class="sleep-stat-value"><?= $avg_sleep_7 ?: '—' ?>h</div>
      <div class="sleep-stat-label">Avg Sleep (7 days)</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sleep-stat-card" style="border-top:3px solid #10b981">
      <div class="sleep-stat-icon" style="background:#d1fae5;color:#10b981">
        <i class="bi bi-graph-up"></i>
      </div>
      <div class="sleep-stat-value"><?= $avg_sleep_all ?: '—' ?>h</div>
      <div class="sleep-stat-label">Overall Average</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sleep-stat-card" style="border-top:3px solid #f59e0b">
      <div class="sleep-stat-icon" style="background:#fef3c7;color:#f59e0b">
        <i class="bi bi-journal-text"></i>
      </div>
      <div class="sleep-stat-value"><?= $total_logs ?></div>
      <div class="sleep-stat-label">Days Logged</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sleep-stat-card" style="border-top:3px solid #ec4899">
      <div class="sleep-stat-icon" style="background:#fce7f3;color:#ec4899">
        <i class="bi bi-emoji-smile"></i>
      </div>
      <div class="sleep-stat-value" style="font-size:1.5rem">
        <?= $top_mood ? ($moods[$top_mood]['emoji'] ?? '—') : '—' ?>
      </div>
      <div class="sleep-stat-label">Top Mood: <?= $top_mood ?? 'N/A' ?></div>
    </div>
  </div>
</div>

<!--  7-DAY CHART  -->
<?php if ($total_logs > 0): ?>
  <div class="sleep-chart-card mb-4">
    <div class="sleep-chart-header">
      <span><i class="bi bi-bar-chart-line me-2" style="color:var(--primary)"></i>Last 7 Days Sleep</span>
      <span class="text-muted" style="font-size:0.78rem">Recommended: 7–9 hours</span>
    </div>
    <div class="sleep-chart-body">
      <canvas id="sleepChart" height="100"></canvas>
    </div>
  </div>
<?php endif; ?>

<!--  SLEEP LOG HISTORY -->
<div class="sleep-chart-card">
  <div class="sleep-chart-header">
    <span><i class="bi bi-clock-history me-2" style="color:var(--primary)"></i>Sleep History</span>
    <span class="text-muted" style="font-size:0.78rem">Last 30 entries</span>
  </div>

  <?php if (empty($logs)): ?>
    <div class="empty-state-page" style="padding:3rem 1rem">
      <i class="bi bi-moon"></i>
      <h5>No sleep logs yet</h5>
      <p>Click "Log Sleep" above to start tracking your rest.</p>
    </div>
  <?php else: ?>
    <div class="sleep-log-list">
      <?php foreach ($logs as $log):
        $mood_cfg = $moods[$log['mood']] ?? $moods['Good'];
        $hours = (float) $log['hours_slept'];
        $quality = $hours >= 7 ? 'good' : ($hours >= 5 ? 'okay' : 'poor');
        $quality_colors = [
          'good' => ['bar' => '#10b981', 'bg' => '#d1fae5', 'text' => '#065f46'],
          'okay' => ['bar' => '#f59e0b', 'bg' => '#fef3c7', 'text' => '#92400e'],
          'poor' => ['bar' => '#ef4444', 'bg' => '#fee2e2', 'text' => '#991b1b'],
        ];
        $qc = $quality_colors[$quality];
        ?>
        <div class="sleep-log-row">

          <!-- Date block -->
          <div class="sleep-log-date">
            <div class="sleep-log-day"><?= date('D', strtotime($log['log_date'])) ?></div>
            <div class="sleep-log-dnum"><?= date('j', strtotime($log['log_date'])) ?></div>
            <div class="sleep-log-month"><?= date('M', strtotime($log['log_date'])) ?></div>
          </div>

          <!-- Times -->
          <div class="sleep-log-times">
            <div class="sleep-time-row">
              <i class="bi bi-moon-fill" style="color:#6366f1;font-size:0.75rem"></i>
              <?= date('g:i A', strtotime($log['sleep_time'])) ?>
            </div>
            <div class="sleep-time-arrow"><i class="bi bi-arrow-down"></i></div>
            <div class="sleep-time-row">
              <i class="bi bi-sun-fill" style="color:#f59e0b;font-size:0.75rem"></i>
              <?= date('g:i A', strtotime($log['wake_time'])) ?>
            </div>
          </div>

          <!-- Hours + quality bar -->
          <div class="sleep-log-hours">
            <div class="sleep-hours-badge" style="background:<?= $qc['bg'] ?>;color:<?= $qc['text'] ?>">
              <?= number_format($hours, 1) ?>h
            </div>
            <div class="sleep-quality-bar-track">
              <!-- bar width = hours / 10 * 100, capped at 100% -->
              <div class="sleep-quality-bar-fill"
                style="width:<?= min(100, round(($hours / 10) * 100)) ?>%;background:<?= $qc['bar'] ?>">
              </div>
            </div>
            <div class="sleep-quality-label" style="color:<?= $qc['text'] ?>">
              <?= ucfirst($quality) ?> sleep
            </div>
          </div>

          <!-- Mood -->
          <div class="sleep-log-mood">
            <span class="mood-tag" style="background:<?= $mood_cfg['bg'] ?>;color:<?= $mood_cfg['color'] ?>">
              <?= $mood_cfg['emoji'] ?>     <?= $log['mood'] ?>
            </span>
            <?php if ($log['notes']): ?>
              <div class="sleep-log-note" title="<?= htmlspecialchars($log['notes']) ?>">
                <i class="bi bi-chat-left-text"></i>
                <?= htmlspecialchars(mb_substr($log['notes'], 0, 35)) ?>       <?= strlen($log['notes']) > 35 ? '…' : '' ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Actions -->
          <div class="sleep-log-actions">
            <a href="sleep.php?edit=<?= $log['id'] ?>" class="icon-btn btn-edit" title="Edit">
              <i class="bi bi-pencil"></i>
            </a>
            <a href="sleep.php?delete=<?= $log['id'] ?>" class="icon-btn btn-delete" title="Delete"
              onclick="return confirm('Delete this sleep log?')">
              <i class="bi bi-trash3"></i>
            </a>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Chart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
  //  Live hours calculator
  function calcHours() {
    const sleepVal = document.getElementById('sleepTimeInput').value;
    const wakeVal = document.getElementById('wakeTimeInput').value;
    const preview = document.getElementById('hoursText');

    if (!sleepVal || !wakeVal) return;

    const [sh, sm] = sleepVal.split(':').map(Number);
    const [wh, wm] = wakeVal.split(':').map(Number);
    let sleepMins = sh * 60 + sm;
    let wakeMins = wh * 60 + wm;
    if (wakeMins <= sleepMins) wakeMins += 24 * 60;  // overnight

    const total = (wakeMins - sleepMins) / 60;
    const hrs = Math.floor(total);
    const mins = Math.round((total - hrs) * 60);

    // Color feedback
    const box = document.getElementById('hoursPreview');
    box.className = 'hours-preview ' + (total >= 7 ? 'hours-good' : total >= 5 ? 'hours-okay' : 'hours-poor');
    preview.textContent = `${hrs}h ${mins}m of sleep  ${total >= 7 ? '✅ Great!' : total >= 5 ? '⚠️ A bit short' : '❌ Too little'}`;
  }

  document.getElementById('sleepTimeInput')?.addEventListener('change', calcHours);
  document.getElementById('wakeTimeInput')?.addEventListener('change', calcHours);
  // Run on load if editing
  calcHours();

  //  7-Day Sleep Chart
  <?php if ($total_logs > 0): ?>
    const ctx = document.getElementById('sleepChart').getContext('2d');

    // Data from PHP — null values show as gaps in the chart
    const labels = <?= json_encode($chart_labels) ?>;
    const hours = <?= json_encode($chart_hours) ?>;

    // Color each bar based on hours
    const barColors = hours.map(h =>
      h === null ? 'rgba(0,0,0,0.05)'
        : h >= 7 ? 'rgba(16,185,129,0.75)'
          : h >= 5 ? 'rgba(245,158,11,0.75)'
            : 'rgba(239,68,68,0.75)'
    );

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Hours Slept',
          data: hours,
          backgroundColor: barColors,
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ctx.raw !== null ? `${ctx.raw}h sleep` : 'No log'
            }
          }
        },
        scales: {
          y: {
            min: 0, max: 12,
            ticks: { stepSize: 2, callback: v => v + 'h' },
            grid: { color: 'rgba(0,0,0,0.05)' }
          },
          x: { grid: { display: false } }
        }
      }
    });
  <?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>