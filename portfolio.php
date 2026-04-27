<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$username = trim($_GET['u'] ?? '');

if (!$username || !preg_match('/^[a-z0-9]{3,30}$/', $username)) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$data = getPublicProfile($username);

if (!$data) {
    http_response_code(404);
    ?><!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><title>Portfolio Not Found</title>
    <link rel="stylesheet" href="assets/css/style.css"></head>
    <body class="auth-page">
    <div class="auth-wrap">
      <div class="auth-card" style="text-align:center">
        <div style="font-size:60px">🔍</div>
        <h1 style="margin:16px 0 8px">Portfolio Not Found</h1>
        <p style="color:var(--text-muted)">No portfolio found for username <strong><?= e($username) ?></strong>.</p>
        <a href="index.php" class="btn btn-primary" style="margin-top:24px">← Back to Home</a>
      </div>
    </div></body></html>
    <?php
    exit;
}

$template    = $data['template'] ?? 'template1';
$name        = $data['name']     ?? '';
$bio         = $data['bio']      ?? '';
$skills      = parseSkills($data['skills']    ?? '');
$projects    = parseProjects($data['projects'] ?? '[]');
$education   = parseEducation($data['education'] ?? '[]');
$github_url  = $data['github_url']   ?? '';
$linkedin_url= $data['linkedin_url'] ?? '';
$website_url = $data['website_url']  ?? '';
$photo       = $data['photo']        ?? '';

$isOwner  = isLoggedIn() && (getCurrentUser()['username'] === $username);
$editUrl  = 'dashboard.php';
$pdfUrl   = 'export-pdf.php?u=' . rawurlencode($username);
$shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
          . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
          . '/student-portfolio/portfolio.php?u=' . rawurlencode($username);

// ── Template-specific helpers ─────────────────

function sectionIcon(string $section, string $tpl): string {
    $icons = ['skills' => '⚡', 'projects' => '🚀', 'education' => '🎓'];
    return $icons[$section] ?? '◆';
}

