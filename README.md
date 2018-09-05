# quizapp_PHP
A simple PHP-based quiz app

# usage
index.php expects to receive the following parameters:
- `question`: Question number. If sent alone, returns the question text
- `answer[]`: Answer number. Must be sent alongside a question. Doesn't have to be sent as an array.
- `reset_db`: Resets (or initializes) the database.
