<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250926135011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Properly alter inventory_stock.product_variant_id (drop FK/index -> alter -> recreate)';
    }

    public function up(Schema $schema): void
    {
        // 1) Drop FK + unique index
        $this->addSql('ALTER TABLE inventory_stock DROP FOREIGN KEY FK_97AAFD60A80EF684');
        $this->addSql('DROP INDEX UNIQ_97AAFD60A80EF684 ON inventory_stock');

        // 2) Alter column (⚠️ adapte cette ligne à ton besoin)
        // Exemples :
        // $this->addSql("ALTER TABLE inventory_stock CHANGE product_variant_id product_variant_id INT NOT NULL");
         $this->addSql("ALTER TABLE inventory_stock CHANGE product_variant_id product_variant_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE inventory_stock CHANGE product_variant_id product_variant_id INT NOT NULL");

        // 3) Recreate unique index + FK (choisis ta stratégie de delete)
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97AAFD60A80EF684 ON inventory_stock (product_variant_id)');
        $this->addSql('ALTER TABLE inventory_stock ADD CONSTRAINT FK_97AAFD60A80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON DELETE CASCADE');
        // Remplace CASCADE par RESTRICT/SET NULL si c’est ce que tu veux (et rends la colonne nullable si SET NULL).
    }

    public function down(Schema $schema): void
    {
        // Inverse
        $this->addSql('ALTER TABLE inventory_stock DROP FOREIGN KEY FK_97AAFD60A80EF684');
        $this->addSql('DROP INDEX UNIQ_97AAFD60A80EF684 ON inventory_stock');
        $this->addSql("ALTER TABLE inventory_stock CHANGE product_variant_id product_variant_id INT DEFAULT NULL");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97AAFD60A80EF684 ON inventory_stock (product_variant_id)');
        $this->addSql('ALTER TABLE inventory_stock ADD CONSTRAINT FK_97AAFD60A80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON DELETE SET NULL');
    }

    
}
