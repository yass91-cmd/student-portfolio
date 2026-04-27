/* ═══════════════════════════════════════════════
   PortfolioHub — main.js
   Dashboard interactions, validation, helpers
═══════════════════════════════════════════════ */

'use strict';

// ── Toast notification ────────────────────────
function showToast(msg, type = 'default', duration = 3000) {
  let el = document.getElementById('toast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast';
    el.className = 'toast';
    document.body.appendChild(el);
  }
  el.textContent = msg;
  el.className = 'toast ' + type;
  requestAnimationFrame(() => el.classList.add('show'));
  clearTimeout(el._timer);
  el._timer = setTimeout(() => el.classList.remove('show'), duration);
}

// ── Copy to clipboard ─────────────────────────
function copyToClipboard(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text)
      .then(() => showToast('Link copied to clipboard!', 'success'))
      .catch(() => fallbackCopy(text));
  } else {
    fallbackCopy(text);
  }
}
function fallbackCopy(text) {
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.style.cssText = 'position:fixed;opacity:0';
  document.body.appendChild(ta);
  ta.select();
  try {
    document.execCommand('copy');
    showToast('Link copied!', 'success');
  } catch {
    showToast('Copy failed — please copy manually.', 'error');
  }
  document.body.removeChild(ta);
}

// ── Auto-resize textareas ─────────────────────
function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = el.scrollHeight + 'px';
}
function initAutoResize() {
  document.querySelectorAll('textarea[data-autoresize]').forEach(el => {
    autoResize(el);
    el.addEventListener('input', () => autoResize(el));
  });
}

// ── Character counter ─────────────────────────
function initCharCounters() {
  document.querySelectorAll('[data-maxlen]').forEach(el => {
    const max     = parseInt(el.dataset.maxlen, 10);
    const countId = el.dataset.counter;
    const counter = countId ? document.getElementById(countId) : null;
    if (!counter) return;
    const update = () => {
      const rem = max - el.value.length;
      counter.textContent = rem + ' characters remaining';
      counter.style.color = rem < 40 ? '#ef4444' : '';
    };
    el.addEventListener('input', update);
    update();
  });
}

