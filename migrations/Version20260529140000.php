<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer_notifications for mobile activity alerts (orders, products).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customer_notifications (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            category VARCHAR(30) NOT NULL,
            event VARCHAR(40) NOT NULL,
            title VARCHAR(120) NOT NULL,
            message LONGTEXT NOT NULL,
            type VARCHAR(20) DEFAULT \'info\' NOT NULL,
            entity_type VARCHAR(50) DEFAULT NULL,
            entity_id INT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_customer_notif_user (user_id),
            INDEX idx_customer_notif_created (created_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_customer_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE customer_notifications');
    }
}
