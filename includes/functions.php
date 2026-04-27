<?php
// ── Profile CRUD helpers ──────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';

function getProfileByUserId(int $userId): ?array {
    $stmt = getDB()->prepare('SELECT * FROM profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getPublicProfile(string $username): ?array {
    $stmt = getDB()->prepare(
        'SELECT u.id, u.name, u.username, u.created_at,
                p.bio, p.skills, p.projects, p.education,
                p.template, p.github_url, p.linkedin_url, p.website_url,
                p.photo, p.updated_at
         FROM users u
         LEFT JOIN profiles p ON u.id = p.user_id
         WHERE u.username = ?'
    );
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function upsertProfile(int $userId, array $data): void {
    $existing = getProfileByUserId($userId);

    $fields = [
        $data['bio']          ?? '',
        $data['skills']       ?? '',
        $data['projects']     ?? '[]',
        $data['education']    ?? '[]',
        $data['template']     ?? 'template1',
        $data['github_url']   ?? '',
        $data['linkedin_url'] ?? '',
        $data['website_url']  ?? '',
    ];

    if ($existing) {
        // Only update photo if explicitly provided
        if (isset($data['photo'])) {
            $stmt = getDB()->prepare(
                'UPDATE profiles
                 SET bio=?, skills=?, projects=?, education=?,
                     template=?, github_url=?, linkedin_url=?, website_url=?, photo=?
                 WHERE user_id=?'
            );
            $stmt->execute([...$fields, $data['photo'], $userId]);
        } else {
            $stmt = getDB()->prepare(
                'UPDATE profiles
                 SET bio=?, skills=?, projects=?, education=?,
                     template=?, github_url=?, linkedin_url=?, website_url=?
                 WHERE user_id=?'
            );
            $stmt->execute([...$fields, $userId]);
        }
    } else {
        $stmt = getDB()->prepare(
            'INSERT INTO profiles
                (user_id, bio, skills, projects, education, template, github_url, linkedin_url, website_url, photo)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$userId, ...$fields, $data['photo'] ?? '']);
    }
}

function saveProfilePhoto(int $userId, array $file): string {
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    $maxBytes    = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try again.');
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Photo must be under 2 MB.');
    }

    // Verify real mime type (not just extension)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('Only JPEG, PNG, or WebP images are allowed.');
    }

    $ext     = match($mime) { 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' };
    $dir     = __DIR__ . '/../assets/uploads/avatars/';
    $newName = $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest    = $dir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save photo. Check server permissions.');
    }

    // Delete old avatar for this user
    foreach (glob($dir . $userId . '_*') as $old) {
        if ($old !== $dest) @unlink($old);
    }

    return 'assets/uploads/avatars/' . $newName;
}

function parseSkills(string $raw): array {
    if (trim($raw) === '') return [];
    return array_values(array_filter(array_map('trim', explode(',', $raw))));
}

function parseProjects(string $raw): array {
    if (trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function parseEducation(string $raw): array {
    if (trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    $init  = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '') $init .= strtoupper($p[0]);
    }
    return $init ?: '?';
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60)   . ' min ago';
    if ($diff < 86400) return floor($diff / 3600)  . ' hr ago';
    return floor($diff / 86400) . ' days ago';
}

function usernameExists(string $username): bool {
    $stmt = getDB()->prepare('SELECT 1 FROM users WHERE username = ?');
    $stmt->execute([$username]);
    return (bool)$stmt->fetchColumn();
}

function emailExists(string $email): bool {
    $stmt = getDB()->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return (bool)$stmt->fetchColumn();
}

function createUser(string $name, string $email, string $username, string $password): int {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = getDB()->prepare(
        'INSERT INTO users (name, email, username, password) VALUES (?,?,?,?)'
    );
    $stmt->execute([$name, $email, $username, $hash]);
    return (int)getDB()->lastInsertId();
}

function getUserByEmail(string $email): ?array {
    $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function countProjects(string $raw): int {
    return count(parseProjects($raw));
}

function countEducation(string $raw): int {
    return count(parseEducation($raw));
}
