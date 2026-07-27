<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260727221506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables du recensement de la diaspora gabonaise (registrant, identity_document, student_profile, employee_profile, entrepreneur_profile).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE employee_profile (id BINARY(16) NOT NULL, last_degree VARCHAR(255) NOT NULL, first_job_year INT NOT NULL, activity_sector VARCHAR(255) NOT NULL, job_title VARCHAR(255) NOT NULL, current_company VARCHAR(255) NOT NULL, wish_integrate_gabon TINYINT NOT NULL, target_company VARCHAR(255) DEFAULT NULL, other_companies JSON DEFAULT NULL, cv_file VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entrepreneur_profile (id BINARY(16) NOT NULL, company_name VARCHAR(255) NOT NULL, created_at DATE NOT NULL, legal_status VARCHAR(20) NOT NULL, activity_sector VARCHAR(255) NOT NULL, registration_number VARCHAR(100) NOT NULL, has_gabon_project TINYINT NOT NULL, investment_sector VARCHAR(255) DEFAULT NULL, has_business_plan TINYINT NOT NULL, other_targets JSON DEFAULT NULL, business_plan_file VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE identity_document (id BINARY(16) NOT NULL, passport_number VARCHAR(50) NOT NULL, passport_issued_at DATE NOT NULL, passport_expires_at DATE NOT NULL, resident_card_number VARCHAR(50) DEFAULT NULL, resident_card_issued_at DATE DEFAULT NULL, resident_card_expires_at DATE DEFAULT NULL, consular_card_number VARCHAR(50) NOT NULL, consular_card_issued_at DATE NOT NULL, consular_card_expires_at DATE NOT NULL, passport_file VARCHAR(255) DEFAULT NULL, resident_card_file VARCHAR(255) DEFAULT NULL, consular_card_file VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE registrant (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, country VARCHAR(2) NOT NULL, last_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, birth_date DATE NOT NULL, birth_place VARCHAR(255) NOT NULL, birth_country VARCHAR(255) NOT NULL, profession VARCHAR(255) NOT NULL, local_phone VARCHAR(20) NOT NULL, gabon_contact VARCHAR(20) NOT NULL, first_entry_date DATE NOT NULL, profile_type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, consent_given TINYINT NOT NULL, identity_document_id BINARY(16) NOT NULL, student_profile_id BINARY(16) DEFAULT NULL, employee_profile_id BINARY(16) DEFAULT NULL, entrepreneur_profile_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_66135AC07C97FC13 (identity_document_id), UNIQUE INDEX UNIQ_66135AC02125FF59 (student_profile_id), UNIQUE INDEX UNIQ_66135AC0542DA26C (employee_profile_id), UNIQUE INDEX UNIQ_66135AC03BAA560C (entrepreneur_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_profile (id BINARY(16) NOT NULL, in_training TINYINT NOT NULL, end_of_cycle TINYINT NOT NULL, awaiting_defense TINYINT NOT NULL, graduated_job_seeking TINYINT NOT NULL, institution VARCHAR(255) NOT NULL, wish_return_gabon TINYINT NOT NULL, target_administration VARCHAR(255) DEFAULT NULL, other_administrations JSON DEFAULT NULL, diploma_file VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE registrant ADD CONSTRAINT FK_66135AC07C97FC13 FOREIGN KEY (identity_document_id) REFERENCES identity_document (id)');
        $this->addSql('ALTER TABLE registrant ADD CONSTRAINT FK_66135AC02125FF59 FOREIGN KEY (student_profile_id) REFERENCES student_profile (id)');
        $this->addSql('ALTER TABLE registrant ADD CONSTRAINT FK_66135AC0542DA26C FOREIGN KEY (employee_profile_id) REFERENCES employee_profile (id)');
        $this->addSql('ALTER TABLE registrant ADD CONSTRAINT FK_66135AC03BAA560C FOREIGN KEY (entrepreneur_profile_id) REFERENCES entrepreneur_profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE registrant DROP FOREIGN KEY FK_66135AC07C97FC13');
        $this->addSql('ALTER TABLE registrant DROP FOREIGN KEY FK_66135AC02125FF59');
        $this->addSql('ALTER TABLE registrant DROP FOREIGN KEY FK_66135AC0542DA26C');
        $this->addSql('ALTER TABLE registrant DROP FOREIGN KEY FK_66135AC03BAA560C');
        $this->addSql('DROP TABLE employee_profile');
        $this->addSql('DROP TABLE entrepreneur_profile');
        $this->addSql('DROP TABLE identity_document');
        $this->addSql('DROP TABLE registrant');
        $this->addSql('DROP TABLE student_profile');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
