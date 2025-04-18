<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250418124831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE semester_student (semester_id INT NOT NULL, student_id INT NOT NULL, INDEX IDX_11AAE6DC4A798B6F (semester_id), INDEX IDX_11AAE6DCCB944F1A (student_id), PRIMARY KEY(semester_id, student_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_student ADD CONSTRAINT FK_11AAE6DC4A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_student ADD CONSTRAINT FK_11AAE6DCCB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_semester DROP FOREIGN KEY FK_44B3C276CB944F1A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_semester DROP FOREIGN KEY FK_44B3C2764A798B6F
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE student_semester
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE student_semester (student_id INT NOT NULL, semester_id INT NOT NULL, INDEX IDX_44B3C276CB944F1A (student_id), INDEX IDX_44B3C2764A798B6F (semester_id), PRIMARY KEY(student_id, semester_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_semester ADD CONSTRAINT FK_44B3C276CB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_semester ADD CONSTRAINT FK_44B3C2764A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_student DROP FOREIGN KEY FK_11AAE6DC4A798B6F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_student DROP FOREIGN KEY FK_11AAE6DCCB944F1A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE semester_student
        SQL);
    }
}
