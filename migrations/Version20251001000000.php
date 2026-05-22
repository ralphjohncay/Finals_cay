<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251001000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema (users, products, services, orders, order_items).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE products (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(10, 2) NOT NULL, category VARCHAR(100) NOT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE services (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price NUMERIC(10, 2) NOT NULL, category VARCHAR(100) NOT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, order_date DATETIME NOT NULL, total_price NUMERIC(10, 2) NOT NULL, status VARCHAR(50) NOT NULL, INDEX IDX_E52FFDEE9395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE9395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id)');

        $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, service_id INT DEFAULT NULL, product_id INT DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(10, 2) NOT NULL, quantity INT NOT NULL, INDEX IDX_62809DB98D9F6D38 (order_id), INDEX IDX_62809DB9ED5CA9E6 (service_id), INDEX IDX_62809DB94584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB98D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB9ED5CA9E6 FOREIGN KEY (service_id) REFERENCES services (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB94584665A FOREIGN KEY (product_id) REFERENCES products (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB98D9F6D38');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB9ED5CA9E6');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB94584665A');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE9395C3F3');

        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE services');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE users');
    }
}

