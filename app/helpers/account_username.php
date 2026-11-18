<?php

function accountUsernameInUse(mysqli $conn, string $username, int $excludedUserId = 0): bool {
    $stmt = $conn->prepare('SELECT user_id FROM accounts WHERE username = ? AND user_id <> ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Unable to check account username.');
    }

    try {
        $stmt->bind_param('si', $username, $excludedUserId);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to check account username.');
        }
        return $stmt->get_result()->fetch_assoc() !== null;
    } finally {
        $stmt->close();
    }
}
