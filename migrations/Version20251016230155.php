<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016230155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_log (id INT AUTO_INCREMENT NOT NULL, order_ref_id INT DEFAULT NULL, template_id INT DEFAULT NULL, recipient VARCHAR(255) DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, body_html VARCHAR(255) DEFAULT NULL, success TINYINT(1) DEFAULT NULL, error_message VARCHAR(255) DEFAULT NULL, sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6FB4883E238517C (order_ref_id), INDEX IDX_6FB48835DA0FB8 (template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB4883E238517C FOREIGN KEY (order_ref_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB48835DA0FB8 FOREIGN KEY (template_id) REFERENCES email_template (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_log DROP FOREIGN KEY FK_6FB4883E238517C');
        $this->addSql('ALTER TABLE email_log DROP FOREIGN KEY FK_6FB48835DA0FB8');
        $this->addSql('DROP TABLE email_log');
    }
}
