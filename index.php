<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require("dbcreds.php");
require("classes/DatabaseOperations.php");
require("classes/Question.php");

//DATABASE VARS

//initiate DB connection and initialize DBO object
try {
	$dbh = new PDO('mysql:host='.$db_host.';dbname='.$db_name,$db_user,$db_pass);
} catch (PDOException $e) {
	$response = ['error'=>'There was a problem connecting to the database'];
}

$dbo = new DatabaseOperations($dbh);





//PARAMETER HANDLING

if (isset($_REQUEST['reset_db'])) {
	$dbo->resetDB();
}


//obtain question number from parameters and initialize Question object
if (isset($_REQUEST['question']) && is_numeric($_REQUEST['question'])) {
	$questionNumber = $_REQUEST['question'];
	$question = new Question($dbo,$questionNumber);
}

//obtain user's answer from parameters
if (isset($_REQUEST['answer'])) {
	$userAnswer = $_REQUEST['answer'];
	
	//convert the user's answer to an array if it isn't one already (e.g. if radio buttons were used instead of checkboxes)
	if (!is_array($userAnswer)) {
		$userAnswer = [$userAnswer];
	}
}


//RESPONSES

//(this logic could have been rolled into the above conditionals, but I have chosen easier-to-follow code over minor shortcuts)

//question and user's answer are both defined.
if (isset($question) && isset($userAnswer)) {

	//start by validating the user's input.
	try {
		$question->validateAnswer($userAnswer);
	} catch (Exception $e) {
		$response = ['error' => $e->getMessage()];
	}

	//if it's all good, check if it's correct!
	if (!isset($response['error'])) {
		$response = [
			'question' => $questionNumber,
			'correct' => $question->checkAnswer($userAnswer),
			'points' => $question->getCorrectAnswerCount()
		];
	}

//there's a question but no answer yet.
} elseif (isset($question) && !isset($userAnswer)) {

 	//return the question text and possible answers
	$response = [
		'question' => $questionNumber,
		'questiontext' => $question->getQuestion(),
		'answers' => $question->getAnswers()
	];

//there's a user answer, but we don't know what question it's for
} elseif (!isset($question) && isset($userAnswer)) {

	//return an error
	$response = ['error' => 'Answers provided for unknown question. Did you pass in the question parameter?'];

//no question requested and no user answer provided.
} elseif (!isset($question) && !isset($userAnswer)) {

	//since this is the form handler, we expect to receive parameters and should return an error
	$response = ['error' => 'No parameters provided to form handler'];
}

//return our response
header('Content-Type: application/json');
echo json_encode($response);