function sectionHead(string $section, string $label, string $tpl): void {
    $icon = sectionIcon($section, $tpl);
    if ($tpl === 'template2') {
        echo '<div class="pf-section-head"><h2>' . e($label) . '</h2><div class="pf-section-line"></div></div>';
    } else {
        echo '<div class="pf-section-head">
                <div class="pf-section-icon">' . $icon . '</div>
                <h2>' . e($label) . '</h2>
              </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($name) ?> — Portfolio</title>
  <meta name="description" content="<?= e(mb_substr($bio, 0, 160)) ?>">
  <meta property="og:title"       content="<?= e($name) ?>'s Portfolio">
  <meta property="og:description" content="<?= e(mb_substr($bio, 0, 160)) ?>">
  <link rel="stylesheet" href="assets/css/<?= e($template) ?>.css">
</head>
<body>

<!-- ── Floating action bar ── -->
<div class="pf-actions no-print">
  <?php if ($isOwner): ?>
    <a href="<?= e($editUrl) ?>" class="pf-btn-edit">✏ Edit</a>
  <?php else: ?>
    <a href="index.php" class="pf-btn-edit">Portfolio<span style="color:var(--t1-secondary,#06b6d4)">Hub</span></a>
  <?php endif; ?>
  <a href="<?= e($pdfUrl) ?>" target="_blank" class="pf-btn-pdf">📄 PDF</a>
  <button class="pf-btn-copy" onclick="copyLink()">🔗 Copy Link</button>
</div>

<!-- ── Header ── -->
<header class="pf-header">
  <div class="pf-hero-inner">
    <?php
    $avatarHtml = $photo
        ? '<img src="' . e($photo) . '" alt="' . e($name) . '" class="pf-avatar-img">'
        : '<div class="pf-avatar">' . e(getInitials($name)) . '</div>';
    ?>
    <?php if ($template === 'template2'): ?>
      <div class="pf-header-inner">
        <?= $avatarHtml ?>
        <div class="pf-header-text">
          <h1 class="pf-name"><?= e($name) ?></h1>
          <?php if ($bio): ?><p class="pf-bio"><?= nl2br(e($bio)) ?></p><?php endif; ?>
          <?php if ($github_url || $linkedin_url || $website_url): ?>
            <div class="pf-social">
              <?php if ($github_url): ?><a href="<?= e($github_url) ?>" target="_blank" rel="noopener">🐙 GitHub</a><?php endif; ?>
              <?php if ($linkedin_url): ?><a href="<?= e($linkedin_url) ?>" target="_blank" rel="noopener">💼 LinkedIn</a><?php endif; ?>
              <?php if ($website_url): ?><a href="<?= e($website_url) ?>" target="_blank" rel="noopener">🌐 Website</a><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <?= $avatarHtml ?>
      <h1 class="pf-name"><?= e($name) ?></h1>
      <?php if ($bio): ?><p class="pf-bio"><?= nl2br(e($bio)) ?></p><?php endif; ?>
      <?php if ($github_url || $linkedin_url || $website_url): ?>
        <div class="pf-social">
          <?php if ($github_url): ?><a href="<?= e($github_url) ?>" target="_blank" rel="noopener">🐙 GitHub</a><?php endif; ?>
          <?php if ($linkedin_url): ?><a href="<?= e($linkedin_url) ?>" target="_blank" rel="noopener">💼 LinkedIn</a><?php endif; ?>
          <?php if ($website_url): ?><a href="<?= e($website_url) ?>" target="_blank" rel="noopener">🌐 Website</a><?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</header>

<!-- ── Main content ── -->
<main class="pf-main">

  <!-- Skills -->
  <?php if ($skills): ?>
    <section class="pf-section">
      <?php sectionHead('skills', 'Skills', $template); ?>
      <div class="pf-section-body">
        <div class="skills-wrap">
          <?php foreach ($skills as $skill): ?>
            <span class="skill-pill"><?= e($skill) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- Projects -->
  <?php if ($projects): ?>
    <section class="pf-section">
      <?php sectionHead('projects', 'Projects', $template); ?>
      <div class="pf-section-body">
        <?php if ($template === 'template2'): ?>
          <div class="projects-list">
            <?php foreach ($projects as $i => $p): ?>
              <div class="project-card">
                <div class="project-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
                <div class="project-body">
                  <div class="project-card-top">
                    <h3 class="project-title"><?= e($p['title'] ?? '') ?></h3>
                    <?php if (!empty($p['url'])): ?>
                      <a href="<?= e($p['url']) ?>" target="_blank" rel="noopener" class="project-link" title="View project">↗</a>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($p['description'])): ?>
                    <p class="project-desc"><?= nl2br(e($p['description'])) ?></p>
                  <?php endif; ?>
                  <?php if (!empty($p['tech'])): ?>
                    <div class="project-tech-wrap">
                      <?php foreach (parseSkills($p['tech']) as $t): ?>
                        <span class="project-tech"><?= e($t) ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="projects-grid">
            <?php foreach ($projects as $p): ?>
              <div class="project-card">
                <div class="project-card-top">
                  <h3 class="project-title"><?= e($p['title'] ?? '') ?></h3>
                  <?php if (!empty($p['url'])): ?>
                    <a href="<?= e($p['url']) ?>" target="_blank" rel="noopener" class="project-link" title="View project">↗</a>
                  <?php endif; ?>
                </div>
                <?php if (!empty($p['description'])): ?>
                  <p class="project-desc"><?= nl2br(e($p['description'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($p['tech'])): ?>
                  <div class="project-tech-wrap">
                    <?php foreach (parseSkills($p['tech']) as $t): ?>
                      <span class="project-tech"><?= e($t) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Education -->
  <?php if ($education): ?>
    <section class="pf-section">
      <?php sectionHead('education', 'Education', $template); ?>
      <div class="pf-section-body">
        <?php if ($template === 'template2'): ?>
          <div class="edu-list">
            <?php foreach ($education as $edu): ?>
              <div class="edu-entry">
                <div class="edu-year"><?= e($edu['year'] ?? '') ?></div>
                <div class="edu-body">
                  <div class="edu-degree"><?= e($edu['degree'] ?? '') ?></div>
                  <?php if (!empty($edu['school'])): ?><div class="edu-school"><?= e($edu['school']) ?></div><?php endif; ?>
                  <?php if (!empty($edu['description'])): ?><div class="edu-desc"><?= e($edu['description']) ?></div><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php elseif ($template === 'template3'): ?>
          <div class="edu-timeline">
            <?php foreach ($education as $edu): ?>
              <div class="edu-entry">
                <div class="edu-connector">
                  <div class="edu-dot">🎓</div>
                  <div class="edu-line"></div>
                </div>
                <div class="edu-body">
                  <div class="edu-degree"><?= e($edu['degree'] ?? '') ?></div>
                  <?php if (!empty($edu['school'])): ?><div class="edu-school"><?= e($edu['school']) ?></div><?php endif; ?>
                  <?php if (!empty($edu['year'])): ?><span class="edu-year"><?= e($edu['year']) ?></span><?php endif; ?>
                  <?php if (!empty($edu['description'])): ?><div class="edu-desc"><?= e($edu['description']) ?></div><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="edu-timeline">
            <?php foreach ($education as $edu): ?>
              <div class="edu-entry">
                <div class="edu-dot">🎓</div>
                <div class="edu-body">
                  <div class="edu-degree"><?= e($edu['degree'] ?? '') ?></div>
                  <?php if (!empty($edu['school'])): ?><div class="edu-school"><?= e($edu['school']) ?></div><?php endif; ?>
                  <?php if (!empty($edu['year'])): ?><div class="edu-year"><?= e($edu['year']) ?></div><?php endif; ?>
                  <?php if (!empty($edu['description'])): ?><div class="edu-desc"><?= e($edu['description']) ?></div><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!$skills && !$projects && !$education): ?>
    <div style="text-align:center;padding:60px 24px;opacity:.6;">
      <div style="font-size:48px">📝</div>
      <p style="margin-top:12px">This portfolio is still being set up.</p>
      <?php if ($isOwner): ?>
        <a href="dashboard.php" style="display:inline-block;margin-top:16px;font-weight:700;">Edit your portfolio →</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</main>

<!-- ── Footer ── -->
<footer class="pf-footer no-print">
  <p>
    Built with <a href="index.php">PortfolioHub</a>
    <?php if ($isOwner): ?> · <a href="dashboard.php">✏ Edit Portfolio</a><?php endif; ?>
    &nbsp;·&nbsp; <a href="<?= e($pdfUrl) ?>" target="_blank">📄 Export PDF</a>
  </p>
</footer>

<div id="toast" class="toast" role="status" aria-live="polite" style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#1e293b;color:#fff;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:500;z-index:9999;transition:transform .3s ease;white-space:nowrap;pointer-events:none;"></div>

<script>
function copyLink() {
  const url = <?= json_encode($shareUrl) ?>;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(() => toast('Link copied!', 'success'));
  } else {
    const ta = document.createElement('textarea');
    ta.value = url;
    ta.style.cssText = 'position:fixed;opacity:0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    toast('Link copied!', 'success');
  }
}

function toast(msg, type) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.style.background = type === 'success' ? '#065f46' : '#991b1b';
  el.style.transform = 'translateX(-50%) translateY(0)';
  setTimeout(() => { el.style.transform = 'translateX(-50%) translateY(80px)'; }, 2500);
}
</script>
</body>
</html>
