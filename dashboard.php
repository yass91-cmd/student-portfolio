<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user    = getCurrentUser();
$profile = getProfileByUserId($user['id']);

$success = '';
$error   = '';

// ── Handle form submission ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $bio         = mb_substr(trim($_POST['bio'] ?? ''), 0, 600);
    $skills      = trim($_POST['skills']      ?? '');
    $projects    = trim($_POST['projects']    ?? '[]');
    $education   = trim($_POST['education']   ?? '[]');
    $template    = trim($_POST['template']    ?? 'template1');
    $github_url  = trim($_POST['github_url']  ?? '');
    $linkedin_url= trim($_POST['linkedin_url']?? '');
    $website_url = trim($_POST['website_url'] ?? '');

    // Validate template choice
    if (!in_array($template, ['template1','template2','template3'])) $template = 'template1';

    // Validate JSON fields
    if (!json_decode($projects))  $projects  = '[]';
    if (!json_decode($education)) $education = '[]';

    // Sanitise URLs
    $github_url   = filter_var($github_url,   FILTER_SANITIZE_URL);
    $linkedin_url = filter_var($linkedin_url, FILTER_SANITIZE_URL);
    $website_url  = filter_var($website_url,  FILTER_SANITIZE_URL);

    try {
        $profileData = compact(
            'bio','skills','projects','education',
            'template','github_url','linkedin_url','website_url'
        );

        // Handle photo upload if a file was submitted
        if (!empty($_FILES['photo']['name'])) {
            $profileData['photo'] = saveProfilePhoto($user['id'], $_FILES['photo']);
        }

        upsertProfile($user['id'], $profileData);
        $profile = getProfileByUserId($user['id']);
        $success = 'Portfolio saved successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ── Current profile values (defaults for new users) ──
$bio         = $profile['bio']          ?? '';
$skills      = $profile['skills']       ?? '';
$projects    = $profile['projects']     ?? '[]';
$education   = $profile['education']    ?? '[]';
$template    = $profile['template']     ?? 'template1';
$github_url  = $profile['github_url']  ?? '';
$linkedin_url= $profile['linkedin_url']?? '';
$website_url = $profile['website_url'] ?? '';
$photo       = $profile['photo']        ?? '';

$projArr = parseProjects($projects);
$eduArr  = parseEducation($education);
$skillArr= parseSkills($skills);

$publicUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
           . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
           . '/student-portfolio/portfolio.php?u=' . rawurlencode($user['username']);

$welcome = isset($_GET['welcome']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — PortfolioHub</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="dash-layout">

  <!-- Sidebar overlay (mobile) -->
  <div class="sidebar-mask" id="sidebarMask"></div>

  <!-- ── Sidebar ── -->
  <aside class="sidebar" id="sidebar" role="navigation" aria-label="Dashboard navigation">
    <div class="sidebar-head">
      <a href="index.php" class="logo">Portfolio<span>Hub</span></a>
    </div>

    <div class="sidebar-user">
      <div class="s-avatar"><?= e(getInitials($user['name'])) ?></div>
      <div>
        <div class="s-name"><?= e($user['name']) ?></div>
        <div class="s-email"><?= e($user['email']) ?></div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <p class="nav-section-label">Portfolio</p>
      <a href="#overview"   class="s-nav-item active"><span class="ni">📊</span> Overview</a>
      <a href="#photo"      class="s-nav-item"><span class="ni">📷</span> Photo</a>
      <a href="#bio"        class="s-nav-item"><span class="ni">👤</span> Bio</a>
      <a href="#skills"     class="s-nav-item"><span class="ni">⚡</span> Skills</a>
      <a href="#projects"   class="s-nav-item"><span class="ni">🚀</span> Projects</a>
      <a href="#education"  class="s-nav-item"><span class="ni">🎓</span> Education</a>
      <a href="#appearance" class="s-nav-item"><span class="ni">🎨</span> Appearance</a>
      <a href="#links"      class="s-nav-item"><span class="ni">🔗</span> Social Links</a>

      <p class="nav-section-label">Share</p>
      <a href="<?= e($publicUrl) ?>" target="_blank" class="s-nav-item"><span class="ni">👁</span> View Portfolio</a>
      <a href="export-pdf.php?u=<?= rawurlencode($user['username']) ?>" target="_blank" class="s-nav-item"><span class="ni">📄</span> Export PDF</a>
    </nav>

    <div class="sidebar-foot">
      <a href="logout.php" class="s-nav-item btn-ghost">
        <span class="ni">🚪</span> Sign Out
      </a>
    </div>
  </aside>

  <!-- ── Main content ── -->
  <div class="dash-main">
    <div class="dash-topbar">
      <h1>My Portfolio</h1>
      <div class="topbar-actions">
        <a href="<?= e($publicUrl) ?>" target="_blank" class="btn btn-outline btn-sm">👁 Preview</a>
        <a href="export-pdf.php?u=<?= rawurlencode($user['username']) ?>" target="_blank" class="btn btn-ghost btn-sm">📄 PDF</a>
      </div>
    </div>

    <div class="dash-content">

      <?php if ($welcome): ?>
        <div class="alert alert-success">
          🎉 Welcome to PortfolioHub, <?= e($user['name']) ?>! Start by filling in your profile below.
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">✓ <?= e($success) ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠ <?= e($error) ?></div>
      <?php endif; ?>

      <!-- ── Overview stats ── -->
      <div id="overview">
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-val"><?= count($skillArr) ?></div>
            <div class="stat-lbl">Skills Listed</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">🚀</div>
            <div class="stat-val"><?= count($projArr) ?></div>
            <div class="stat-lbl">Projects</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <div class="stat-val"><?= count($eduArr) ?></div>
            <div class="stat-lbl">Education Entries</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">🎨</div>
            <div class="stat-val" style="font-size:16px;font-weight:700;"><?= ucfirst(str_replace('template','T',$template)) ?></div>
            <div class="stat-lbl">Active Template</div>
          </div>
        </div>

        <!-- Share link -->
        <div class="card" style="margin-bottom:20px;">
          <div class="card-head"><div class="card-title"><span class="icon">🔗</span> Your Public Portfolio Link</div></div>
          <div class="card-body">
            <div class="share-bar">
              <div class="share-url">
                <a href="<?= e($publicUrl) ?>" target="_blank" rel="noopener"><?= e($publicUrl) ?></a>
              </div>
              <button class="btn btn-outline btn-sm" onclick="copyToClipboard('<?= e($publicUrl) ?>')">
                📋 Copy Link
              </button>
              <a href="<?= e($publicUrl) ?>" target="_blank" class="btn btn-ghost btn-sm">Open →</a>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Main form ── -->
      <form id="portfolioForm" method="POST" action="dashboard.php" enctype="multipart/form-data">
        <?= csrfField() ?>

        <!-- Hidden serialised fields for dynamic sections -->
        <input type="hidden" id="projectsHidden"  name="projects"  value="<?= e($projects) ?>">
        <input type="hidden" id="educationHidden" name="education" value="<?= e($education) ?>">
        <input type="hidden" id="templateHidden"  name="template"  value="<?= e($template) ?>">

        <!-- ── Profile Photo ── -->
        <div class="card" id="photo">
          <div class="card-head">
            <div class="card-title"><span class="icon">📷</span> Profile Photo</div>
          </div>
          <div class="card-body">
            <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
              <div id="avatarPreviewWrap" style="flex-shrink:0;">
                <?php if ($photo): ?>
                  <img id="avatarPreview" src="<?= e($photo) ?>" alt="Current photo"
                       style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);">
                <?php else: ?>
                  <div id="avatarInitials" style="width:96px;height:96px;border-radius:50%;
                       background:linear-gradient(135deg,#6366f1,#06b6d4);
                       display:flex;align-items:center;justify-content:center;
                       font-size:32px;font-weight:800;color:#fff;">
                    <?= e(getInitials($user['name'])) ?>
                  </div>
                <?php endif; ?>
              </div>
              <div style="flex:1;min-width:200px;">
                <label class="form-label" for="photoInput">Upload Photo</label>
                <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/webp"
                       style="display:block;width:100%;padding:8px;border:1px dashed var(--border);
                              border-radius:8px;cursor:pointer;font-size:14px;background:var(--input-bg,#fff);">
                <p class="form-hint" style="margin-top:6px;">JPEG, PNG or WebP · Max 2 MB. Shown on your portfolio and CV.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Bio ── -->
        <div class="card" id="bio">
          <div class="card-head">
            <div class="card-title"><span class="icon">👤</span> Bio</div>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label" for="bioInput">About You</label>
              <textarea id="bioInput" name="bio" class="form-textarea" rows="4" placeholder="Write a short, engaging bio about yourself — your background, interests and goals."
                        data-autoresize data-maxlen="600" data-counter="bioCount"><?= e($bio) ?></textarea>
              <div class="char-count" id="bioCount"></div>
            </div>
          </div>
        </div>

        <!-- ── Skills ── -->
        <div class="card" id="skills">
          <div class="card-head">
            <div class="card-title"><span class="icon">⚡</span> Skills</div>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Skills <span style="font-weight:400;color:var(--text-muted)">(type and press Enter or comma)</span></label>
              <div class="tag-input-wrap" id="skillTagsWrap">
                <input type="text" class="tag-text-input" id="skillTagInput" placeholder="e.g. PHP, JavaScript…" autocomplete="off">
              </div>
              <input type="hidden" id="skillsHidden" name="skills" value="<?= e($skills) ?>">
              <p class="form-hint">Separate skills with <kbd>,</kbd> or <kbd>Enter</kbd>. Click × to remove.</p>
            </div>
          </div>
        </div>

        <!-- ── Projects ── -->
        <div class="card" id="projects">
          <div class="card-head">
            <div class="card-title"><span class="icon">🚀</span> Projects</div>
            <button type="button" class="btn btn-outline btn-sm" onclick="addProject()">+ Add Project</button>
          </div>
          <div class="card-body">
            <div class="proj-list" id="projList"></div>
            <div id="projEmpty" style="text-align:center;padding:24px;color:var(--text-muted);font-size:14px;">
              No projects yet. Click <strong>+ Add Project</strong> to get started.
            </div>
          </div>
        </div>

        <!-- ── Education ── -->
        <div class="card" id="education">
          <div class="card-head">
            <div class="card-title"><span class="icon">🎓</span> Education</div>
            <button type="button" class="btn btn-outline btn-sm" onclick="addEducation()">+ Add Education</button>
          </div>
          <div class="card-body">
            <div class="edu-list" id="eduList"></div>
            <div id="eduEmpty" style="text-align:center;padding:24px;color:var(--text-muted);font-size:14px;">
              No education entries yet. Click <strong>+ Add Education</strong> to get started.
            </div>
          </div>
        </div>

        <!-- ── Appearance / Templates ── -->
        <div class="card" id="appearance">
          <div class="card-head">
            <div class="card-title"><span class="icon">🎨</span> Portfolio Template</div>
          </div>
          <div class="card-body">
            <p style="font-size:14px;color:var(--text-muted);margin-bottom:20px;">
              Choose how your public portfolio looks. Changes are applied when you save.
            </p>
            <div class="tpl-grid">
              <div class="tpl-card <?= $template === 'template1' ? 'sel' : '' ?>" data-tpl="template1">
                <div class="tpl-prev tpl-prev-1">
                  <div class="tpl-av"></div>
                  <div class="tpl-lines">
                    <div class="tpl-ln"></div>
                    <div class="tpl-ln" style="width:60%"></div>
                    <div class="tpl-ln" style="width:80%"></div>
                  </div>
                  <div class="tpl-check">✓</div>
                </div>
                <div class="tpl-name">Modern Gradient</div>
              </div>
              <div class="tpl-card <?= $template === 'template2' ? 'sel' : '' ?>" data-tpl="template2">
                <div class="tpl-prev tpl-prev-2">
                  <div class="tpl-av"></div>
                  <div class="tpl-lines">
                    <div class="tpl-ln"></div>
                    <div class="tpl-ln" style="width:60%"></div>
                    <div class="tpl-ln" style="width:80%"></div>
                  </div>
                  <div class="tpl-check">✓</div>
                </div>
                <div class="tpl-name">Minimal Clean</div>
              </div>
              <div class="tpl-card <?= $template === 'template3' ? 'sel' : '' ?>" data-tpl="template3">
                <div class="tpl-prev tpl-prev-3">
                  <div class="tpl-av"></div>
                  <div class="tpl-lines">
                    <div class="tpl-ln"></div>
                    <div class="tpl-ln" style="width:60%"></div>
                    <div class="tpl-ln" style="width:80%"></div>
                  </div>
                  <div class="tpl-check">✓</div>
                </div>
                <div class="tpl-name">Dark Pro</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Social Links ── -->
        <div class="card" id="links">
          <div class="card-head">
            <div class="card-title"><span class="icon">🔗</span> Social Links</div>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label" for="githubUrl">GitHub</label>
              <div class="input-wrap">
                <span class="icon" style="font-size:14px;left:10px">🐙</span>
                <input type="url" id="githubUrl" name="github_url" class="form-input"
                       placeholder="https://github.com/username" value="<?= e($github_url) ?>"
                       data-validate="url">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="linkedinUrl">LinkedIn</label>
              <div class="input-wrap">
                <span class="icon" style="font-size:14px;left:10px">💼</span>
                <input type="url" id="linkedinUrl" name="linkedin_url" class="form-input"
                       placeholder="https://linkedin.com/in/username" value="<?= e($linkedin_url) ?>"
                       data-validate="url">
              </div>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label" for="websiteUrl">Personal Website</label>
              <div class="input-wrap">
                <span class="icon" style="font-size:14px;left:10px">🌐</span>
                <input type="url" id="websiteUrl" name="website_url" class="form-input"
                       placeholder="https://yoursite.com" value="<?= e($website_url) ?>"
                       data-validate="url">
              </div>
            </div>
          </div>
        </div>

        <!-- ── Sticky save bar ── -->
        <div class="save-bar">
          <span class="save-bar-msg">
            <?php if ($profile && $profile['updated_at']): ?>
              Last saved: <?= e(timeAgo($profile['updated_at'])) ?>
            <?php else: ?>
              Not saved yet
            <?php endif; ?>
          </span>
          <div class="save-bar-actions">
            <a href="<?= e($publicUrl) ?>" target="_blank" class="btn btn-ghost">👁 Preview</a>
            <button type="submit" class="btn btn-primary btn-lg">💾 Save Portfolio</button>
          </div>
        </div>

      </form>
    </div><!-- /.dash-content -->
  </div><!-- /.dash-main -->
</div><!-- /.dash-layout -->

<!-- Mobile menu button -->
<button class="menu-fab" id="menuFab" aria-label="Toggle navigation">☰</button>

<!-- Toast container -->
<div id="toast" class="toast" role="status" aria-live="polite"></div>

<script src="assets/js/main.js"></script>
<script>
// Pre-populate dynamic sections from PHP
const projData = <?= $projects ?>;
const eduData  = <?= $education ?>;

if (Array.isArray(projData) && projData.length) {
  projData.forEach(p => addProject(p));
} else {
  document.getElementById('projEmpty').style.display = 'block';
}

if (Array.isArray(eduData) && eduData.length) {
  eduData.forEach(e => addEducation(e));
} else {
  document.getElementById('eduEmpty').style.display = 'block';
}

// Hide empty notices when items exist
const projObserver = new MutationObserver(() => {
  document.getElementById('projEmpty').style.display =
    document.querySelectorAll('.proj-item').length ? 'none' : 'block';
});
projObserver.observe(document.getElementById('projList'), {childList: true});

const eduObserver = new MutationObserver(() => {
  document.getElementById('eduEmpty').style.display =
    document.querySelectorAll('.edu-item').length ? 'none' : 'block';
});
eduObserver.observe(document.getElementById('eduList'), {childList: true});

// Live photo preview
document.getElementById('photoInput')?.addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (ev) => {
    const wrap = document.getElementById('avatarPreviewWrap');
    wrap.innerHTML = `<img id="avatarPreview" src="${ev.target.result}" alt="Preview"
      style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);">`;
  };
  reader.readAsDataURL(file);
});

// Sidebar smooth scroll + active nav highlight
document.querySelectorAll('.sidebar-nav .s-nav-item[href^="#"]').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const target = document.querySelector(link.getAttribute('href'));
    if (target) target.scrollIntoView({behavior: 'smooth', block: 'start'});
    document.querySelectorAll('.s-nav-item').forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    if (window.innerWidth <= 768) {
      document.getElementById('sidebar').classList.remove('open');
      document.getElementById('sidebarMask').classList.remove('show');
    }
  });
});
</script>
</body>
</html>
