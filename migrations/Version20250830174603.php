<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250830174603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence ADD status VARCHAR(20) DEFAULT \'UNJUSTIFIED\' NOT NULL, ADD justification_reason VARCHAR(100) DEFAULT NULL, ADD justification_comment LONGTEXT DEFAULT NULL, ADD justification_files JSON DEFAULT NULL, ADD justified_at DATETIME DEFAULT NULL, ADD review_comment LONGTEXT DEFAULT NULL, ADD reviewed_at DATETIME DEFAULT NULL, ADD justified_by_id INT DEFAULT NULL, ADD reviewed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9CD130C9C FOREIGN KEY (justified_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9FC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_765AE0C9CD130C9C ON absence (justified_by_id)');
        $this->addSql('CREATE INDEX IDX_765AE0C9FC6B21F1 ON absence (reviewed_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9CD130C9C');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9FC6B21F1');
        $this->addSql('DROP INDEX IDX_765AE0C9CD130C9C ON absence');
        $this->addSql('DROP INDEX IDX_765AE0C9FC6B21F1 ON absence');
        $this->addSql('ALTER TABLE absence DROP status, DROP justification_reason, DROP justification_comment, DROP justification_files, DROP justified_at, DROP review_comment, DROP reviewed_at, DROP justified_by_id, DROP reviewed_by_id');
    }
}
