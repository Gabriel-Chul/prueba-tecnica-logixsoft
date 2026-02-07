<?php

class UserStore
{
    private string $filePath;
    private string $encryptionKey;
    private static bool $cacheLoaded = false;
    private static array $cachedUsers = [];

    public function __construct(string $storageDir, string $encryptionKey)
    {
        $this->filePath = rtrim($storageDir, '/\\') . '/users.json';
        $this->encryptionKey = $this->normalizeKey($encryptionKey);
    }

    public function findByEmail(string $email): ?array
    {
        $users = $this->readUsers();
        foreach ($users as $user) {
            if (isset($user['email']) && $user['email'] === $email) {
                return $user;
            }
        }

        return null;
    }

    public function addUser(array $user): void
    {
        $users = $this->readUsers();
        $users[] = $user;
        $this->writeUsers($users);
    }

    private function readUsers(): array
    {
        if (self::$cacheLoaded) {
            return self::$cachedUsers;
        }

        if (!file_exists($this->filePath)) {
            self::$cacheLoaded = true;
            self::$cachedUsers = [];
            return [];
        }

        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            return [];
        }

        flock($handle, LOCK_SH);
        $contents = stream_get_contents($handle) ?: '';
        flock($handle, LOCK_UN);
        fclose($handle);

        $data = json_decode($contents, true);
        if (is_array($data) && isset($data['data'], $data['iv'], $data['tag'])) {
            self::$cachedUsers = $this->decryptUsers($data);
        } else {
            self::$cachedUsers = is_array($data) ? $data : [];
        }

        self::$cacheLoaded = true;
        return self::$cachedUsers;
    }

    private function writeUsers(array $users): void
    {
        $handle = fopen($this->filePath, 'c+');
        if ($handle === false) {
            return;
        }

        flock($handle, LOCK_EX);
        ftruncate($handle, 0);
        rewind($handle);
        $encrypted = $this->encryptUsers($users);
        fwrite($handle, json_encode($encrypted, JSON_PRETTY_PRINT));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        self::$cachedUsers = $users;
        self::$cacheLoaded = true;
    }

    private function encryptUsers(array $users): array
    {
        $plaintext = json_encode($users);
        if ($plaintext === false) {
            return [];
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($cipher === false) {
            return [];
        }

        return [
            'v' => 1,
            'alg' => 'AES-256-GCM',
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($cipher),
        ];
    }

    private function decryptUsers(array $payload): array
    {
        $iv = base64_decode((string)($payload['iv'] ?? ''), true);
        $tag = base64_decode((string)($payload['tag'] ?? ''), true);
        $data = base64_decode((string)($payload['data'] ?? ''), true);

        if ($iv === false || $tag === false || $data === false) {
            return [];
        }

        $plaintext = openssl_decrypt(
            $data,
            'aes-256-gcm',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            return [];
        }

        $decoded = json_decode($plaintext, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeKey(string $key): string
    {
        $prefix = 'base64:';
        if (strpos($key, $prefix) === 0) {
            $decoded = base64_decode(substr($key, strlen($prefix)), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return hash('sha256', $key, true);
    }
}
