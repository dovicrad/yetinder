<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220728165020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_D4E6F818BAC62AF');
        $this->addSql('CREATE TEMPORARY TABLE __temp__address AS SELECT id, city_id, street, street_number, zip FROM address');
        $this->addSql('DROP TABLE address');
        $this->addSql('CREATE TABLE address (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, city_id INTEGER DEFAULT NULL, street VARCHAR(255) NOT NULL, street_number INTEGER NOT NULL, zip VARCHAR(255) NOT NULL, CONSTRAINT FK_D4E6F818BAC62AF FOREIGN KEY (city_id) REFERENCES city (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO address (id, city_id, street, street_number, zip) SELECT id, city_id, street, street_number, zip FROM __temp__address');
        $this->addSql('DROP TABLE __temp__address');
        $this->addSql('CREATE INDEX IDX_D4E6F818BAC62AF ON address (city_id)');
        $this->addSql('DROP INDEX IDX_2D5B0234F92F3E70');
        $this->addSql('CREATE TEMPORARY TABLE __temp__city AS SELECT id, country_id, name FROM city');
        $this->addSql('DROP TABLE city');
        $this->addSql('CREATE TABLE city (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, country_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, CONSTRAINT FK_2D5B0234F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO city (id, country_id, name) SELECT id, country_id, name FROM __temp__city');
        $this->addSql('DROP TABLE __temp__city');
        $this->addSql('CREATE INDEX IDX_2D5B0234F92F3E70 ON city (country_id)');
        $this->addSql('DROP INDEX IDX_6525CCFF5B7AF75');
        $this->addSql('CREATE TEMPORARY TABLE __temp__yeti AS SELECT id, address_id, first_name, last_name, weight, height FROM yeti');
        $this->addSql('DROP TABLE yeti');
        $this->addSql('CREATE TABLE yeti (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, address_id INTEGER DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, weight DOUBLE PRECISION NOT NULL, height DOUBLE PRECISION NOT NULL, CONSTRAINT FK_6525CCFF5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO yeti (id, address_id, first_name, last_name, weight, height) SELECT id, address_id, first_name, last_name, weight, height FROM __temp__yeti');
        $this->addSql('DROP TABLE __temp__yeti');
        $this->addSql('CREATE INDEX IDX_6525CCFF5B7AF75 ON yeti (address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_D4E6F818BAC62AF');
        $this->addSql('CREATE TEMPORARY TABLE __temp__address AS SELECT id, city_id, street, street_number, zip FROM address');
        $this->addSql('DROP TABLE address');
        $this->addSql('CREATE TABLE address (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, city_id INTEGER DEFAULT NULL, street VARCHAR(255) NOT NULL, street_number INTEGER NOT NULL, zip VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO address (id, city_id, street, street_number, zip) SELECT id, city_id, street, street_number, zip FROM __temp__address');
        $this->addSql('DROP TABLE __temp__address');
        $this->addSql('CREATE INDEX IDX_D4E6F818BAC62AF ON address (city_id)');
        $this->addSql('DROP INDEX IDX_2D5B0234F92F3E70');
        $this->addSql('CREATE TEMPORARY TABLE __temp__city AS SELECT id, country_id, name FROM city');
        $this->addSql('DROP TABLE city');
        $this->addSql('CREATE TABLE city (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, country_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO city (id, country_id, name) SELECT id, country_id, name FROM __temp__city');
        $this->addSql('DROP TABLE __temp__city');
        $this->addSql('CREATE INDEX IDX_2D5B0234F92F3E70 ON city (country_id)');
        $this->addSql('DROP INDEX IDX_6525CCFF5B7AF75');
        $this->addSql('CREATE TEMPORARY TABLE __temp__yeti AS SELECT id, address_id, first_name, last_name, weight, height FROM yeti');
        $this->addSql('DROP TABLE yeti');
        $this->addSql('CREATE TABLE yeti (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, address_id INTEGER DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, weight DOUBLE PRECISION NOT NULL, height DOUBLE PRECISION NOT NULL)');
        $this->addSql('INSERT INTO yeti (id, address_id, first_name, last_name, weight, height) SELECT id, address_id, first_name, last_name, weight, height FROM __temp__yeti');
        $this->addSql('DROP TABLE __temp__yeti');
        $this->addSql('CREATE INDEX IDX_6525CCFF5B7AF75 ON yeti (address_id)');
    }
}
