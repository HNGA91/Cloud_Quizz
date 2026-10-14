<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot B — modèle de données :
 *
 * 1. Commandes : table ligne_commande (produit, quantité, titre et prix copiés à l'achat)
 *    à la place de la table de jointure commande_produit_boutique, que le code
 *    n'alimentait plus. Les éventuelles données existantes sont reprises.
 * 2. Référence de commande unique (les doublons existants reçoivent un suffixe « -id »).
 * 3. Quizz : table choix (2, 3 ou 4 par question, image facultative, bonne réponse
 *    en booléen) à la place des colonnes choix1..choix4 + reponse. Les questions
 *    existantes sont converties ; une image facultative est ajoutée sur la question.
 */
final class Version20261014120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lignes de commande, référence unique, table choix (2 à 4 réponses avec image)';
    }

    /**
     * Avec MySQL/TiDB, chaque ordre DDL (CREATE, ALTER…) valide implicitement la
     * transaction en cours : l'envelopper dans une transaction ne protège rien.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Signale les questions dont la « reponse » ne correspond à aucun choix :
        // après conversion, elles n'auront aucune bonne réponse (à corriger dans l'admin).
        $sansBonneReponse = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM question_quizz
             WHERE TRIM(reponse) NOT IN (TRIM(choix1), TRIM(choix2), TRIM(choix3), TRIM(choix4))"
        );
        $this->warnIf(
            $sansBonneReponse > 0,
            sprintf('%d question(s) sans bonne réponse reconnue : à corriger dans /admin/question/quizz.', $sansBonneReponse)
        );

        // ---- 1. Lignes de commande -------------------------------------------------
        $this->addSql('CREATE TABLE ligne_commande (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, produit_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, prix_unitaire DOUBLE PRECISION NOT NULL, quantite SMALLINT UNSIGNED NOT NULL, INDEX IDX_3170B74B82EA2E54 (commande_id), INDEX IDX_3170B74BF347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74BF347EFB FOREIGN KEY (produit_id) REFERENCES produit_boutique (id) ON DELETE SET NULL');

        // Reprise de l'ancienne jointure (sa clé primaire empêchait les doublons : quantité 1)
        $this->addSql('INSERT INTO ligne_commande (commande_id, produit_id, titre, prix_unitaire, quantite)
            SELECT cpb.commande_id, p.id, p.titre, p.prix, 1
            FROM commande_produit_boutique cpb
            JOIN produit_boutique p ON p.id = cpb.produit_boutique_id');
        $this->addSql('DROP TABLE commande_produit_boutique');

        // ---- 2. Référence unique ---------------------------------------------------
        $this->addSql('UPDATE commande c
            JOIN (SELECT reference FROM commande GROUP BY reference HAVING COUNT(*) > 1) d ON d.reference = c.reference
            SET c.reference = CONCAT(c.reference, \'-\', c.id)');
        $this->addSql('ALTER TABLE commande MODIFY reference VARCHAR(32) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6EEAA67DAEA34913 ON commande (reference)');

        // ---- 3. Choix des questions ------------------------------------------------
        $this->addSql('CREATE TABLE choix (id INT AUTO_INCREMENT NOT NULL, question_id INT NOT NULL, libelle VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, est_correct TINYINT(1) NOT NULL, position SMALLINT UNSIGNED NOT NULL, INDEX IDX_4F4880911E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE choix ADD CONSTRAINT FK_4F4880911E27F6BF FOREIGN KEY (question_id) REFERENCES question_quizz (id) ON DELETE CASCADE');

        // Une ligne par choix non vide ; bonne réponse = libellé identique à « reponse »
        // (comparaison insensible à la casse et aux accents, grâce à utf8mb4_unicode_ci)
        $this->addSql('INSERT INTO choix (question_id, libelle, image, est_correct, position)
            SELECT id, TRIM(choix1), NULL, TRIM(choix1) = TRIM(reponse), 1 FROM question_quizz WHERE TRIM(choix1) <> \'\'
            UNION ALL
            SELECT id, TRIM(choix2), NULL, TRIM(choix2) = TRIM(reponse), 2 FROM question_quizz WHERE TRIM(choix2) <> \'\'
            UNION ALL
            SELECT id, TRIM(choix3), NULL, TRIM(choix3) = TRIM(reponse), 3 FROM question_quizz WHERE TRIM(choix3) <> \'\'
            UNION ALL
            SELECT id, TRIM(choix4), NULL, TRIM(choix4) = TRIM(reponse), 4 FROM question_quizz WHERE TRIM(choix4) <> \'\'');

        // Si un choix du milieu était vide, les positions ont un trou (1, 2, 4) :
        // sans conséquence sur l'ordre, et renumérotées au prochain enregistrement dans l'admin.

        // Un ordre par colonne : plus portable (MySQL, MariaDB, TiDB)
        $this->addSql('ALTER TABLE question_quizz ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE question_quizz DROP choix1');
        $this->addSql('ALTER TABLE question_quizz DROP choix2');
        $this->addSql('ALTER TABLE question_quizz DROP choix3');
        $this->addSql('ALTER TABLE question_quizz DROP choix4');
        $this->addSql('ALTER TABLE question_quizz DROP reponse');
    }

    public function down(Schema $schema): void
    {
        // ---- 3. Choix → colonnes (les choix au-delà du 4e et les images sont perdus)
        $this->addSql('ALTER TABLE question_quizz ADD choix1 VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE question_quizz ADD choix2 VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE question_quizz ADD choix3 VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE question_quizz ADD choix4 VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE question_quizz ADD reponse VARCHAR(255) NOT NULL DEFAULT \'\'');
        for ($i = 1; $i <= 4; ++$i) {
            $this->addSql(sprintf('UPDATE question_quizz q JOIN choix c ON c.question_id = q.id AND c.position = %d SET q.choix%d = c.libelle', $i, $i));
        }
        $this->addSql('UPDATE question_quizz q JOIN choix c ON c.question_id = q.id AND c.est_correct = 1 SET q.reponse = c.libelle');
        $this->addSql('ALTER TABLE question_quizz DROP image');
        $this->addSql('DROP TABLE choix');

        // ---- 2. Référence
        $this->addSql('DROP INDEX UNIQ_6EEAA67DAEA34913 ON commande');
        $this->addSql('ALTER TABLE commande MODIFY reference VARCHAR(255) NOT NULL');

        // ---- 1. Lignes → jointure (les quantités sont perdues)
        $this->addSql('CREATE TABLE commande_produit_boutique (commande_id INT NOT NULL, produit_boutique_id INT NOT NULL, INDEX IDX_6355FB1882EA2E54 (commande_id), INDEX IDX_6355FB18D57F3F62 (produit_boutique_id), PRIMARY KEY(commande_id, produit_boutique_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande_produit_boutique ADD CONSTRAINT FK_6355FB1882EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_produit_boutique ADD CONSTRAINT FK_6355FB18D57F3F62 FOREIGN KEY (produit_boutique_id) REFERENCES produit_boutique (id) ON DELETE CASCADE');
        $this->addSql('INSERT IGNORE INTO commande_produit_boutique (commande_id, produit_boutique_id)
            SELECT commande_id, produit_id FROM ligne_commande WHERE produit_id IS NOT NULL');
        $this->addSql('DROP TABLE ligne_commande');
    }
}
