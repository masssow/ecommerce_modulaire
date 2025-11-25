<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125165213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE support_message (id INT AUTO_INCREMENT NOT NULL, order_request_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, email VARCHAR(180) NOT NULL, subject VARCHAR(255) DEFAULT NULL, body LONGTEXT NOT NULL, order_number VARCHAR(150) DEFAULT NULL, request_kind VARCHAR(255) DEFAULT NULL, credit INT DEFAULT NULL, status VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B883883F1A445F0 (order_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE support_message ADD CONSTRAINT FK_B883883F1A445F0 FOREIGN KEY (order_request_id) REFERENCES `order` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE support_message DROP FOREIGN KEY FK_B883883F1A445F0');
        $this->addSql('DROP TABLE support_message');
    }
}
