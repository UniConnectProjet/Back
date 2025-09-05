<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905094953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence ADD minutes_late INT DEFAULT NULL, ADD justification_note LONGTEXT DEFAULT NULL, ADD recorded_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9D05A957B FOREIGN KEY (recorded_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_765AE0C9D05A957B ON absence (recorded_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9D05A957B');
        $this->addSql('DROP INDEX IDX_765AE0C9D05A957B ON absence');
        $this->addSql('ALTER TABLE absence DROP minutes_late, DROP justification_note, DROP recorded_by_id');
    }
}
