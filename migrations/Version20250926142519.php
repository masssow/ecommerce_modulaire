<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250926142519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inventory_stock DROP FOREIGN KEY FK_97AAFD60A80EF684');
        $this->addSql('ALTER TABLE inventory_stock ADD CONSTRAINT FK_97AAFD60A80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id)');
        $this->addSql('ALTER TABLE shipping_method ADD free_shipping_threshold INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inventory_stock DROP FOREIGN KEY FK_97AAFD60A80EF684');
        $this->addSql('ALTER TABLE inventory_stock ADD CONSTRAINT FK_97AAFD60A80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shipping_method DROP free_shipping_threshold');
    }
}
