<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/** Failed-login log used for throttling (brute-force protection). */
class LoginAttemptRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function record(string $ip, string $email): void
    {
        $this->db->prepare('INSERT INTO login_attempts (ip, email) VALUES (?, ?)')
                 ->execute([substr($ip, 0, 45), substr($email, 0, 190)]);
        // housekeeping: forget anything older than a day
        $this->db->exec('DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY');
    }

    public function countForIp(string $ip, int $minutes): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > NOW() - INTERVAL ' . (int) $minutes . ' MINUTE'
        );
        $stmt->execute([$ip]);
        return (int) $stmt->fetchColumn();
    }

    public function countForIpAndEmail(string $ip, string $email, int $minutes): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND email = ? AND attempted_at > NOW() - INTERVAL ' . (int) $minutes . ' MINUTE'
        );
        $stmt->execute([$ip, substr($email, 0, 190)]);
        return (int) $stmt->fetchColumn();
    }

    /** Seconds until the oldest failure in the window expires (0 if none). */
    public function secondsUntilWindowFrees(string $ip, int $minutes): int
    {
        $stmt = $this->db->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(), MIN(attempted_at) + INTERVAL ' . (int) $minutes . ' MINUTE)
               FROM login_attempts WHERE ip = ? AND attempted_at > NOW() - INTERVAL ' . (int) $minutes . ' MINUTE'
        );
        $stmt->execute([$ip]);
        return max(0, (int) $stmt->fetchColumn());
    }

    public function clear(string $ip, string $email): void
    {
        $this->db->prepare('DELETE FROM login_attempts WHERE ip = ? AND email = ?')
                 ->execute([$ip, substr($email, 0, 190)]);
    }
}
