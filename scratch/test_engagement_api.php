<?php
$cookieFile = __DIR__ . '/test_cookie_eng.txt';
@unlink($cookieFile);

function req($url, $method = 'GET', $data = null, $isJson = false) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isJson) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

// 1. Login
$login = req('http://127.0.0.1:8000/entrar', 'POST', [
    'email' => 'gabriel@fasm.edu.br',
    'password' => 'vertice123'
]);
echo "1. Login: {$login['code']}\n";

// 2. List Forum Questions
$fList = req('http://127.0.0.1:8000/api/forum/questions?category_id=1');
echo "2. Forum List [{$fList['code']}]: " . substr($fList['body'], 0, 100) . "...\n";

// 3. Create Forum Question
$fCreate = req('http://127.0.0.1:8000/api/forum/question/create', 'POST', [
    'category_id' => 1,
    'title' => 'Como indexar chaves estrangeiras no InnoDB?',
    'content' => 'Gostaria de saber se o MySQL cria o índice de FK automaticamente ou se devo criar manualmente.',
    'tags' => 'mysql, indices, chaves'
], true);
echo "3. Forum Create [{$fCreate['code']}]: {$fCreate['body']}\n";
$newQId = json_decode($fCreate['body'], true)['question_id'] ?? 1;

// 4. Create Answer
$ansCreate = req('http://127.0.0.1:8000/api/forum/answer/create', 'POST', [
    'question_id' => $newQId,
    'content' => 'No MySQL InnoDB, ao definir FOREIGN KEY, o índice é criado automaticamente caso não exista um índice prévio!'
], true);
echo "4. Answer Create [{$ansCreate['code']}]: {$ansCreate['body']}\n";
$newAnsId = json_decode($ansCreate['body'], true)['answer_id'] ?? 1;

// 5. Mark Solution
$sol = req('http://127.0.0.1:8000/api/forum/answer/mark-solution', 'POST', [
    'question_id' => $newQId,
    'answer_id' => $newAnsId
], true);
echo "5. Mark Solution [{$sol['code']}]: {$sol['body']}\n";

// 6. Upvote Answer
$vote = req('http://127.0.0.1:8000/api/forum/answer/vote', 'POST', [
    'answer_id' => $newAnsId
], true);
echo "6. Vote Answer [{$vote['code']}]: {$vote['body']}\n";

// 7. Gamification Stats & Leaderboard
$stats = req('http://127.0.0.1:8000/api/gamification/my-stats');
echo "7. My Karma Stats [{$stats['code']}]: {$stats['body']}\n";

$leader = req('http://127.0.0.1:8000/api/gamification/leaderboard');
echo "8. Leaderboard [{$leader['code']}]: " . substr($leader['body'], 0, 120) . "...\n";

// 8. Quizzes List
$qList = req('http://127.0.0.1:8000/api/quiz/list?category_id=1');
echo "9. Quizzes List [{$qList['code']}]: " . substr($qList['body'], 0, 100) . "...\n";
$quizId = json_decode($qList['body'], true)['quizzes'][0]['id'] ?? 1;

// 9. Get Quiz Details
$qGet = req("http://127.0.0.1:8000/api/quiz/get?id={$quizId}");
echo "10. Quiz Get [{$qGet['code']}]: " . substr($qGet['body'], 0, 100) . "...\n";

// 10. Submit Quiz Attempt
$qSubmit = req('http://127.0.0.1:8000/api/quiz/submit', 'POST', [
    'quiz_id' => $quizId,
    'answers' => [1 => 1, 2 => 1, 3 => 2, 4 => 1],
    'time_taken' => 45
], true);
echo "11. Quiz Submit [{$qSubmit['code']}]: {$qSubmit['body']}\n";

@unlink($cookieFile);
