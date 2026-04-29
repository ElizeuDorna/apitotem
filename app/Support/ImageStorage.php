<?php

namespace App\Support;

class ImageStorage
{
    public const DISK = 'images';

    public const PUBLIC_PREFIX = '/storage-images/';

    public static function disk(): string
    {
        return self::DISK;
    }

    public static function publicUrl(string $path): string
    {
        return self::PUBLIC_PREFIX.ltrim($path, '/');
    }

    public static function isInternalPublicPath(string $value): bool
    {
        $normalized = trim($value);

        return str_starts_with($normalized, self::PUBLIC_PREFIX)
            || str_starts_with($normalized, '/storage/')
            || str_starts_with($normalized, 'storage/')
            || str_starts_with($normalized, 'storage-images/');
    }

    public static function isValidImagePathOrUrl(string $value): bool
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return true;
        }

        if (self::isInternalPublicPath($normalized)) {
            return true;
        }

        return filter_var($normalized, FILTER_VALIDATE_URL) !== false;
    }

    public static function normalizePublicUrl(string $raw): string
    {
        $value = trim($raw);

        if ($value === '') {
            return '';
        }

        if (preg_match('#^https?://localhost/storage-images/(.+)$#i', $value, $matches)) {
            return self::PUBLIC_PREFIX.ltrim((string) ($matches[1] ?? ''), '/');
        }

        if (preg_match('#^https?://localhost/storage/(.+)$#i', $value, $matches)) {
            return '/storage/'.ltrim((string) ($matches[1] ?? ''), '/');
        }

        if (str_starts_with($value, 'storage-images/')) {
            return '/'.ltrim($value, '/');
        }

        if (str_starts_with($value, 'storage/')) {
            return '/'.ltrim($value, '/');
        }

        return $value;
    }

    public static function extractRelativePathFromPublicUrl(string $url): string
    {
        $value = trim($url);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, self::PUBLIC_PREFIX)) {
            return ltrim(substr($value, strlen(self::PUBLIC_PREFIX)), '/');
        }

        if (str_starts_with($value, '/storage/')) {
            return ltrim(substr($value, 9), '/');
        }

        if (str_starts_with($value, 'storage-images/')) {
            return ltrim(substr($value, 15), '/');
        }

        if (str_starts_with($value, 'storage/')) {
            return ltrim(substr($value, 8), '/');
        }

        return '';
    }
}