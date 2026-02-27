<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE/SET NULL to foreign keys for domain deletion support';
    }

    public function up(Schema $schema): void
    {
        // users.domain_id → domains: CASCADE (delete users when domain is deleted)
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_3C68956A115F0EE5');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_3C68956A115F0EE5 FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE');

        // users.invitation_voucher_id → vouchers: SET NULL (clear reference when voucher is deleted)
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_3C68956AB29B3622');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_3C68956AB29B3622 FOREIGN KEY (invitation_voucher_id) REFERENCES vouchers (id) ON DELETE SET NULL');

        // aliases.domain_id → domains: CASCADE (delete aliases when domain is deleted)
        $this->addSql('ALTER TABLE aliases DROP FOREIGN KEY FK_696568F6115F0EE5');
        $this->addSql('ALTER TABLE aliases ADD CONSTRAINT FK_696568F6115F0EE5 FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE');

        // aliases.user_id → users: SET NULL (clear user reference when user is deleted)
        $this->addSql('ALTER TABLE aliases DROP FOREIGN KEY FK_696568F6A76ED395');
        $this->addSql('ALTER TABLE aliases ADD CONSTRAINT FK_696568F6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');

        // vouchers.domain_id → domains: CASCADE (delete vouchers when domain is deleted)
        $this->addSql('ALTER TABLE vouchers DROP FOREIGN KEY FK_93150748115F0EE5');
        $this->addSql('ALTER TABLE vouchers ADD CONSTRAINT FK_93150748115F0EE5 FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE');

        // vouchers.user_id → users: SET NULL (clear user reference when user is deleted)
        $this->addSql('ALTER TABLE vouchers DROP FOREIGN KEY FK_98F8AFBAA76ED395');
        $this->addSql('ALTER TABLE vouchers ADD CONSTRAINT FK_98F8AFBAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');

        // openpgp_keys.user_id → users: CASCADE (delete keys when user is deleted)
        $this->addSql('ALTER TABLE openpgp_keys DROP FOREIGN KEY FK_3DB259EAA76ED395');
        $this->addSql('ALTER TABLE openpgp_keys ADD CONSTRAINT FK_3DB259EAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Restore original foreign keys without ON DELETE clauses
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_3C68956A115F0EE5');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_3C68956A115F0EE5 FOREIGN KEY (domain_id) REFERENCES domains (id)');

        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_3C68956AB29B3622');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_3C68956AB29B3622 FOREIGN KEY (invitation_voucher_id) REFERENCES vouchers (id)');

        $this->addSql('ALTER TABLE aliases DROP FOREIGN KEY FK_696568F6115F0EE5');
        $this->addSql('ALTER TABLE aliases ADD CONSTRAINT FK_696568F6115F0EE5 FOREIGN KEY (domain_id) REFERENCES domains (id)');

        $this->addSql('ALTER TABLE aliases DROP FOREIGN KEY FK_696568F6A76ED395');
        $this->addSql('ALTER TABLE aliases ADD CONSTRAINT FK_696568F6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        $this->addSql('ALTER TABLE vouchers DROP FOREIGN KEY FK_93150748115F0EE5');
        $this->addSql('ALTER TABLE vouchers ADD CONSTRAINT FK_93150748115F0EE5 FOREIGN KEY (domain_id) REFERENCES domains (id)');

        $this->addSql('ALTER TABLE vouchers DROP FOREIGN KEY FK_98F8AFBAA76ED395');
        $this->addSql('ALTER TABLE vouchers ADD CONSTRAINT FK_98F8AFBAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        $this->addSql('ALTER TABLE openpgp_keys DROP FOREIGN KEY FK_3DB259EAA76ED395');
        $this->addSql('ALTER TABLE openpgp_keys ADD CONSTRAINT FK_3DB259EAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }
}
