class Question {
    constructor(id, text, choices, answer) {
        this.text = text;
        this.choices = choices;
        this.answer = answer;
        this.id =id;
    }
    isCorrectAnswer(choice) {
        return this.answer === choice;
    }
}

class Quiz {
    constructor() {
        this.score = 0;
        this.currentQuestionIndex = 0;
        this.currentQuestion = null;
        this.totalLength = 0;
        this.quizz = null;
    }

    init(quizzId, afterInit) {

        //Appel AJAX vers le serveur
        fetch(`/jeux/quizz/${quizzId}/details`)
        .then((response) => {
            return response.json();
        })

        //Récupération des données
        .then((data) => {
            this.quizz = data;
            this.totalLength = data.count;
            this.currentQuestion = new Question(data.question.id, data.question.text, data.question.choices, data.question.answer);
            
            //Fonction callback qui permet d'etre sûr que les requêtes AJAX se sont bien executées
            afterInit();
        });
    }

    getCurrentQuestion() {
        return this.currentQuestion;
    }

    guess(answer, afterGuess) {
        if (this.getCurrentQuestion().isCorrectAnswer(answer)) {
            this.score++;
        }
        this.currentQuestionIndex++;
        //Appel AJAX vers le serveur
        fetch(`/jeux/quizz/${this.quizz.id}/questions/${this.currentQuestion.id}/next`)
        .then((response) => {
            return response.json();
        })

        //Récupération des données
        .then ((data) => {
            this.currentQuestion = new Question(data.id, data.text, data.choices, data.answer);
            afterGuess();
        });
    }

    hasEnded() {
        return this.currentQuestionIndex >= this.totalLength;
    }
}
