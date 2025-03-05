<?php
require_once('connexionDB.php');
require_once('../jwt-utils.php');

class GestionAuth {
    private $conn;

    public function __construct() {
        $db = new ConnexionDB();
        $this->conn = $db->getConnection();
    }

    /**
     * Authenticate user and generate JWT token if successful
     * 
     * @param string $login User's email/login
     * @param string $password User's password
     * @return string|false JWT token if authentication succeeds, false otherwise
     */
    public function authenticateUser($login, $password) {
        // Récupérer l'utilisateur par son login
        $query = "SELECT * FROM user WHERE login = :login";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':login', $login);
        $stmt->execute();

        // Vérification de l'existence de l'utilisateur
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier le mot de passe haché avec password_verify
            if (password_verify($password, $user['password'])) {
                // Génération du token
                $headers = [
                    'alg' => 'HS256',
                    'typ' => 'JWT'
                ];

                $payload = [
                    'login' => $login,
                    'role' => $user['role'],
                    'exp'   => time() + 60
                ];

                $jwt = generate_jwt($headers, $payload);
                return $jwt;
            }
        }
        
        return false;
    }

    public function validateToken($token) {
        // Vérifier que le token est bien structuré
        $parts = explode('.', $token);
        if(count($parts) !== 3) {
            return [
                'valid' => false,
                'message' => 'Format de token invalide'
            ];
        }
        
        // Vérifier la validité du token
        if(!is_jwt_valid($token)) {
            return false;
        }
        
        // Extract payload data if needed
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        
        return true;
    }
}
?>