<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509165332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, reference VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', prix DOUBLE PRECISION NOT NULL, INDEX IDX_6EEAA67DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande_produit_boutique (commande_id INT NOT NULL, produit_boutique_id INT NOT NULL, INDEX IDX_6355FB1882EA2E54 (commande_id), INDEX IDX_6355FB18D57F3F62 (produit_boutique_id), PRIMARY KEY(commande_id, produit_boutique_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commentaires (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, parent_id INT DEFAULT NULL, jeux_quizz_id INT NOT NULL, contenu LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', rgpd TINYINT(1) NOT NULL, INDEX IDX_D9BEC0C4A76ED395 (user_id), INDEX IDX_D9BEC0C4727ACA70 (parent_id), INDEX IDX_D9BEC0C49398C03C (jeux_quizz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE jeux_quizz (id INT AUTO_INCREMENT NOT NULL, theme_quizz_id INT NOT NULL, jeux VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, image_alt VARCHAR(255) NOT NULL, INDEX IDX_4F01E39E18AD59BA (theme_quizz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE produit_boutique (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, image_alt VARCHAR(255) NOT NULL, resume VARCHAR(255) NOT NULL, prix DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE question_quizz (id INT AUTO_INCREMENT NOT NULL, jeux_quizz_id INT NOT NULL, question VARCHAR(255) NOT NULL, choix1 VARCHAR(255) NOT NULL, choix2 VARCHAR(255) NOT NULL, choix3 VARCHAR(255) NOT NULL, choix4 VARCHAR(255) NOT NULL, reponse VARCHAR(255) NOT NULL, INDEX IDX_4C9C86399398C03C (jeux_quizz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE theme_quizz (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, image_alt VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, pseudonyme VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commande_produit_boutique ADD CONSTRAINT FK_6355FB1882EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_produit_boutique ADD CONSTRAINT FK_6355FB18D57F3F62 FOREIGN KEY (produit_boutique_id) REFERENCES produit_boutique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT FK_D9BEC0C4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT FK_D9BEC0C4727ACA70 FOREIGN KEY (parent_id) REFERENCES commentaires (id)');
        $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT FK_D9BEC0C49398C03C FOREIGN KEY (jeux_quizz_id) REFERENCES jeux_quizz (id)');
        $this->addSql('ALTER TABLE jeux_quizz ADD CONSTRAINT FK_4F01E39E18AD59BA FOREIGN KEY (theme_quizz_id) REFERENCES theme_quizz (id)');
        $this->addSql('ALTER TABLE question_quizz ADD CONSTRAINT FK_4C9C86399398C03C FOREIGN KEY (jeux_quizz_id) REFERENCES jeux_quizz (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande_produit_boutique DROP FOREIGN KEY FK_6355FB1882EA2E54');
        $this->addSql('ALTER TABLE commande_produit_boutique DROP FOREIGN KEY FK_6355FB18D57F3F62');
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY FK_D9BEC0C4A76ED395');
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY FK_D9BEC0C4727ACA70');
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY FK_D9BEC0C49398C03C');
        $this->addSql('ALTER TABLE jeux_quizz DROP FOREIGN KEY FK_4F01E39E18AD59BA');
        $this->addSql('ALTER TABLE question_quizz DROP FOREIGN KEY FK_4C9C86399398C03C');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_produit_boutique');
        $this->addSql('DROP TABLE commentaires');
        $this->addSql('DROP TABLE jeux_quizz');
        $this->addSql('DROP TABLE produit_boutique');
        $this->addSql('DROP TABLE question_quizz');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE theme_quizz');
        $this->addSql('DROP TABLE user');
    }
}
