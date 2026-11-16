<?php
/**
 * Utility to re-hash all user passwords with OWASP Argon2id / Peppered Hash
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$users = [
    'admin@piknik.com'   => 'Admin@123',
    'owner@piknik.com'   => 'Owner@123',
    'dea@gmail.com'      => 'Password@123',
    'deanda@gmail.com'   => 'Password@123',
    'elputra@gmail.com'  => 'Password@123',
    'siddiq@gmail.com'   => 'Password@123',
    'nisa@gmail.com'     => 'Password@123',
];

foreach ($users as $email => $plainPassword) {
    $hash = hash_password_peppered($plainPassword);
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
    $stmt->execute([
        'password' => $hash,
        'email'    => $email
    ]);
    echo "User {$email} updated with peppered hash.\n";
}

echo "Semua password user berhasil diperbarui dengan HMAC-SHA256 Pepper + Argon2id/Bcrypt.\n";
