<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016073230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_payment_method (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, gateway VARCHAR(50) DEFAULT NULL, external_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_10E47EAFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_payment_method ADD CONSTRAINT FK_10E47EAFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payment ADD currency VARCHAR(3) DEFAULT NULL, CHANGE amount amount INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_payment_method DROP FOREIGN KEY FK_10E47EAFA76ED395');
        $this->addSql('DROP TABLE user_payment_method');
        $this->addSql('ALTER TABLE payment DROP currency, CHANGE amount amount VARCHAR(255) NOT NULL');
    }
}
