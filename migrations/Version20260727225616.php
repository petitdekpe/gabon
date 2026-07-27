<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260727225616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le numéro de référence unique du dossier soumis sur registrant.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE registrant ADD reference VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_66135AC0AEA34913 ON registrant (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_66135AC0AEA34913 ON registrant');
        $this->addSql('ALTER TABLE registrant DROP reference');
    }
}
