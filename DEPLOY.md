# Free Deployment Guide — PortfolioHub

## Recommended: Railway (easiest, real MySQL, no sleep)

### Step 1 — Push to GitHub
```bash
git init
git add .
git commit -m "initial commit"
git remote add origin https://github.com/YOUR_USERNAME/student-portfolio.git
git push -u origin main
```

### Step 2 — Create Railway project
1. Go to https://railway.app and sign up (free $5/month credit)
2. Click **New Project → Deploy from GitHub repo**
3. Select your repository

### Step 3 — Add MySQL database
1. Inside your Railway project click **+ New → Database → MySQL**
2. Railway auto-creates environment variables:
   - `MYSQLHOST`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLPORT`

### Step 4 — Map env vars to app
In Railway → your PHP service → **Variables**, add:
```
DB_HOST     = ${{MySQL.MYSQLHOST}}
DB_NAME     = ${{MySQL.MYSQLDATABASE}}
DB_USER     = ${{MySQL.MYSQLUSER}}
DB_PASS     = ${{MySQL.MYSQLPASSWORD}}
```

### Step 5 — Add nixpacks.toml for PHP
Create this file at the project root:
```toml
[phases.setup]
nixPkgs = ["php82", "php82Extensions.pdo_mysql", "php82Extensions.fileinfo", "php82Extensions.mbstring"]

[start]
cmd = "php -S 0.0.0.0:$PORT -t ."
```

### Step 6 — Import database
In Railway, click MySQL → **Connect → Query** and paste the contents of `database.sql`.

### Step 7 — Fix uploads path
In Railway, uploaded photos don't persist across redeploys (ephemeral filesystem).
Options:
- Use **Cloudinary free tier** for photo storage (recommended)
- Or skip persistent uploads and use Railway's volume (paid)

### Step 8 — Deploy
Railway deploys automatically on every git push. Your app gets a public URL like:
`https://student-portfolio-production.up.railway.app`

---

## Alternative: InfinityFree (100% free, no credit card)

1. Sign up at https://infinityfree.com
2. Create a hosting account → note your FTP credentials and MySQL host
3. In their control panel: **MySQL Databases → Create** → note DB name, user, password
4. Upload all project files via **FileZilla** (FTP) to `htdocs/` folder
5. Import `database.sql` via **phpMyAdmin** in their control panel
6. Edit `config/database.php` with your InfinityFree DB credentials directly
   (env vars not supported — hardcode for this host only)

**Limitations:** Ads injected on free plan, 5 GB disk, no SSH.

---

## Pre-deployment checklist

- [ ] `config/database.php` reads from env vars (already done)
- [ ] `assets/uploads/avatars/` directory exists and is writable (`chmod 755`)
- [ ] HTTPS is active on the host (Railway and InfinityFree both provide it)
      → The secure cookie flag will auto-enable via the code in `includes/auth.php`
- [ ] Demo credentials block only shows on localhost (already done in `login.php`)
- [ ] Remove any test accounts from the database before going live

---

## Security notes for production

| Setting | Status |
|---|---|
| DB credentials via env vars | ✅ Done |
| HTTPS-only cookies | ✅ Auto-detects HTTPS |
| Security headers (X-Frame, X-Content-Type) | ✅ Done |
| XSS in JS context fixed | ✅ Done |
| Rate limiting on login (5 attempts / 15 min) | ✅ Done |
| Demo credentials hidden in production | ✅ Done |
| Password hashing bcrypt cost 12 | ✅ Done |
| CSRF tokens on all forms | ✅ Done |
| Prepared statements everywhere | ✅ Done |
| Photo upload — mime validated server-side | ✅ Done |
