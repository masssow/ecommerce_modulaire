<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016092159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment_instrument (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, gateway VARCHAR(50) DEFAULT NULL, external_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_default TINYINT(1) DEFAULT NULL, brand VARCHAR(255) DEFAULT NULL, last4 VARCHAR(4) DEFAULT NULL, exp_month SMALLINT DEFAULT NULL, exp_year SMALLINT DEFAULT NULL, fingerprint VARCHAR(64) DEFAULT NULL, INDEX IDX_949E808A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment_instrument ADD CONSTRAINT FK_949E808A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_payment_method DROP FOREIGN KEY FK_10E47EAFA76ED395');
        $this->addSql('DROP TABLE user_payment_method');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_payment_method (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, gateway VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, external_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_default TINYINT(1) DEFAULT NULL, brand VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, last4 VARCHAR(4) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, exp_month SMALLINT DEFAULT NULL, exp_year SMALLINT DEFAULT NULL, fingerprint VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_10E47EAFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user_payment_method ADD CONSTRAINT FK_10E47EAFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE payment_instrument DROP FOREIGN KEY FK_949E808A76ED395');
        $this->addSql('DROP TABLE payment_instrument');
    }
}
