<?php
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PortfolioHub — Student Portfolio Platform</title>
  <meta name="description" content="Create, manage and share your student portfolio with a single link.">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing">

<!-- Navigation -->
<nav class="topnav" role="navigation">
  <div class="topnav-inner">
    <a href="index.php" class="logo">Portfolio<span>Hub</span></a>
    <div class="topnav-links">
      <a href="login.php"    class="btn btn-ghost btn-sm">Login</a>
      <a href="register.php" class="btn btn-primary btn-sm">Get Started</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-eyebrow">✦ Free for Students</div>
    <h1>Build Your<br><span class="grad">Student Portfolio</span></h1>
    <p class="hero-sub">
      Create a stunning online portfolio in minutes. Add your bio, skills, projects
      and education — then share it with a single link.
    </p>
    <div class="hero-btns">
      <a href="register.php" class="btn btn-primary btn-lg">Create Free Portfolio</a>
      <a href="portfolio.php?u=demo" class="btn btn-outline btn-lg">View Demo →</a>
    </div>
    <p class="hero-note"><strong>No credit card required.</strong> Takes less than 5 minutes.</p>
  </div>

  <div class="hero-visual" aria-hidden="true">
    <div class="browser-frame">
      <div class="browser-bar">
        <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
        <div class="browser-url-bar">portfoliohub.com/u/alex</div>
      </div>
      <div class="browser-body">
        <div class="mock-hero">
          <div class="mock-av"></div>
          <div class="mock-text">
            <div class="mock-line"></div>
            <div class="mock-line short"></div>
            <div class="mock-line shorter"></div>
          </div>
        </div>
        <div class="mock-section">
          <div class="mock-label"></div>
          <div class="mock-badges">
            <span class="mock-badge"></span>
            <span class="mock-badge"></span>
            <span class="mock-badge"></span>
            <span class="mock-badge"></span>
          </div>
        </div>
        <div class="mock-section">
          <div class="mock-label"></div>
          <div class="mock-cards">
            <div class="mock-card"></div>
            <div class="mock-card"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Features -->
<section class="features">
  <div class="section-head">
    <h2>Everything You Need</h2>
    <p>A complete toolkit to showcase your work professionally.</p>
  </div>
  <div class="features-grid">
    <div class="feat-card">
      <div class="feat-icon">📝</div>
      <h3>Easy to Build</h3>
      <p>Add your bio, skills, projects and education through a clean dashboard — no code required.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon">🎨</div>
      <h3>3 Beautiful Templates</h3>
      <p>Choose from Modern, Minimal or Dark Pro — switch anytime with one click.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon">🔗</div>
      <h3>Shareable Link</h3>
      <p>Every portfolio gets a unique URL you can share with recruiters or add to your CV.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon">📄</div>
      <h3>PDF Export</h3>
      <p>Download your portfolio as a professional PDF — perfect for job applications.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon">📱</div>
      <h3>Fully Responsive</h3>
      <p>Your portfolio looks perfect on desktop, tablet and mobile — always.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon">🔒</div>
      <h3>Secure & Private</h3>
      <p>Protected with hashed passwords, prepared statements and session-based access control.</p>
    </div>
  </div>
</section>

<!-- How it works -->
<section class="how">
  <div class="section-head">
    <h2>How It Works</h2>
    <p>Three steps from sign-up to published portfolio.</p>
  </div>
  <div class="steps">
    <div class="step">
      <div class="step-num">1</div>
      <h3>Create Account</h3>
      <p>Register with your name, email and a unique username that becomes your public URL.</p>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <h3>Fill Your Portfolio</h3>
      <p>Add bio, skills, projects and education. Choose a template that fits your style.</p>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <h3>Share with the World</h3>
      <p>Copy your link, export a PDF and start impressing recruiters.</p>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-band">
  <h2>Ready to Stand Out?</h2>
  <p>Join students who already have a professional online presence.</p>
  <a href="register.php" class="btn btn-primary btn-xl">Create Your Portfolio — It's Free</a>
</section>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> PortfolioHub · Built for students &nbsp;|&nbsp;
     <a href="portfolio.php?u=demo">View Demo</a>
  </p>
</footer>

</body>
</html>
