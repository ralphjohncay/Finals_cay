<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408120500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync schema for messenger table and index names.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE order_items RENAME INDEX idx_62809db98d9f6d38 TO IDX_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE order_items RENAME INDEX idx_62809db9ed5ca9e6 TO IDX_62809DB0ED5CA9E6');
        $this->addSql('ALTER TABLE order_items RENAME INDEX idx_62809db94584665a TO IDX_62809DB04584665A');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_1483a5e9fb0eea95 TO UNIQ_1483A5E9C1CC006B');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users RENAME INDEX UNIQ_1483A5E9C1CC006B TO uniq_1483a5e9fb0eea95');
        $this->addSql('ALTER TABLE order_items RENAME INDEX IDX_62809DB04584665A TO idx_62809db94584665a');
        $this->addSql('ALTER TABLE order_items RENAME INDEX IDX_62809DB0ED5CA9E6 TO idx_62809db9ed5ca9e6');
        $this->addSql('ALTER TABLE order_items RENAME INDEX IDX_62809DB08D9F6D38 TO idx_62809db98d9f6d38');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

