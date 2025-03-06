<?php 
require_once('gestionAuth.php');

/// Identification du type de méthode HTTP envoyée par le client
$http_method = $_SERVER['REQUEST_METHOD'];
switch ($http_method){
    case "POST" :
        // Récupération des données dans le corps
        $postedData = file_get_contents('php://input');
        $data = json_decode($postedData,true); 
        /*Reçoit du json et renvoi une adaptation exploitable en php. Le paramètre true impose un tableau en retour
        et non un objet.*/
        if(!isset($data['login']) || !isset($data['password'])){
            deliver_response(400, "Erreur de données, veuiller entrer un 'login' et un 'password' au format JSON", null);
            return;
        }

        $login = $data['login'];
        $password = $data['password'];

        $authManager = new GestionAuth();
        $jwt = $authManager->authenticateUser($login, $password);
        
        if($jwt == false){
            deliver_response(401, "Authentification échouée", null);
            return;
        }

        deliver_response(200, "Authentification réussie", $jwt);
    break;
    case "GET":
        // Le token doit être transmis en paramètre GET : authapi.php?token=xxx
        if(!isset($_GET['token']) || empty($_GET['token'])){
            deliver_response(400, "Token manquant", null);
            return;
        }
        
        $token = $_GET['token'];
        
        $authManager = new GestionAuth();

        if(!$authManager->validateToken($token)){
            deliver_response(401, "Token invalide", null);
            return;
        }
        
        deliver_response(200, "Token valide");
    break;
    default:
        deliver_response(405, "Méthode non autorisée", null);
}

/// Envoi de la réponse au Client
function deliver_response($status_code, $status_message, $data=null){
    /// Paramétrage de l'entête HTTP
    http_response_code($status_code); //Utilise un message standardisé en fonction du code HTTP

    header("Access-Control-Allow-Origin: *");
    //header("HTTP/1.1 $status_code $status_message"); //Permet de personnaliser le message associé au code HTTP
    header("Content-Type:application/json; charset=utf-8");//Indique au client le format de la réponse
    $response['status_code'] = $status_code;
    $response['status_message'] = $status_message;
    $response['data'] = $data;

    /// Mapping de la réponse au format JSON
    $json_response = json_encode($response);

    if($json_response===false)
        die('json encode ERROR : '.json_last_error_msg());

    /// Affichage de la réponse (Retourné au client)
    echo $json_response;
}

?>