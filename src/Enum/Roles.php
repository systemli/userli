<?php

declare(strict_types=1);

namespace App\Enum;

final class Roles
{
    /**
     * Role for users that should not be deleted (e.g. the main admin account).
     */
    public const string PERMANENT = 'ROLE_PERMANENT';

    /**
     * Role for users that can create voucher codes.
     */
    public const string MULTIPLIER = 'ROLE_MULTIPLIER';

    /**
     * Role for users that are marked as spammers and can't send emails.
     */
    public const string SPAM = 'ROLE_SPAM';

    /**
     * Role for users that are marked as suspicious and don't receive voucher codes.
     */
    public const string SUSPICIOUS = 'ROLE_SUSPICIOUS';

    /**
     * Basic role for all authenticated users.
     */
    public const string USER = 'ROLE_USER';

    /**
     * Role for users that can manage users within their domain.
     */
    public const string DOMAIN_ADMIN = 'ROLE_DOMAIN_ADMIN';

    /**
     * Role for users that can manage everything.
     */
    public const string ADMIN = 'ROLE_ADMIN';

    /**
     * Returns a canonical list of all defined roles.
     *
     * The array is associative and maps each role name to itself. This shape is convenient
     * for Symfony's ChoiceType (choices: [label => value]) and for simple existence checks.
     *
     * Example consumer usage:
     *   $choices = Roles::getAll();            // ['ROLE_USER' => 'ROLE_USER', ...]
     *   $isKnown = isset($choices[$someRole]); // constant-time membership check
     *
     * @return array<string, string> Map of role => role
     */
    public static function getAll(): array
    {
        return [
            self::PERMANENT => self::PERMANENT,
            self::MULTIPLIER => self::MULTIPLIER,
            self::SPAM => self::SPAM,
            self::SUSPICIOUS => self::SUSPICIOUS,
            self::USER => self::USER,
            self::DOMAIN_ADMIN => self::DOMAIN_ADMIN,
            self::ADMIN => self::ADMIN,
        ];
    }

    /**
     * Returns all roles that are reachable (implied) from the given roles.
     *
     * If a provided role is not part of the defined hierarchy, it's ignored.
     * This method performs a direct lookup in the hierarchy table and does not compute
     * transitive closure dynamically. Duplicates are removed while preserving order
     * of appearance.
     *
     * Examples:
     *   Roles::getReachableRoles([Roles::ADMIN]);
     *   // -> ['ROLE_ADMIN', 'ROLE_DOMAIN_ADMIN', 'ROLE_USER', 'ROLE_PERMANENT', 'ROLE_SPAM', 'ROLE_MULTIPLIER', 'ROLE_SUSPICIOUS']
     *
     *   Roles::getReachableRoles([Roles::DOMAIN_ADMIN, Roles::SPAM]);
     *   // -> ['ROLE_USER', 'ROLE_PERMANENT']
     *
     * @param array<int, string> $roles List of role names
     *
     * @return string[] List of role names implied by any given role
     */
    public static function getReachableRoles(array $roles): array
    {
        $hierarchy = self::getRoleHierarchy();

        $reachableMap = [];
        foreach ($roles as $role) {
            if (!isset($hierarchy[$role])) {
                continue;
            }

            foreach ($hierarchy[$role] as $implied) {
                $reachableMap[$implied] = true;
            }
        }

        return array_keys($reachableMap);
    }

    /**
     * Returns the role hierarchy used within the application.
     *
     * The array maps a "root" role to a list of roles that are considered reachable when
     * a user has that root role. To simplify authorization checks, the lists may include
     * the root role itself if desired (see ADMIN below).
     *
     * Expected shape:
     *   [
     *     'ROOT_ROLE' => ['ROLE_A', 'ROLE_B', ...],
     *     // ...
     *   ]
     *
     * @return array<string, string[]> Map of root role => list of reachable roles
     */
    public static function getRoleHierarchy(): array
    {
        return [
            self::DOMAIN_ADMIN => [self::USER, self::PERMANENT],
            self::ADMIN => [self::ADMIN, self::DOMAIN_ADMIN, self::USER, self::PERMANENT, self::SPAM, self::MULTIPLIER, self::SUSPICIOUS],
        ];
    }
}
