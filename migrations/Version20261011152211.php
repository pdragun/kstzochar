<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** String columns are VARCHAR(255), display_name was the only VARCHAR(190) */
final class Version20261011152211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen user.display_name to VARCHAR(255)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE display_name display_name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE display_name display_name VARCHAR(190) NOT NULL');
    }
}
