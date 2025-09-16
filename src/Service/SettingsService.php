<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SettingsService
{
    private const CACHE_KEY = 'app.settings';
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly SettingRepository      $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheItemPoolInterface $cache,
        private readonly SettingsConfigService  $configService,
    )
    {
    }

    public function get(string $name, mixed $default = null): mixed
    {
        // First try to get from database
        $settings = $this->getAllSettings();
        $value = $settings[$name] ?? null;

        // If not found in database, get default from defaults
        if ($value === null) {
            $defaults = $this->configService->getSettings();
            $value = $defaults[$name]['default'] ?? $default;
        }

        // Convert value based on type definition
        return $this->convertValueFromString($name, $value);
    }

    public function set(string $name, mixed $value): void
    {
        // Convert value to string for storage first
        $stringValue = $this->convertValueToString($value);
        $setting = $this->repository->findOneBy(['name' => $name]);

        if (!$setting) {
            // Create new setting with value immediately
            $setting = new Setting($name, $stringValue);
            $this->entityManager->persist($setting);
        } else {
            $setting->setValue($stringValue);
        }

        $this->entityManager->flush();

        // Clear cache
        $this->cache->deleteItem(self::CACHE_KEY);
    }

    public function setAll(array $settings): void
    {
        $defaults = $this->configService->getSettings();

        foreach ($settings as $name => $value) {
            // Skip undefined settings
            if (!isset($defaults[$name])) {
                continue;
            }

            // Convert value to string for storage first
            $stringValue = $this->convertValueToString($value);
            $setting = $this->repository->findOneBy(['name' => $name]);

            if (!$setting) {
                // Create new setting with value immediately
                $setting = new Setting($name, $stringValue);
                $this->entityManager->persist($setting);
            } else {
                $setting->setValue($stringValue);
            }
        }

        // Flush all changes at once
        $this->entityManager->flush();

        // Clear cache once after all changes
        $this->cache->deleteItem(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllSettings(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_TTL);

            $settings = $this->repository->findAll();
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->getName()] = $setting->getValue();
            }

            return $result;
        });
    }

    public function clearCache(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
    }

    private function convertValueToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string)$value;
    }

    private function convertValueFromString(string $name, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $defaults = $this->configService->getSettings();
        $type = $defaults[$name]['type'] ?? 'string';

        // If value is already the correct type (from default values), return as-is
        if ($type === 'boolean' && is_bool($value)) {
            return $value;
        }
        if ($type === 'integer' && is_int($value)) {
            return $value;
        }
        if ($type === 'float' && (is_float($value) || is_int($value))) {
            return (float)$value;
        }
        if ($type === 'array' && is_array($value)) {
            return $value;
        }

        // Convert string values from database
        if ($value === '') {
            return null;
        }

        return match ($type) {
            'boolean' => in_array($value, ['1', 'true', true, 1], true),
            'integer' => (int)$value,
            'float' => (float)$value,
            'array' => is_string($value) ? json_decode($value, true) : $value, // These are string-based field types
            default => (string)$value,
        };
    }
}
