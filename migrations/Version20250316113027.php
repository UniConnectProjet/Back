<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250316113027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema migration adapted for MariaDB';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE absence (
            id INT AUTO_INCREMENT NOT NULL,
            student_id INT DEFAULT NULL,
            started_date DATETIME NOT NULL,
            ended_date DATETIME NOT NULL,
            justified TINYINT(1) NOT NULL,
            justification VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX IDX_765AE0C9CB944F1A ON absence (student_id)');

        $this->addSql('CREATE TABLE classe (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE course (
            id INT AUTO_INCREMENT NOT NULL,
            course_unit_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            average DOUBLE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX IDX_169E6FB9F07E75E1 ON course (course_unit_id)');

        $this->addSql('CREATE TABLE course_unit (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            semester INT NOT NULL,
            average DOUBLE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE grade (
            id INT AUTO_INCREMENT NOT NULL,
            student_id INT DEFAULT NULL,
            course_id INT DEFAULT NULL,
            grade DOUBLE NOT NULL,
            dividor DOUBLE NOT NULL,
            title VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX IDX_595AAE34CB944F1A ON grade (student_id)');
        $this->addSql('CREATE INDEX IDX_595AAE34591CC992 ON grade (course_id)');

        $this->addSql('CREATE TABLE student (
            id INT AUTO_INCREMENT NOT NULL,
            classe_id INT NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX IDX_B723AF338F5EA509 ON student (classe_id)');

        $this->addSql('CREATE TABLE `user` (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            lastname VARCHAR(255) NOT NULL,
            birthday DATETIME NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON `user` (email)');

        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB9F07E75E1 FOREIGN KEY (course_unit_id) REFERENCES course_unit (id)');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE34CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE34591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF338F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9CB944F1A');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB9F07E75E1');
        $this->addSql('ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34CB944F1A');
        $this->addSql('ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34591CC992');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF338F5EA509');

        $this->addSql('DROP TABLE absence');
        $this->addSql('DROP TABLE classe');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE course_unit');
        $this->addSql('DROP TABLE grade');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE `user`');
    }
}