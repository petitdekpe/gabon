<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260906220207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute site_image (images du site remplaçables depuis le back-office).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE site_image (id BINARY(16) NOT NULL, slot VARCHAR(50) NOT NULL, filename VARCHAR(150) NOT NULL, original_filename VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, updated_by_id BINARY(16) DEFAULT NULL, INDEX IDX_167D45A896DBBDE (updated_by_id), UNIQUE INDEX UNIQ_167D45AAC0E2067 (slot), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE site_image ADD CONSTRAINT FK_167D45A896DBBDE FOREIGN KEY (updated_by_id) REFERENCES admin_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site_image DROP FOREIGN KEY FK_167D45A896DBBDE');
        $this->addSql('DROP TABLE site_image');
    }
}
