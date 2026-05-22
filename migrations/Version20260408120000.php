<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification fields to users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL, ADD verification_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9FB0EEA95 ON users (verification_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1483A5E9FB0EEA95 ON users');
        $this->addSql('ALTER TABLE users DROP is_verified, DROP verification_token');
    }
}

