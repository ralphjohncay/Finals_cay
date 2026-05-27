<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add app_notifications table for mobile/web announcement bar.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_notifications (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(120) DEFAULT NULL,
            message LONGTEXT NOT NULL,
            type VARCHAR(20) DEFAULT \'info\' NOT NULL,
            audience VARCHAR(20) DEFAULT \'all\' NOT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            priority INT DEFAULT 0 NOT NULL,
            starts_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_notifications');
    }
}
