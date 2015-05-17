<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/database.config.php';

$pdo = new PDO($dsn, $usr, $pwd);


$app = new \Slim\Slim();
$app->response->header('Content-type', 'application/json');

$app->get('/hello/:name', function ($name) {
    echo "Hello, $name";
});

$app->get('/weibo/list', function () {
    global $pdo;
    $sql = <<<SQL
    SELECT * FROM `weibo_info`
SQL;
    $result = $pdo->prepare($sql);
    $result->execute();

    echo json_encode($result->fetchAll(PDO::FETCH_ASSOC));
});

$app->get('/weibo/:mid', function ($mid) {
    global $pdo;
    $sql = <<<SQL
    SELECT * FROM `weibo_info`
    WHERE mid = :mid
SQL;
    $result = $pdo->prepare($sql);
    $result->bindValue(':mid', $mid);
    $result->execute();

    echo json_encode($result->fetch(PDO::FETCH_ASSOC));
});

$app->get('/weibo/:mid/count', function ($mid) {
    echo $mid;
    global $pdo;

    $q = $pdo->prepare("SELECT COUNT(*) FROM forward_info WHERE origin_mid = :mid");
    $q->bindParam(':mid', $mid);
    $q->execute();

    var_dump($q->fetch());
});

$app->get('/weibo/:mid/stat/:num', function ($mid, $num) {
    global $pdo;

    $q = $pdo->prepare('SELECT * FROM forward_stat WHERE origin_mid = :mid ORDER BY `count` DESC LIMIT :num');
    $q->bindValue(':mid', $mid);
    $q->bindValue(':num', (int)$num, PDO::PARAM_INT);
    $q->execute();

    $data = $q->fetchAll(PDO::FETCH_ASSOC);
    $result = [
        'key' => [],
        'value' => [],
    ];

    foreach ($data as &$item) {
        $result['key'][] = $item['word'];
        $result['value'][] = $item['count'];
    }
    echo json_encode($result);
});

$app->get('/weibo/:mid/top/:num', function ($mid, $num) {
    global $pdo;
    $sql = <<<SQL
SELECT *, (forward_count + like_count  * 0.5 + LENGTH(wb_content_main) / 10) as forward_mark FROM forward_info
JOIN `user_info` ON `user_info`.`user_id` = `forward_info`.`user_id`
WHERE origin_mid = :mid
ORDER BY forward_mark DESC
LIMIT :num
SQL;
    $q = $pdo->prepare($sql);
    $q->bindParam(':mid', $mid);
    $q->bindValue(':num', (int)$num, PDO::PARAM_INT);
    $q->execute();

    $data = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as &$item) {
        $item['date_str'] = date('Y-m-d H:i:s', $item['date']);
    }
    echo json_encode($data);
});

$app->get('/user/:uid', function ($uid) {
    global $pdo;
    $sql = <<<SQL
SELECT * FROM `user_info`
WHERE user_id = :uid
SQL;
    $q = $pdo->prepare($sql);
    $q->bindValue(':uid', $uid);
    $q->execute();

    echo json_encode($q->fetch(PDO::FETCH_ASSOC));
});

$app->run();
