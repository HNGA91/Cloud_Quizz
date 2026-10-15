/*
 * Cloud Quizz — déroulement d'un quizz.
 *
 * Le navigateur ne connaît jamais la bonne réponse à l'avance :
 *   1. GET  /jeux/quizz/{id}/details                  → 1re question (sans réponse)
 *   2. POST /jeux/quizz/{id}/questions/{q}/reponse    → le serveur dit si c'est juste
 *   3. GET  /jeux/quizz/{id}/questions/{q}/next       → question suivante, ou { fin: true }
 *
 * Tout le texte venant du serveur est inséré avec textContent (jamais innerHTML) :
 * une question contenant du HTML s'affiche telle quelle, sans être exécutée.
 */
(() => {
    const racine = document.getElementById("quiz");
    const quizzId = racine.dataset.quizzId;

    const el = {
        question: document.getElementById("question"),
        image: document.getElementById("question-image"),
        choix: document.getElementById("choices"),
        feedback: document.getElementById("feedback"),
        suivant: document.getElementById("suivant"),
        progress: document.getElementById("progress"),
        score: document.getElementById("score"),
        fin: document.getElementById("fin"),
        scoreFinal: document.getElementById("score-final"),
    };

    const etat = { total: 0, numero: 0, score: 0, question: null };

    const appel = async (url, options = {}) => {
        const reponse = await fetch(url, {
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            ...options,
        });
        if (!reponse.ok) {
            throw new Error(`Erreur ${reponse.status}`);
        }
        return reponse.json();
    };

    const afficherErreur = () => {
        el.feedback.textContent = "Le quizz n'a pas pu être chargé. Réessayez dans un instant.";
        el.feedback.className = "feedback-jeux text-center fw-bold text-danger";
    };

    const afficherQuestion = (question) => {
        etat.question = question;
        etat.numero++;

        el.question.textContent = question.text;
        el.feedback.textContent = "";
        el.feedback.className = "feedback-jeux text-center fw-bold";
        el.suivant.hidden = true;

        if (question.image) {
            el.image.src = question.image;
            el.image.hidden = false;
        } else {
            el.image.removeAttribute("src");
            el.image.hidden = true;
        }

        el.choix.replaceChildren();
        el.choix.classList.toggle("choices-images", question.choices.some((c) => c.image));

        question.choices.forEach((choix, index) => {
            const bouton = document.createElement("button");
            bouton.type = "button";
            bouton.className = "btn button-jeux";
            bouton.dataset.choixId = choix.id;

            if (choix.image) {
                const img = document.createElement("img");
                img.src = choix.image; // adresse neutre, sans le nom du pays
                // Le nom du pays donnerait la réponse : il n'apparaît qu'après la réponse
                img.alt = `Choix ${index + 1}`;
                img.className = "choix-image-jeux";
                bouton.append(img);
            }

            const libelle = document.createElement("span");
            libelle.className = "p-jeux d-block";
            libelle.textContent = choix.libelle ?? "";
            bouton.append(libelle);

            bouton.addEventListener("click", () => repondre(choix.id));
            el.choix.append(bouton);
        });

        el.progress.textContent = `Question ${etat.numero} sur ${etat.total}`;
        el.score.textContent = `Score : ${etat.score}`;
    };

    const repondre = async (choixId) => {
        const boutons = [...el.choix.querySelectorAll("button")];
        boutons.forEach((b) => (b.disabled = true)); // un seul clic par question

        try {
            const resultat = await appel(
                `/jeux/quizz/${quizzId}/questions/${etat.question.id}/reponse`,
                { method: "POST", body: JSON.stringify({ choix: choixId }) }
            );

            if (resultat.correct) {
                etat.score++;
            }

            boutons.forEach((bouton) => {
                const id = Number(bouton.dataset.choixId);
                // Révèle les libellés (utile pour les drapeaux : on apprend en jouant)
                bouton.querySelector(".p-jeux").textContent = resultat.libelles[id] ?? "";
                if (id === resultat.bonChoix) {
                    bouton.classList.add("choix-correct");
                } else if (id === choixId) {
                    bouton.classList.add("choix-faux");
                } else {
                    bouton.classList.add("choix-neutre");
                }
            });

            el.feedback.textContent = resultat.correct ? "Bonne réponse !" : "Mauvaise réponse…";
            el.feedback.classList.add(resultat.correct ? "text-success" : "text-danger");
            el.score.textContent = `Score : ${etat.score}`;
            el.suivant.textContent = etat.numero < etat.total ? "Question suivante" : "Voir mon score";
            el.suivant.hidden = false;
            el.suivant.focus();
        } catch (e) {
            boutons.forEach((b) => (b.disabled = false));
            afficherErreur();
        }
    };

    const suivante = async () => {
        el.suivant.hidden = true;
        try {
            const donnees = await appel(`/jeux/quizz/${quizzId}/questions/${etat.question.id}/next`);
            if (donnees.fin) {
                terminer();
            } else {
                afficherQuestion(donnees);
            }
        } catch (e) {
            el.suivant.hidden = false;
            afficherErreur();
        }
    };

    const terminer = () => {
        el.question.closest(".row").hidden = true;
        el.scoreFinal.textContent = `${etat.score} / ${etat.total}`;
        el.fin.hidden = false;
    };

    el.suivant.addEventListener("click", suivante);

    appel(`/jeux/quizz/${quizzId}/details`)
        .then((donnees) => {
            etat.total = donnees.count;
            afficherQuestion(donnees.question);
        })
        .catch(afficherErreur);
})();
