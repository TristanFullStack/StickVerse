<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la confirmation e-mail et sécurise la suppression des comptes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user ADD email_verifie TINYINT(1) NOT NULL DEFAULT 1, '
            .'ADD jeton_verification_email_hash VARCHAR(64) DEFAULT NULL, '
            .'ADD date_expiration_verification_email DATETIME DEFAULT NULL'
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_USER_VERIFICATION_EMAIL_HASH '
            .'ON user (jeton_verification_email_hash)'
        );

        $this->addSql(
            'ALTER TABLE combat DROP FOREIGN KEY FK_8D51E39892C1E237'
        );
        $this->addSql(
            'ALTER TABLE combat DROP FOREIGN KEY FK_8D51E39880744DD9'
        );
        $this->addSql(
            'ALTER TABLE combat DROP FOREIGN KEY FK_8D51E3982F942B8'
        );
        $this->addSql(
            'ALTER TABLE combat ADD CONSTRAINT FK_8D51E39892C1E237 '
            .'FOREIGN KEY (joueur1_id) REFERENCES user (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE combat ADD CONSTRAINT FK_8D51E39880744DD9 '
            .'FOREIGN KEY (joueur2_id) REFERENCES user (id) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE combat ADD CONSTRAINT FK_8D51E3982F942B8 '
            .'FOREIGN KEY (gagnant_id) REFERENCES user (id) ON DELETE SET NULL'
        );

        $this->addSql(
            'ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA15FB88E14F'
        );
        $this->addSql(
            'ALTER TABLE equipe ADD CONSTRAINT FK_2449BA15FB88E14F '
            .'FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE inventaire DROP FOREIGN KEY FK_338920E0FB88E14F'
        );
        $this->addSql(
            'ALTER TABLE inventaire ADD CONSTRAINT FK_338920E0FB88E14F '
            .'FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE combattant_combat DROP FOREIGN KEY FK_5CFA82B7A9E2D76C'
        );
        $this->addSql(
            'ALTER TABLE combattant_combat ADD CONSTRAINT FK_5CFA82B7A9E2D76C '
            .'FOREIGN KEY (joueur_id) REFERENCES user (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE plan_round_combat DROP FOREIGN KEY FK_9372DB3CA9E2D76C'
        );
        $this->addSql(
            'ALTER TABLE plan_round_combat ADD CONSTRAINT FK_9372DB3CA9E2D76C '
            .'FOREIGN KEY (joueur_id) REFERENCES user (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE plan_round_combat DROP FOREIGN KEY FK_9372DB3CA9E2D76C'
        );
        $this->addSql(
            'ALTER TABLE plan_round_combat ADD CONSTRAINT FK_9372DB3CA9E2D76C '
            .'FOREIGN KEY (joueur_id) REFERENCES user (id)'
        );

        $this->addSql(
            'ALTER TABLE combattant_combat DROP FOREIGN KEY FK_5CFA82B7A9E2D76C'
        );
        $this->addSql(
            'ALTER TABLE combattant_combat ADD CONSTRAINT FK_5CFA82B7A9E2D76C '
            .'FOREIGN KEY (joueur_id) REFERENCES user (id)'
        );

        $this->addSql(
            'ALTER TABLE inventaire DROP FOREIGN KEY FK_338920E0FB88E14F'
        );
        $this->addSql(
            'ALTER TABLE inventaire ADD CONSTRAINT FK_338920E0FB88E14F '
            .'FOREIGN KEY (utilisateur_id) REFERENCES user (id)'
        );

        $this->addSql(
            'ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA15FB88E14F'
        );
        $this->addSql(
            'ALTER TABLE equipe ADD CONSTRAINT FK_2449BA15FB88E14F '
            .'FOREIGN KEY (utilisateur_id) REFERENCES user (id)'
        );

        $this->addSql(
            'ALTER TABLE combat DROP FOREIGN KEY FK_8D51E39892C1E237'
        );
        $this->addSql(
            'ALTER TABLE combat DROP FOREIGN KEY FK_8D51E39880744DD9'
        );
        $this->addSql(
            'ALTER TABLE combat DROP FOREIGN KEY FK_8D51E3982F942B8'
        );
        $this->addSql(
            'ALTER TABLE combat ADD CONSTRAINT FK_8D51E39892C1E237 '
            .'FOREIGN KEY (joueur1_id) REFERENCES user (id)'
        );
        $this->addSql(
            'ALTER TABLE combat ADD CONSTRAINT FK_8D51E39880744DD9 '
            .'FOREIGN KEY (joueur2_id) REFERENCES user (id)'
        );
        $this->addSql(
            'ALTER TABLE combat ADD CONSTRAINT FK_8D51E3982F942B8 '
            .'FOREIGN KEY (gagnant_id) REFERENCES user (id)'
        );

        $this->addSql(
            'DROP INDEX UNIQ_USER_VERIFICATION_EMAIL_HASH ON user'
        );
        $this->addSql(
            'ALTER TABLE user DROP email_verifie, '
            .'DROP jeton_verification_email_hash, '
            .'DROP date_expiration_verification_email'
        );
    }
}
