<?php

class RateLimiter
{
    private string $filePath;
    private int $windowSeconds;
    private int $maxAttempts;

    public function __construct(string $storageDir, int $windowSeconds, int $maxAttempts)
    {
        $this->filePath = rtrim($storageDir, '/\\') . '/ratelimit.json';
        $this->windowSeconds = $windowSeconds;
        $this->maxAttempts = $maxAttempts;
    }

    public function tooManyAttempts(string $key): bool
    {
        $data = $this->readData();
        if (!isset($data[$key])) {
            return false;
        }

        $record = $data[$key];
        if (time() > $record['resetAt']) {
            return false;
        }

        return $record['count'] >= $this->maxAttempts;
    }

    public function hit(string $key): void
    {
        $data = $this->readData();
        $now = time();

        if (!isset($data[$key]) || $now > $data[$key]['resetAt']) {
            $data[$key] = [
                'count' => 1,
                'resetAt' => $now + $this->windowSeconds,
            ];
        } else {
            $data[$key]['count']++;
        }

        $this->writeData($data);
    }

    public function clear(string $key): void
    {
        $data = $this->readData();
        if (isset($data[$key])) {
            unset($data[$key]);
            $this->writeData($data);
        }
    }

    private function readData(): array
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

    private function writeData(array $data): void
    {
        $handle = fopen($this->filePath, 'c+');
        if ($handle === false) {
            return;
        }

        flock($handle, LOCK_EX);
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
