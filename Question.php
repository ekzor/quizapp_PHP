<?php
class Question {

	private $dbo;
	private $questionID;
	private $answers;

	public function __construct(DatabaseOperations $dbo, int $questionID) {
		$this->dbo = $dbo;
		$this->questionID = $questionID;
		$this->answers = $dbo->getAnswers($questionID);
	}

	//return the question text
	public function getQuestion() : string {
		return $this->dbo->getQuestion($this->questionID);
	}

	//return possible answers for this question
	public function getAnswers() : array {
		return array_column($this->answers, 'answer_text');
	}

	//return the total number of correct answers for this question
	public function getCorrectAnswerCount() : int {
		return array_sum(array_column($this->answers, 'correct'));
	}

	//validate user's input. throws an Exception if anything is wrong
	public function validateAnswer(array $userAnswer) : array {

		//check if the user provided an answer
		if (count($userAnswer) == 0) {
			throw new Exception("No answer provided");
		}

		//check if the correct number of answers were provided for the given question
		$ansCount = $this->getCorrectAnswerCount();
		if (count($userAnswer) != $ansCount) {
			throw new Exception('Incorrect number of answers provided for question. Expected: '.$ansCount.', received: '.count($userAnswer));
		}

		//check that user responses are numeric
		foreach($userAnswer as $u) {
			if (!is_numeric($u)) {
				throw new Exception('Answer must be numeric');
			}
		}

		//check that the user's response is bounded by the number of possible answers
		if (min($userAnswer)<0 || max($userAnswer)>max(array_keys($this->answers))) {
			throw new Exception('Answer is out of bounds');
		}

		//make sure the user isn't pulling a fast one and sending the same answer multiple times.
		if (max(array_count_values($userAnswer)) > 1) {
			throw new Exception('Answers must be unique');
		}

		return $userAnswer;
	}

	//check if the correct answers were provided by the user. function expects validated input.
	public function checkAnswer(array $userAnswer) : bool {
		foreach($userAnswer as $a) {
			if ($this->answers[$a]['correct'] == 0) {
				return false;
			}
		}
		return true;
	}
}