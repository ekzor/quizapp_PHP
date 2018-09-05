<?php

class DatabaseOperations {

	private $dbh;

	function __construct(PDO $dbh) {
		$this->dbh = $dbh;
	}
	
	public function resetDB() {

		//CREATE TABLES
		try {

			//drop existing tables (better to drop than truncate; no telling what modifications have been made to the tables in the interim)
			//answers has to go before questions due to FK constraint
			$tables = ['quizapp_answers','quizapp_questions','quizapp_players'];
			foreach($tables as $t) {
				$this->dbh->exec('DROP TABLE `'.$t.'`');
			}

			//create questions table
			$sql = 'CREATE TABLE quizapp_questions (
				id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
				question_text VARCHAR(255) NOT NULL,
				PRIMARY KEY (id)
			);';
			$this->dbh->exec($sql);

			//create answers table.
			$sql = 'CREATE TABLE quizapp_answers (
				id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
				question_id SMALLINT UNSIGNED NOT NULL,
				answer_text VARCHAR(255) NOT NULL,
				correct TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (id),
				FOREIGN KEY (question_id) REFERENCES quizapp_questions(id) ON DELETE CASCADE
			);';
			$this->dbh->exec($sql);

			//create players table.
			$sql = 'CREATE TABLE quizapp_players (
				id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(30) NOT NULL,
				score MEDIUMINT NOT NULL DEFAULT 0,
				PRIMARY KEY (id)
			);';
			$this->dbh->exec($sql);

		} catch(PDOException $e) {
			echo $e->getMessage();
		}

		//POPULATE TABLES
		$this->readCSVIntoDB('seeddata_questions.csv','quizapp_questions');
		$this->readCSVIntoDB('seeddata_answers.csv','quizapp_answers');

	}

	private function readCSVIntoDB($filename,$table) {
		try {
			//This function assumes CSV columns are in the same order as the columns in the DB.
			//Also assumes # columns in file = # columns in DB
			if (($handle = fopen($filename,'r')) !== FALSE) {

				//pull the headers first to get a column count and prepare our DB statement
				$headers = fgetcsv($handle);
				$valstr = '(?'.str_repeat(',?',count($headers)-1).')';
				$sql = 'INSERT INTO '.$table.' VALUES '.$valstr;
				$stmt = $this->dbh->prepare($sql);
				
				//loop through the rest of the data
				while (($rowdata = fgetcsv($handle)) !== FALSE) {
					$stmt->execute($rowdata);
				}
			}
		} catch(PDOException $e) {
			echo $e->getMessage();
		} catch(Exception $e) {
			throw new Exception('Error reading file during CSV import');
		}
	}

	//return question text given a question ID
	public function getQuestion(int $questionID) : string {
		$stmt = $this->dbh->prepare('SELECT question_text FROM quizapp_questions WHERE id=?');
		$stmt->execute([$questionID]);
		return $stmt->fetchColumn();
	}

	//return the total number of questions in the database
	public function getQuestionCount() : int {
		return $this->dbh->query('SELECT count(*) FROM quizapp_questions')
			->fetchColumn();
	}

	//return all question IDs in the database.
	public function getQuestionIDs() : array {
		return $this->dbh->query('SELECT id FROM quizapp_questions')
			->fetchAll(PDO::FETCH_COLUMN,0);
	}

	//return all the possible answers for a given question
	public function getAnswers(int $questionID) : array {
		$stmt = $this->dbh->prepare('SELECT answer_text,correct FROM quizapp_answers WHERE question_id=?');
		$stmt->execute([$questionID]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	//return a given player's score -- TODO: check if this is redundant
	public function getScore(int $playerID) : int {
		$stmt = $this->dbh->prepare('SELECT score FROM quizapp_players WHERE id=?');
		$stmt->execute([$playerID]);
		return $stmt->fetchColumn();
	}

	//set a given player's score
	public function setScore(int $playerID, int $score) {
		$stmt = $this->dbh->prepare('UPDATE quizapp_players SET score=? WHERE id=?');
		$stmt->execute([$score,$playerID]);
	}

	//get the top scores (defaults to top 5)
	public function getHighScores(int $count=5) : array {
		$stmt = $this->dbh->prepare('SELECT id,name,score FROM quizapp_players ORDER BY score DESC, id DESC LIMIT ?');
		$stmt->execute([$count]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	//create a new player in the database
	public function createPlayer(string $name) : int {
		$stmt = $this->dbh->prepare("INSERT INTO quizapp_players (name) VALUES (?)");
		$stmt->execute([$name]);
		return $this->dbh->lastInsertId();
	}

	//get an existing player's name and score
	public function getPlayer(int $playerID) : array {
		$stmt = $this->dbh->prepare('SELECT name,score FROM quizapp_players WHERE id=?');
		$stmt->execute([$playerID]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}