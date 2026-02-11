<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ReservedName;
use App\Repository\ReservedNameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ReservedNameManager
{
    private const int PAGE_SIZE = 20;

    public function __construct(
        private EntityManagerInterface $em,
        private ReservedNameRepository $repository,
    ) {
    }

    /**
     * Find reserved names with offset-based pagination and optional search.
     *
     * @return array{items: ReservedName[], page: int, totalPages: int, total: int}
     */
    public function findPaginated(int $page = 1, string $search = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $total = $this->repository->countBySearch($search);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $items = $this->repository->findPaginatedBySearch($search, self::PAGE_SIZE, $offset);

        return [
            'items' => $items,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ];
    }

    public function create(string $name): ReservedName
    {
        $reservedName = new ReservedName();
        $reservedName->setName($name);

        $this->em->persist($reservedName);
        $this->em->flush();

        return $reservedName;
    }

    public function delete(ReservedName $reservedName): void
    {
        $this->em->remove($reservedName);
        $this->em->flush();
    }

    /**
     * Import reserved names from an uploaded file.
     * Skips existing entries and comments (lines starting with #).
     *
     * @return array{imported: int, skipped: int}
     */
    public function importFromFile(UploadedFile $file): array
    {
        $imported = 0;
        $skipped = 0;

        $handle = fopen($file->getPathname(), 'r');
        if (false === $handle) {
            return ['imported' => $imported, 'skipped' => $skipped];
        }

        while ($line = fgets($handle)) {
            $name = trim($line);
            if ('' === $name) {
                continue;
            }

            if ('#' === $name[0]) {
                continue;
            }

            $name = strtolower($name);

            if (null !== $this->repository->findByName($name)) {
                ++$skipped;
                continue;
            }

            try {
                $this->create($name);
                ++$imported;
            } catch (Exception) {
                ++$skipped;
            }
        }

        fclose($handle);

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Export all reserved names as a text string (one name per line).
     */
    public function exportAsText(): string
    {
        $reservedNames = $this->repository->findBy([], ['name' => 'ASC']);
        $lines = array_map(static fn (ReservedName $r) => $r->getName(), $reservedNames);

        return implode("\n", $lines)."\n";
    }
}
