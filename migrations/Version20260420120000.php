<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260420120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Division and Coordinacion organizational hierarchy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE kimai2_divisions (id INT AUTO_INCREMENT NOT NULL, lider_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, visible TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_division_name (name), UNIQUE INDEX UNIQ_division_lider (lider_id), INDEX IDX_division_visible (visible), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE kimai2_divisions ADD CONSTRAINT FK_div_lider FOREIGN KEY (lider_id) REFERENCES kimai2_users (id) ON DELETE SET NULL');
        $this->addSql('CREATE TABLE kimai2_coordinaciones (id INT AUTO_INCREMENT NOT NULL, division_id INT NOT NULL, coordinador_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, visible TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_coord_name_per_division (name, division_id), UNIQUE INDEX UNIQ_coord_coordinador (coordinador_id), INDEX IDX_coord_division (division_id), INDEX IDX_coord_visible (visible), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE kimai2_coordinaciones ADD CONSTRAINT FK_coord_division FOREIGN KEY (division_id) REFERENCES kimai2_divisions (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE kimai2_coordinaciones ADD CONSTRAINT FK_coord_coordinador FOREIGN KEY (coordinador_id) REFERENCES kimai2_users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE kimai2_projects ADD coordinacion_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_407F1206360A43D5 ON kimai2_projects (coordinacion_id)');
        $this->addSql('ALTER TABLE kimai2_projects ADD CONSTRAINT FK_proj_coordinacion FOREIGN KEY (coordinacion_id) REFERENCES kimai2_coordinaciones (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_proj_coordinacion ON kimai2_projects');
        $this->addSql('ALTER TABLE kimai2_projects DROP COLUMN coordinacion_id');

        $coordTable = $schema->getTable('kimai2_coordinaciones');
        $coordTable->removeForeignKey('FK_coord_division');
        $coordTable->removeForeignKey('FK_coord_coordinador');
        $schema->dropTable('kimai2_coordinaciones');

        $divTable = $schema->getTable('kimai2_divisions');
        $divTable->removeForeignKey('FK_div_lider');
        $schema->dropTable('kimai2_divisions');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
