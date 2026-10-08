<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DBAL 4 no longer stores the type in column comments; databases created with DBAL 3 (such as
 * production) still have "(DC2Type:datetime_immutable)" on these columns, so every schema diff
 * proposed removing it. Only metadata changes; on a database created by the baseline it is a no-op.
 */
final class Version20261008190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the legacy DBAL 3 type comments from the datetime columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog CHANGE created_at created_at DATETIME NOT NULL, CHANGE start_date start_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event CHANGE start_date start_date DATETIME NOT NULL, CHANGE end_date end_date DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL, CHANGE published_at published_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event_chronicle CHANGE published_at published_at DATETIME DEFAULT NULL, CHANGE start_date start_date DATETIME NOT NULL, CHANGE end_date end_date DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event_invitation CHANGE published_at published_at DATETIME DEFAULT NULL, CHANGE start_date start_date DATETIME NOT NULL, CHANGE end_date end_date DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event_route CHANGE created_at created_at DATETIME NOT NULL, CHANGE event_date event_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE start_date start_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE event CHANGE start_date start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE end_date end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE modified_at modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE published_at published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE event_chronicle CHANGE published_at published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE start_date start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE end_date end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE modified_at modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE event_invitation CHANGE published_at published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE start_date start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE end_date end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE modified_at modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE event_route CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE event_date event_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
