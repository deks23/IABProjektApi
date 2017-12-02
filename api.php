<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;
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
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }

    return (json_encode($dawcy));
});

$app->get('/user&id={id}', function(Request $request, Response $response){
  $id = $request->getAttribute('id');
  $sql="
  SELECT Imie, Nazwisko
  FROM Dawcy
  WHERE id= " . $id ."";
  try {
      $db = new Database();
      $db = $db->getConnection();
      $stmt = $db->query($sql);
      $dawca = $stmt->fetchAll(PDO::FETCH_OBJ);

  } catch (PDOException $e) {
      echo '{"error": {"text": '.$e->getMessage().'}}';
  }

  return (json_encode($dawca));
});


$app->post('/login', function(Request $request, Response $response){
    $email = $request->getParam('email');
    $password = $request->getParam('password');

    $sql = "SELECT q.Email, q.Haslo, q.Dawcy_Id, p.Imie, p.Nazwisko, p.Id
    FROM DaneLogowaniaDawców as q
    JOIN Dawcy as p
    ON q.Dawcy_Id = p.Id
    WHERE q.Email LIKE :email";

    try{
      $db = new Database();
      $db = $db->getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      $dawca = $stmt->fetchAll(PDO::FETCH_OBJ);
    }catch(PDOException $e){
      echo '{"error": {"text": '.$e->getMessage().'}}';
    }
    $new=new\stdClass();
    //DODAĆ, JEŻELI HASŁO POPRAWNE TO ZWRACA TOKEN
    if( $dawca[0]->Haslo==$password) {
     $new->userId=$dawca[0]->Id;
     $new->Imie=$dawca[0]->Imie;
     $new->Nazwisko=$dawca[0]->Nazwisko;
     $new->token=uniqid();
   }
    else {$new = "nope";}

      return json_encode($new);


});

$app->post('/loginJWT', function(Request $request, Response $response){
    $email = $request->getParam('email');
    $password = $request->getParam('password');
    $key = "qwerty";

    $sql = "SELECT q.Email, q.Haslo, q.Dawcy_Id, p.Imie, p.Nazwisko, p.Id
    FROM DaneLogowaniaDawców as q
    JOIN Dawcy as p
    ON q.Dawcy_Id = p.Id
    WHERE q.Email LIKE :email";

    try{
      $db = new Database();
      $db = $db->getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      $dawca = $stmt->fetchAll(PDO::FETCH_OBJ);
    }catch(PDOException $e){
      echo '{"error": {"text": '.$e->getMessage().'}}';
    }

    //DODAĆ, JEŻELI HASŁO POPRAWNE TO ZWRACA TOKEN
    if( $dawca[0]->Haslo==$password) {
     $issuedAt   = time();
     $expire = $issuedAt + 3600;

     $tokenData = array(
         "iat" => $issuedAt,
         "exp" => $expire,
         "data"=> [
           "userId" => $dawca[0]->Id
        ]
     );
         $jwt=JWT::encode($tokenData, $key);
         //$decoded =JWT::decode($jwt, $key, array('HS256'));
   }
    else {$jwt = "nope";}
      return json_encode($jwt);
});

$app->run();