// ── Tag/Skill input ───────────────────────────
function initSkillTags() {
  const wrap  = document.getElementById('skillTagsWrap');
  const input = document.getElementById('skillTagInput');
  const hidden= document.getElementById('skillsHidden');
  if (!wrap || !input || !hidden) return;

  function getTags() {
    return Array.from(wrap.querySelectorAll('.skill-tag'))
                .map(t => t.dataset.value);
  }
  function syncHidden() {
    hidden.value = getTags().join(', ');
  }
  function addTag(value) {
    const v = value.trim().replace(/[,;]+$/, '').trim();
    if (!v) return;
    if (getTags().map(t => t.toLowerCase()).includes(v.toLowerCase())) return;

    const tag = document.createElement('span');
    tag.className = 'skill-tag';
    tag.dataset.value = v;
    tag.innerHTML = `${escHtml(v)}<button type="button" aria-label="Remove ${escHtml(v)}">×</button>`;
    tag.querySelector('button').addEventListener('click', () => {
      tag.remove();
      syncHidden();
    });
    wrap.insertBefore(tag, input);
    syncHidden();
  }

  input.addEventListener('keydown', e => {
    if ([',', 'Enter'].includes(e.key)) {
      e.preventDefault();
      addTag(input.value);
      input.value = '';
    }
    if (e.key === 'Backspace' && input.value === '') {
      const tags = wrap.querySelectorAll('.skill-tag');
      if (tags.length) tags[tags.length - 1].remove();
      syncHidden();
    }
  });
  input.addEventListener('blur', () => {
    if (input.value.trim()) { addTag(input.value); input.value = ''; }
  });
  wrap.addEventListener('click', () => input.focus());

  // Pre-populate from hidden field
  const existing = hidden.value.split(',').map(s => s.trim()).filter(Boolean);
  existing.forEach(addTag);
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Dynamic Projects ──────────────────────────
let projectCount = 0;

function addProject(data = {}) {
  projectCount++;
  const n     = projectCount;
  const list  = document.getElementById('projList');
  if (!list) return;

  const item = document.createElement('div');
  item.className = 'proj-item';
  item.dataset.idx = n;
  item.innerHTML = `
    <div class="proj-item-head">
      <span class="proj-item-num">Project #${n}</span>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeProject(this)">✕ Remove</button>
    </div>
    <div class="proj-grid">
      <div class="form-group">
        <label class="form-label">Title <span class="req">*</span></label>
        <input type="text" class="form-input proj-title" placeholder="e.g. Portfolio Website"
               value="${escHtml(data.title||'')}" required>
      </div>
      <div class="form-group">
        <label class="form-label">Tech Stack</label>
        <input type="text" class="form-input proj-tech" placeholder="PHP, MySQL, CSS"
               value="${escHtml(data.tech||'')}">
      </div>
      <div class="form-group full">
        <label class="form-label">Description</label>
        <textarea class="form-textarea proj-desc" rows="2" placeholder="What does this project do?"
                  data-autoresize>${escHtml(data.description||'')}</textarea>
      </div>
      <div class="form-group full">
        <label class="form-label">Project URL</label>
        <input type="url" class="form-input proj-url" placeholder="https://github.com/..."
               value="${escHtml(data.url||'')}">
      </div>
    </div>`;
  list.appendChild(item);
  autoResize(item.querySelector('textarea'));
  renumberProjects();
}

function removeProject(btn) {
  btn.closest('.proj-item').remove();
  renumberProjects();
}

function renumberProjects() {
  document.querySelectorAll('.proj-item').forEach((el, i) => {
    el.querySelector('.proj-item-num').textContent = 'Project #' + (i + 1);
  });
  projectCount = document.querySelectorAll('.proj-item').length;
}

function collectProjects() {
  const result = [];
  document.querySelectorAll('.proj-item').forEach(item => {
    const title = item.querySelector('.proj-title').value.trim();
    if (!title) return;
    result.push({
      title,
      description: item.querySelector('.proj-desc').value.trim(),
      tech:        item.querySelector('.proj-tech').value.trim(),
      url:         item.querySelector('.proj-url').value.trim(),
    });
  });
  return JSON.stringify(result);
}

// ── Dynamic Education ─────────────────────────
let eduCount = 0;

function addEducation(data = {}) {
  eduCount++;
  const n    = eduCount;
  const list = document.getElementById('eduList');
  if (!list) return;

  const item = document.createElement('div');
  item.className = 'edu-item';
  item.innerHTML = `
    <div class="edu-item-head">
      <span class="edu-num">Education #${n}</span>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeEducation(this)">✕ Remove</button>
    </div>
    <div class="proj-grid">
      <div class="form-group">
        <label class="form-label">Degree / Certificate <span class="req">*</span></label>
        <input type="text" class="form-input edu-degree" placeholder="BSc Computer Science"
               value="${escHtml(data.degree||'')}" required>
      </div>
      <div class="form-group">
        <label class="form-label">School / Institution</label>
        <input type="text" class="form-input edu-school" placeholder="State University"
               value="${escHtml(data.school||'')}">
      </div>
      <div class="form-group">
        <label class="form-label">Year / Duration</label>
        <input type="text" class="form-input edu-year" placeholder="2020 – 2024"
               value="${escHtml(data.year||'')}">
      </div>
      <div class="form-group">
        <label class="form-label">Details</label>
        <input type="text" class="form-input edu-desc" placeholder="GPA, honours, etc."
               value="${escHtml(data.description||'')}">
      </div>
    </div>`;
  list.appendChild(item);
  renumberEducation();
}

function removeEducation(btn) {
  btn.closest('.edu-item').remove();
  renumberEducation();
}

function renumberEducation() {
  document.querySelectorAll('.edu-item').forEach((el, i) => {
    el.querySelector('.edu-num').textContent = 'Education #' + (i + 1);
  });
  eduCount = document.querySelectorAll('.edu-item').length;
}

function collectEducation() {
  const result = [];
  document.querySelectorAll('.edu-item').forEach(item => {
    const degree = item.querySelector('.edu-degree').value.trim();
    if (!degree) return;
    result.push({
      degree,
      school:      item.querySelector('.edu-school').value.trim(),
      year:        item.querySelector('.edu-year').value.trim(),
      description: item.querySelector('.edu-desc').value.trim(),
    });
  });
  return JSON.stringify(result);
}

// ── Template selector ─────────────────────────
function initTemplatePicker() {
  document.querySelectorAll('.tpl-card').forEach(card => {
    card.addEventListener('click', () => {
      document.querySelectorAll('.tpl-card').forEach(c => c.classList.remove('sel'));
      card.classList.add('sel');
      const hidden = document.getElementById('templateHidden');
      if (hidden) hidden.value = card.dataset.tpl;
    });
  });
}

// ── Dashboard form submission ─────────────────
function initDashForm() {
  const form = document.getElementById('portfolioForm');
  if (!form) return;

  form.addEventListener('submit', e => {
    e.preventDefault();

    // Inject collected dynamic data into hidden inputs
    const projInput = document.getElementById('projectsHidden');
    const eduInput  = document.getElementById('educationHidden');
    if (projInput) projInput.value = collectProjects();
    if (eduInput)  eduInput.value  = collectEducation();

    // Basic validation
    let ok = true;
    form.querySelectorAll('[required]').forEach(el => {
      const err = el.closest('.form-group')?.querySelector('.form-error');
      if (!el.value.trim()) {
        el.classList.add('error');
        if (err) { err.textContent = 'This field is required.'; err.classList.add('show'); }
        ok = false;
      } else {
        el.classList.remove('error');
        if (err) err.classList.remove('show');
      }
    });

    if (!ok) {
      showToast('Please fill in all required fields.', 'error');
      return;
    }

    const btn = form.querySelector('[type=submit]');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    form.submit();
  });
}

// ── Sidebar mobile toggle ─────────────────────
function initSidebar() {
  const fab    = document.getElementById('menuFab');
  const sidebar= document.getElementById('sidebar');
  const mask   = document.getElementById('sidebarMask');
  if (!fab || !sidebar) return;

  function open()  { sidebar.classList.add('open'); mask && mask.classList.add('show'); }
  function close() { sidebar.classList.remove('open'); mask && mask.classList.remove('show'); }

  fab.addEventListener('click', () => sidebar.classList.contains('open') ? close() : open());
  mask && mask.addEventListener('click', close);
}

// ── Password strength meter ───────────────────
function initPwStrength() {
  const input = document.getElementById('pwInput');
  const bar   = document.getElementById('pwBar');
  const label = document.getElementById('pwLabel');
  if (!input || !bar) return;

  input.addEventListener('input', () => {
    const v = input.value;
    let score = 0;
    if (v.length >= 8)  score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    const colours = ['#ef4444','#f59e0b','#10b981','#6366f1'];
    const labels  = ['Weak','Fair','Good','Strong'];
    const pct     = score * 25;
    bar.style.width      = pct + '%';
    bar.style.background = colours[score - 1] || '#e2e8f0';
    if (label) label.textContent = v ? labels[score - 1] || '' : '';
  });
}

// ── Register: username preview ────────────────
function initUsernamePreview() {
  const nameInput = document.getElementById('nameInput');
  const uInput    = document.getElementById('usernameInput');
  const preview   = document.getElementById('urlPreview');
  if (!nameInput || !uInput || !preview) return;

  function slugify(s) {
    return s.toLowerCase().replace(/[^a-z0-9]+/g, '').slice(0, 30);
  }
  nameInput.addEventListener('input', () => {
    if (!uInput.dataset.manualEdit) uInput.value = slugify(nameInput.value);
    updatePreview();
  });
  uInput.addEventListener('input', () => {
    uInput.dataset.manualEdit = '1';
    updatePreview();
  });
  function updatePreview() {
    const origin = window.location.origin;
    preview.textContent = origin + '/portfolio.php?u=' + (uInput.value || 'yourusername');
  }
  updatePreview();
}

// ── Form field validation (live) ─────────────
function initLiveValidation() {
  document.querySelectorAll('.form-input[data-validate], .form-textarea[data-validate]').forEach(el => {
    el.addEventListener('blur', () => validateField(el));
  });
}

function validateField(el) {
  const rule = el.dataset.validate || '';
  const err  = el.closest('.form-group')?.querySelector('.form-error');
  let msg = '';

  if (el.required && !el.value.trim()) {
    msg = 'This field is required.';
  } else if (rule === 'email' && el.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value)) {
    msg = 'Please enter a valid email address.';
  } else if (rule === 'username' && el.value && !/^[a-z0-9]{3,30}$/.test(el.value)) {
    msg = 'Username: 3–30 lowercase letters and numbers only.';
  } else if (rule === 'url' && el.value && !/^https?:\/\/.+/.test(el.value)) {
    msg = 'Please enter a valid URL starting with http:// or https://';
  }

  el.classList.toggle('error', !!msg);
  if (err) { err.textContent = msg; err.classList.toggle('show', !!msg); }
  return !msg;
}

// ── Export PDF ────────────────────────────────
function exportPDF() {
  window.print();
}

// ── Initialise on DOM ready ───────────────────
document.addEventListener('DOMContentLoaded', () => {
  initAutoResize();
  initCharCounters();
  initSkillTags();
  initTemplatePicker();
  initDashForm();
  initSidebar();
  initPwStrength();
  initUsernamePreview();
  initLiveValidation();
});
