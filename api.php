<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');
header('Content-type: application/json; charset=utf-8');
use \Firebase\JWT\JWT;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
require 'vendor/autoload.php';
require 'Database.php';

$app = new \Slim\App;

$app->get('/select', function (Request $request, Response $response) {
    $sql = "
        SELECT e.Imie, e.Nazwisko, b.NazwaGrupyKrwi
        FROM Dawcy as e
        INNER JOIN GrupaKrwi as b
        on b.Id= e.GrupaKrwi_Id";

    try {
        $db = new Database();
        $db = $db->getConnection();
        $stmt = $db->query($sql);
        $dawcy = $stmt->fetchAll(PDO::FETCH_OBJ);

    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }

    return (json_encode($dawcy));
});

$app->post('/user', function (Request $request, Response $response) {
    $key = "qwerty";
    try {
        $decoded = JWT::decode($request->getParam('token'), $key, array('HS256'));
    } catch (\Firebase\JWT\ExpiredException $e) {
        $experiedToken = array(
            "token" => "",
        );
        return json_encode($experiedToken);
    }
    $data = $decoded->data;
    $id = $data->userId;

    $sql = "
        SELECT q.Imie, q.Nazwisko, q.Id, w.Dawcy_Id, w.Id as IdDonacji, q.DataUrodzenia, w.Data, w.Uwagi
        FROM Dawcy AS q
        JOIN Donacje as w
        ON q.Id=w.Dawcy_Id
        WHERE q.Id= " . $id . "";

    try {
        $db = new Database();
        $db = $db->getConnection();
        $stmt = $db->query($sql);
        $dawca = $stmt->fetchAll(PDO::FETCH_OBJ);

    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }

    return (json_encode($dawca));
});

$app->post('/login', function (Request $request, Response $response) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');
    $key = "qwerty";

    $sql = "
        SELECT q.Email, q.Haslo, q.Dawcy_Id, p.Imie, p.Nazwisko, p.Id
        FROM DaneLogowaniaDawcow as q
        JOIN Dawcy as p
        ON q.Dawcy_Id = p.Id
        WHERE q.Email LIKE :email";

    try {
        $db = new Database();
        $db = $db->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $dawca = $stmt->fetchAll(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }

    if ($dawca[0]->Haslo == $password) {
        $issuedAt = time();
        $expire = $issuedAt + 3600;

        $tokenData = array(
            "iat" => $issuedAt,
            "exp" => $expire,
            "data" => [
                "userId" => $dawca[0]->Id,
            ],
        );
        $jwt = JWT::encode($tokenData, $key);

    } else { $jwt = "failed";}
    return json_encode($jwt);
});

$app->post('/employeeLogin', function (Request $request, Response $response) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');
    $key = "qwerty";

    $sql = "
        SELECT q.Email, q.Haslo, q.Pracownicy_Id, p.Imie, p.Nazwisko, p.Id
        FROM DaneLogowaniaPracownikow as q
        JOIN Pracownicy as p
        ON q.Pracownicy_Id = p.Id
        WHERE q.Email LIKE :email";

    try {
        $db = new Database();
        $db = $db->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $pracownik = $stmt->fetchAll(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        echo '{"error": {"text": ' . $e->getMessage() . '}}';
    }

    if ($pracownik[0]->Haslo == $password) {
        $issuedAt = time();
        $expire = $issuedAt + 3600;

        $tokenData = array(
            "iat" => $issuedAt,
            "exp" => $expire,
            "data" => [
                "employeeId" => $pracownik[0]->Id,
            ],
        );
        $jwt = JWT::encode($tokenData, $key);

    } else { $jwt = "failed";}
    return json_encode($jwt);
});

$app->post('/register', function (Request $request, Response $response) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');
    $imie = $request->getParam('imie');
    $nazwisko = $request->getParam('nazwisko');
    $dataUrodzenia = $request->getParam('dataUrodzenia');
    

    $sql = "
    BEGIN;
    INSERT INTO Dawcy (Imie, Nazwisko, DataUrodzenia) 
        VALUES (:imie, :nazwisko, :dataUrodzenia);
    INSERT INTO DaneLogowaniaDawcow (Email, Dawcy_Id, Haslo)
        VALUES (:email, LAST_INSERT_ID(), :password);
    COMMIT;";

    try {
        $db = new Database();
        $db = $db->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':imie', $imie);
        $stmt->bindParam(':nazwisko', $nazwisko);
        $stmt->bindParam(':dataUrodzenia', $dataUrodzenia);
        $stmt->execute();

        $dawca = $stmt->fetchAll(PDO::FETCH_OBJ);
        $response="userAdded";
    } catch (PDOException $e) {
        $response =  '{"error": {"text": ' . $e->getMessage() . '}}';
    }

   
    return json_encode($response);
});

$app->run();
