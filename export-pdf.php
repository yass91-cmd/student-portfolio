<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$username = trim($_GET['u'] ?? '');

if (!$username || !preg_match('/^[a-z0-9]{3,30}$/', $username)) {
    http_response_code(400);
    die('Invalid username.');
}

$data = getPublicProfile($username);
if (!$data) {
    http_response_code(404);
    die('Portfolio not found.');
}

$name         = $data['name']     ?? '';
$bio          = $data['bio']      ?? '';
$template     = $data['template'] ?? 'template1';
$skills       = parseSkills($data['skills']    ?? '');
$projects     = parseProjects($data['projects'] ?? '[]');
$education    = parseEducation($data['education'] ?? '[]');
$github_url   = $data['github_url']   ?? '';
$linkedin_url = $data['linkedin_url'] ?? '';
$website_url  = $data['website_url']  ?? '';
$photo        = $data['photo']        ?? '';

$portfolioUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
              . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
              . '/student-portfolio/portfolio.php?u=' . rawurlencode($username);

// Theme tokens — mirrors each template's CSS variables exactly
$theme = match($template) {
    'template2' => [
        'bg'           => '#ffffff',
        'bg2'          => '#fafafa',
        'surface'      => '#ffffff',
        'border'       => '#ebebeb',
        'text'         => '#111111',
        'text2'        => '#444444',
        'muted'        => '#888888',
        'primary'      => '#6366f1',
        'accent'       => '#06b6d4',
        'grad'         => 'linear-gradient(135deg,#6366f1,#06b6d4)',
        'font'         => "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif",
        'toolbar_bg'   => '#6366f1',
        'toolbar_text' => '#ffffff',
        'skill_bg'     => '#ede9fe',
        'skill_color'  => '#4c1d95',
        'skill_border' => '#c4b5fd',
        'proj_border'  => '#6366f1',
        'page_shadow'  => '0 8px 32px rgba(0,0,0,.08)',
    ],
    'template3' => [
        'bg'           => '#0a0f1e',
        'bg2'          => '#0f172a',
        'surface'      => '#1e2a3a',
        'border'       => '#1e3a5f',
        'text'         => '#e2e8f0',
        'text2'        => '#94a3b8',
        'muted'        => '#64748b',
        'primary'      => '#06b6d4',
        'accent'       => '#818cf8',
        'grad'         => 'linear-gradient(135deg,#06b6d4,#818cf8)',
        'font'         => "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Courier New',monospace",
        'toolbar_bg'   => '#0f172a',
        'toolbar_text' => '#06b6d4',
        'skill_bg'     => 'rgba(6,182,212,.15)',
        'skill_color'  => '#06b6d4',
        'skill_border' => 'rgba(6,182,212,.4)',
        'proj_border'  => '#06b6d4',
        'page_shadow'  => '0 25px 60px rgba(0,0,0,.6)',
    ],
    default => [ // template1
        'bg'           => '#f8fafc',
        'bg2'          => '#f1f5f9',
        'surface'      => '#ffffff',
        'border'       => '#e2e8f0',
        'text'         => '#0f172a',
        'text2'        => '#334155',
        'muted'        => '#64748b',
        'primary'      => '#6366f1',
        'accent'       => '#06b6d4',
        'grad'         => 'linear-gradient(135deg,#6366f1,#06b6d4)',
        'font'         => "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif",
        'toolbar_bg'   => '#6366f1',
        'toolbar_text' => '#ffffff',
        'skill_bg'     => '#ede9fe',
        'skill_color'  => '#4c1d95',
        'skill_border' => '#c4b5fd',
        'proj_border'  => '#6366f1',
        'page_shadow'  => '0 10px 40px rgba(0,0,0,.1)',
    ],
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($name) ?> — Portfolio Export</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: <?= $theme['font'] ?>;
      background: <?= $theme['bg'] ?>;
      color: <?= $theme['text'] ?>;
      font-size: 11pt;
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
      min-height: 100vh;
    }

    a { color: inherit; text-decoration: none; }

    /* ── Toolbar ── */
    .pdf-toolbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      background: <?= $theme['toolbar_bg'] ?>;
      color: <?= $theme['toolbar_text'] ?>;
      padding: 10px 24px;
      display: flex; align-items: center; justify-content: space-between;
      font-size: 14px; gap: 12px;
      border-bottom: 1px solid <?= $theme['border'] ?>;
    }
    .pdf-toolbar strong { font-weight: 700; }
    .pdf-toolbar .btns { display: flex; gap: 8px; }
    .pdf-btn {
      padding: 7px 16px; border-radius: 6px; font-size: 13px; font-weight: 600;
      cursor: pointer; border: none;
      font-family: <?= $theme['font'] ?>;
      transition: opacity .15s ease;
    }
    .pdf-btn-print {
      background: <?= $theme['primary'] ?>;
      color: #fff;
    }
    .pdf-btn-print:hover { opacity: .85; }
    .pdf-btn-back {
      background: transparent;
      color: <?= $theme['toolbar_text'] ?>;
      border: 1px solid <?= $theme['border'] ?>;
    }
    .pdf-btn-back:hover { opacity: .75; }

    /* ── Page card ── */
    .page {
      max-width: 820px;
      margin: 72px auto 48px;
      padding: 40px 48px;
      background: <?= $theme['surface'] ?>;
      border: 1px solid <?= $theme['border'] ?>;
      border-radius: 10px;
      box-shadow: <?= $theme['page_shadow'] ?>;
    }

    /* ── Header ── */
    .pdf-header {
      display: flex; align-items: flex-start; gap: 28px;
      padding-bottom: 24px;
      border-bottom: 2px solid <?= $theme['primary'] ?>;
      margin-bottom: 32px;
    }
    .pdf-avatar {
      width: 80px; height: 80px; border-radius: 50%; flex-shrink: 0;
      background: <?= $theme['grad'] ?>;
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; font-weight: 800; color: #fff;
    }
    .pdf-avatar-img {
      width: 80px; height: 80px; border-radius: 50%; flex-shrink: 0;
      object-fit: cover;
      border: 3px solid <?= $theme['primary'] ?>;
      print-color-adjust: exact; -webkit-print-color-adjust: exact;
    }
    .pdf-name  { font-size: 26pt; font-weight: 800; letter-spacing: -.02em; margin-bottom: 8px; color: <?= $theme['text'] ?>; }
    .pdf-bio   { font-size: 10pt; color: <?= $theme['text2'] ?>; line-height: 1.75; margin-bottom: 12px; }
    .pdf-meta  { display: flex; flex-wrap: wrap; gap: 14px; }
    .pdf-meta-item { font-size: 10pt; color: <?= $theme['muted'] ?>; }
    .pdf-meta-item strong { color: <?= $theme['primary'] ?>; font-weight: 600; }

    /* ── Section ── */
    .pdf-section   { margin-bottom: 28px; }
    .pdf-section-h {
      font-size: 8.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .12em;
      color: <?= $theme['primary'] ?>;
      border-bottom: 1.5px solid <?= $theme['border'] ?>;
      padding-bottom: 6px; margin-bottom: 16px;
    }

    /* Skills */
    .skill-list  { display: flex; flex-wrap: wrap; gap: 8px; }
    .skill-badge {
      padding: 4px 13px; border-radius: 4px; font-size: 9.5pt; font-weight: 600;
      background: <?= $theme['skill_bg'] ?>;
      color: <?= $theme['skill_color'] ?>;
      border: 1px solid <?= $theme['skill_border'] ?>;
    }

    /* Projects */
    .proj-item  { margin-bottom: 18px; padding-left: 14px; border-left: 3px solid <?= $theme['proj_border'] ?>; }
    .proj-title { font-size: 11.5pt; font-weight: 700; margin-bottom: 3px; color: <?= $theme['text'] ?>; }
    .proj-meta  { font-size: 9pt; color: <?= $theme['muted'] ?>; margin-bottom: 5px; }
    .proj-desc  { font-size: 10pt; color: <?= $theme['text2'] ?>; line-height: 1.65; }
    .proj-url   { font-size: 9pt; color: <?= $theme['accent'] ?>; margin-top: 4px; }

    /* Education */
    .edu-row    { display: grid; grid-template-columns: 130px 1fr; gap: 12px; margin-bottom: 16px; }
    .edu-year   { font-size: 9.5pt; color: <?= $theme['muted'] ?>; padding-top: 2px; }
    .edu-degree { font-size: 11.5pt; font-weight: 700; margin-bottom: 2px; color: <?= $theme['text'] ?>; }
    .edu-school { font-size: 10pt; color: <?= $theme['primary'] ?>; font-weight: 600; margin-bottom: 3px; }
    .edu-desc   { font-size: 9.5pt; color: <?= $theme['muted'] ?>; }

    /* Footer */
    .pdf-foot {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid <?= $theme['border'] ?>;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .pdf-foot-brand {
      display: flex; align-items: center; gap: 10px;
    }
    .pdf-foot-logo {
      width: 32px; height: 32px; border-radius: 8px;
      background: <?= $theme['grad'] ?>;
      display: flex; align-items: center; justify-content: center;
      font-size: 15px; font-weight: 900; color: #fff;
      letter-spacing: -.03em;
      print-color-adjust: exact; -webkit-print-color-adjust: exact;
    }
    .pdf-foot-name {
      font-size: 13pt; font-weight: 800; letter-spacing: -.02em;
      color: <?= $theme['text'] ?>;
    }
    .pdf-foot-name span { color: <?= $theme['primary'] ?>; }
    .pdf-foot-tagline {
      font-size: 8pt; color: <?= $theme['muted'] ?>; margin-top: 2px;
      letter-spacing: .04em; text-transform: uppercase;
    }
    .pdf-foot-meta {
      text-align: right; font-size: 8.5pt; color: <?= $theme['muted'] ?>;
      line-height: 1.7;
    }
    .pdf-foot-meta strong { color: <?= $theme['text2'] ?>; font-weight: 600; }

    /* ── Print overrides — always white on paper ── */
    @media print {
      .pdf-toolbar { display: none !important; }
      body  { background: #fff !important; color: #0f172a !important; }
      .page {
        margin: 0; padding: 0; max-width: none;
        background: #fff !important; border: none; box-shadow: none; border-radius: 0;
      }
      .pdf-name       { color: #0f172a !important; }
      .pdf-bio        { color: #334155 !important; }
      .proj-desc      { color: #334155 !important; }
      .skill-badge    { background: #ede9fe !important; color: #4c1d95 !important; border-color: #c4b5fd !important; }
      .pdf-foot-name  { color: #0f172a !important; }
      .pdf-foot-meta  { color: #64748b !important; }
      @page { margin: 15mm; size: A4; }
    }
  </style>
</head>
<body>

<div class="pdf-toolbar">
  <span>📄 <strong><?= e($name) ?></strong> — Portfolio Export</span>
  <div class="btns">
    <button class="pdf-btn pdf-btn-back"  onclick="window.history.back()">← Back</button>
    <button class="pdf-btn pdf-btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
  </div>
</div>

<div class="page" id="pdfPage">

  <div class="pdf-header">
    <?php if ($photo): ?>
      <img src="<?= e($photo) ?>" alt="<?= e($name) ?>" class="pdf-avatar-img">
    <?php else: ?>
      <div class="pdf-avatar"><?= e(getInitials($name)) ?></div>
    <?php endif; ?>
    <div style="flex:1">
      <div class="pdf-name"><?= e($name) ?></div>
      <?php if ($bio): ?>
        <p class="pdf-bio"><?= nl2br(e($bio)) ?></p>
      <?php endif; ?>
      <div class="pdf-meta">
        <?php if ($github_url): ?>
          <span class="pdf-meta-item">🐙 <strong>GitHub:</strong> <?= e(str_replace(['https://','http://'], '', $github_url)) ?></span>
        <?php endif; ?>
        <?php if ($linkedin_url): ?>
          <span class="pdf-meta-item">💼 <strong>LinkedIn:</strong> <?= e(str_replace(['https://','http://'], '', $linkedin_url)) ?></span>
        <?php endif; ?>
        <?php if ($website_url): ?>
          <span class="pdf-meta-item">🌐 <?= e(str_replace(['https://','http://'], '', $website_url)) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($skills): ?>
    <div class="pdf-section">
      <div class="pdf-section-h">Skills</div>
      <div class="skill-list">
        <?php foreach ($skills as $s): ?>
          <span class="skill-badge"><?= e($s) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($projects): ?>
    <div class="pdf-section">
      <div class="pdf-section-h">Projects</div>
      <?php foreach ($projects as $p): ?>
        <div class="proj-item">
          <div class="proj-title"><?= e($p['title'] ?? '') ?></div>
          <?php if (!empty($p['tech'])): ?>
            <div class="proj-meta"><?= e($p['tech']) ?></div>
          <?php endif; ?>
          <?php if (!empty($p['description'])): ?>
            <div class="proj-desc"><?= nl2br(e($p['description'])) ?></div>
          <?php endif; ?>
          <?php if (!empty($p['url'])): ?>
            <div class="proj-url"><?= e($p['url']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($education): ?>
    <div class="pdf-section">
      <div class="pdf-section-h">Education</div>
      <?php foreach ($education as $edu): ?>
        <div class="edu-row">
          <div class="edu-year"><?= e($edu['year'] ?? '') ?></div>
          <div>
            <div class="edu-degree"><?= e($edu['degree'] ?? '') ?></div>
            <?php if (!empty($edu['school'])): ?><div class="edu-school"><?= e($edu['school']) ?></div><?php endif; ?>
            <?php if (!empty($edu['description'])): ?><div class="edu-desc"><?= e($edu['description']) ?></div><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="pdf-foot">
    <div class="pdf-foot-brand">
      <div class="pdf-foot-logo">P</div>
      <div>
        <div class="pdf-foot-name">Portfolio<span>Hub</span></div>
        <div class="pdf-foot-tagline">Student Portfolio Platform</div>
      </div>
    </div>
    <div class="pdf-foot-meta">
      <div><strong><?= e($name) ?></strong></div>
      <div>Generated <?= date('F j, Y') ?></div>
    </div>
  </div>

</div>

<script>
  window.addEventListener('load', () => {
    setTimeout(() => {
      if (window.location.search.includes('autoprint=1')) window.print();
    }, 400);
  });
</script>

</body>
</html>
