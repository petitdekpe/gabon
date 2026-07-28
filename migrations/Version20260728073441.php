<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260728073441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute alert_log, audit_log, et l'e-mail de l'inscrit sur registrant.";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alert_log (id BINARY(16) NOT NULL, document VARCHAR(20) NOT NULL, sent_at DATETIME NOT NULL, channel VARCHAR(10) NOT NULL, registrant_id BINARY(16) NOT NULL, INDEX IDX_5A7313193304A716 (registrant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audit_log (id BINARY(16) NOT NULL, action VARCHAR(50) NOT NULL, entity_type VARCHAR(100) DEFAULT NULL, entity_id VARCHAR(36) DEFAULT NULL, payload JSON DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, occurred_at DATETIME NOT NULL, admin_user_id BINARY(16) DEFAULT NULL, INDEX IDX_F6E1C0F56352511C (admin_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE alert_log ADD CONSTRAINT FK_5A7313193304A716 FOREIGN KEY (registrant_id) REFERENCES registrant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F56352511C FOREIGN KEY (admin_user_id) REFERENCES admin_user (id) ON DELETE SET NULL');
        // Ajouté nullable puis complété + verrouillé en NOT NULL, pour ne pas casser
        // les lignes déjà en base (le formulaire d'inscription impose déjà l'e-mail).
        $this->addSql('ALTER TABLE registrant ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE registrant SET email = CONCAT('inconnu+', HEX(id), '@a-completer.invalid') WHERE email IS NULL");
        $this->addSql('ALTER TABLE registrant MODIFY email VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert_log DROP FOREIGN KEY FK_5A7313193304A716');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F56352511C');
        $this->addSql('DROP TABLE alert_log');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('ALTER TABLE registrant DROP email');
    }
}
