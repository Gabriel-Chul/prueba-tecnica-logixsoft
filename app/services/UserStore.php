<?php

class UserStore
{
    private string $filePath;

    public function __construct(string $storageDir)
    {
        $this->filePath = rtrim($storageDir, '/\\') . '/users.json';
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
        if (!file_exists($this->filePath)) {
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
        return is_array($data) ? $data : [];
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
        fwrite($handle, json_encode($users, JSON_PRETTY_PRINT));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
