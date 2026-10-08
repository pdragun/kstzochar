<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Start location of event invitations (#19) */
final class Version20261008190100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the location table and event_invitation.location_id';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('location')) {
            $this->write('The location table already exists (created by hand from the SQL in #19), only recorded as executed.');

            return;
        }

        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address_locality VARCHAR(255) NOT NULL, address_country VARCHAR(2) NOT NULL, street_address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(16) DEFAULT NULL, address_region VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, UNIQUE INDEX UNIQ_5E9E89CB5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE event_invitation ADD location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event_invitation ADD CONSTRAINT FK_A9F3B88D64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_A9F3B88D64D218E ON event_invitation (location_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event_invitation DROP FOREIGN KEY FK_A9F3B88D64D218E');
        $this->addSql('DROP INDEX IDX_A9F3B88D64D218E ON event_invitation');
        $this->addSql('ALTER TABLE event_invitation DROP location_id');
        $this->addSql('DROP TABLE location');
    }
}
