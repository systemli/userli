<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Helper\TotpBackupCodeGenerator;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Backup codes are shown only once, right after they are created. Until the user confirms
 * having stored them, the new codes are kept in the session, not read from the user.
 */
final readonly class TwofactorBackupCodeService
{
    private const string SESSION_NEW_CODES = 'twofactor_new_backup_codes';

    public function __construct(
        private TotpBackupCodeGenerator $generator,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Replaces the backup codes of the user, the caller flushes.
     */
    public function regenerate(User $user): void
    {
        $codes = $this->generator->generate();
        $user->setTotpBackupCodes($codes);
        $this->requestStack->getSession()->set(self::SESSION_NEW_CODES, $codes);
    }

    /**
     * @return string[]|null the codes created in this session and not confirmed yet
     */
    public function getNewCodes(): ?array
    {
        $codes = $this->requestStack->getSession()->get(self::SESSION_NEW_CODES);

        return is_array($codes) && [] !== $codes ? array_values(array_map(strval(...), $codes)) : null;
    }

    public function clearNewCodes(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_NEW_CODES);
    }
}
