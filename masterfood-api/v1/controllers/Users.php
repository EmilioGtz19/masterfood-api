<?php

require_once 'data/MysqlManager.php';

class Users
{

    public static function get($urlSegments)
    {
    }

    public static function post($urlSegments)
    {
        if (!isset($urlSegments[0])) {
            throw new ApiException(
                400,
                0,
                "El recurso está mal referenciado",
                "http://localhost",
                "El recurso $_SERVER[REQUEST_URI] no esta sujeto a resultados"
            );
        }

        switch ($urlSegments[0]) {
            case "register":
                return self::saveUser();
                break;
            case "login":
                return self::authUser();
                break;
            case "updateUser":
                return self::modifyUser();
                break;
            case "updatePass":
                return self::modifyPass();
                break;
            default:
                throw new ApiException(
                    404,
                    0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost",
                    "No se encontró el segmento \"users/$urlSegments[0]\"."
                );
        }
    }

    public static function saveUser()
    {
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            $internalServerError = new ApiException(
                500,
                0,
                "Error interno en el servidor. Contacte al administrador",
                "http://localhost",
                "Error de parsing JSON. Causa: " . json_last_error_msg()
            );
            throw $internalServerError;
        }

        if (
            !isset($decodedParameters["first_name"]) ||
            !isset($decodedParameters["last_name"]) ||
            !isset($decodedParameters["email"]) ||
            !isset($decodedParameters["pass"]) ||
            !isset($decodedParameters["user_photo"])
        ) {

            throw new ApiException(
                400,
                0,
                "Verifique los datos del usuario tengan formato correcto",
                "http://localhost",
                "Uno de los atributos del usuario no está definido en los parámetros"
            );
        }

        $checkEmail = self::checkEmail($decodedParameters["email"]);

        if($checkEmail){
            return ["message" => "Email ya se encuentra registrado"];
        }else{
            // Insertar usuario
            $dbResult = self::insertUser($decodedParameters);
        }

        // Procesar resultado de la inserción
        if ($dbResult) {
            return ["status" => 201, "message" => "Usuario registrado"];
        } else {
            throw new ApiException(
                500,
                0,
                "Error del servidor",
                "http://localhost",
                "Error en la base de datos al ejecutar la inserción del usuario."
            );
        }
    }

    private static function insertUser($decodedParameters)
    {
        //Extraer datos del usuario
        $first_name = $decodedParameters["first_name"];
        $last_name = $decodedParameters["last_name"];
        $email = $decodedParameters["email"];
        $pass = $decodedParameters["pass"];
        $user_photo = $decodedParameters["user_photo"];


        $datab = $user_photo;
        list($type, $datab) = explode(';', $datab);
        list(, $datab)      = explode(',', $datab);
        //DECODIFICA 
        $user_photo = base64_decode($datab);


        // Encriptar contraseña
        //$hashPassword = password_hash($pass, PASSWORD_DEFAULT);

        try {
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia INSERT
            $sentence = "INSERT INTO users (first_name, last_name, email, pass, user_photo)" .
                " VALUES (?,?,?,?,?)";

            // Preparar sentencia
            $preparedStament = $pdo->prepare($sentence);
            $preparedStament->bindParam(1, $first_name);
            $preparedStament->bindParam(2, $last_name);
            $preparedStament->bindParam(3, $email);
            $preparedStament->bindParam(4, $pass);
            $preparedStament->bindParam(5, $user_photo);

            // Ejecutar sentencia
            return $preparedStament->execute();
        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al intentar insertar el usuario: " . $e->getMessage()
            );
        }
    }

    private static function authUser()
    {
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            $internalServerError = new ApiException(
                500,
                0,
                "Error interno en el servidor. Contacte al administrador",
                "http://localhost",
                "Error de parsing JSON. Causa: " . json_last_error_msg()
            );
            throw $internalServerError;
        }

        if (
            !isset($decodedParameters["email"]) ||
            !isset($decodedParameters["pass"])
        ) {
            throw new ApiException(
                400,
                0,
                "Las credenciales del afiliado deben estar definidas correctamente",
                "http://localhost",
                "El atributo \"id\" o \"password\" o ambos, están vacíos o no definidos"
            );
        }

        $email = $decodedParameters["email"];
        $pass = $decodedParameters["pass"];

        $dbResult = self::findUserByCredentials($email, $pass);

        if ($dbResult != NULL) {
            return [
                "status" => 200,
                "user_id" => $dbResult["user_id"],
                "first_name" => $dbResult["first_name"],
                "last_name" => $dbResult["last_name"],
                "email" => $dbResult["email"],
                "last_update" => $dbResult["LAST_UPDATE"],
                "pass" => $dbResult["pass"],
                "user_photo" => $dbResult["user_photo"]
            ]; 
        } else {
            throw new ApiException(
                400,
                0,
                "Número de identificación o contraseña inválidos",
                "http://localhost",
                "Puede que no exista un usuario creado con el id \"$email\" o que la contraseña \"$pass\" sea incorrecta."
            );
        }
    }

    private static function findUserByCredentials($email, $password)
    {
        try {
            $pdo = MysqlManager::get()->getDb();

            $sentence = "SELECT * FROM users WHERE email = ?";

            $preparedSentence = $pdo->prepare($sentence);
            $preparedSentence->bindParam(1, $email);

            if ($preparedSentence->execute()) {
                $userData = $preparedSentence->fetch(PDO::FETCH_ASSOC);

                // Verificar contraseña
                if ($password == $userData["pass"]) {
                    $imageEncode = base64_encode($userData["user_photo"]);
                    $userData["user_photo"] = $imageEncode;
                    return $userData;
                } else {
                    return null;
                }
            } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos. Detalles:" . $pdo->errorInfo()[2]
                );
            }
        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al consultar el usuario: " . $e->getMessage()
            );
        }
    }

    private static function modifyUser(){
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            $internalServerError = new ApiException(
                500,
                0,
                "Error interno en el servidor. Contacte al administrador",
                "http://localhost",
                "Error de parsing JSON. Causa: " . json_last_error_msg()
            );
            throw $internalServerError;
        }

        if (
            !isset($decodedParameters["user_id"]) ||
            !isset($decodedParameters["first_name"]) ||
            !isset($decodedParameters["last_name"]) ||
            !isset($decodedParameters["email"]) ||
            !isset($decodedParameters["user_photo"])
        ) {

            throw new ApiException(
                400,
                0,
                "Verifique los datos del usuario tengan formato correcto",
                "http://localhost",
                "Uno de los atributos del usuario no está definido en los parámetros"
            );
        }

        $dbResult = self::updateUser($decodedParameters);

        if ($dbResult) {
            return ["status" => 201, "message" => "Usuario actualizado"];
        } else {
            throw new ApiException(
                500,
                0,
                "Error del servidor",
                "http://localhost",
                "Error en la base de datos al ejecutar la actualizacion del usuario."
            );
        }

    }

    private static function updateUser($decodedParameters){
        $user_id = $decodedParameters["user_id"];
        $first_name = $decodedParameters["first_name"];
        $last_name = $decodedParameters["last_name"];
        $email = $decodedParameters["email"];
        $user_photo = $decodedParameters["user_photo"];


        $datab = $user_photo;

        $user_photo = base64_decode($datab);

        try {
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia
            $sentence = "UPDATE users SET first_name = ?, last_name = ?, email = ?, user_photo = ?, last_update = CURRENT_TIMESTAMP WHERE user_id = ?";

            // Preparar sentencia
            $preparedStament = $pdo->prepare($sentence);
            $preparedStament->bindParam(1, $first_name);
            $preparedStament->bindParam(2, $last_name);
            $preparedStament->bindParam(3, $email);
            $preparedStament->bindParam(4, $user_photo);
            $preparedStament->bindParam(5, $user_id);

            // Ejecutar sentencia
            return $preparedStament->execute();
        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al intentar actualizar el usuario: " . $e->getMessage()
            );
        }
    }

    private static function modifyPass(){
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            $internalServerError = new ApiException(
                500,
                0,
                "Error interno en el servidor. Contacte al administrador",
                "http://localhost",
                "Error de parsing JSON. Causa: " . json_last_error_msg()
            );
            throw $internalServerError;
        }

        if (
            !isset($decodedParameters["user_id"]) ||
            !isset($decodedParameters["pass"])
        ) {

            throw new ApiException(
                400,
                0,
                "Verifique los datos del usuario tengan formato correcto",
                "http://localhost",
                "Uno de los atributos del usuario no está definido en los parámetros"
            );
        }

        $dbResult = self::updatePass($decodedParameters);

        if ($dbResult) {
            return ["status" => 201, "message" => "Contraseña actualizada"];
        } else {
            throw new ApiException(
                500,
                0,
                "Error del servidor",
                "http://localhost",
                "Error en la base de datos al ejecutar la actualizacion del usuario."
            );
        }
    }

    private static function updatePass($decodedParameters){
        $user_id = $decodedParameters["user_id"];
        $pass = $decodedParameters["pass"];

        try {
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia
            $sentence = "UPDATE users SET pass = ? WHERE user_id = ?";

            // Preparar sentencia
            $preparedStament = $pdo->prepare($sentence);
            $preparedStament->bindParam(1, $pass);
            $preparedStament->bindParam(2, $user_id);

            // Ejecutar sentencia
            return $preparedStament->execute();
        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al intentar actualizar el usuario: " . $e->getMessage()
            );
        }
    }

    private static function checkEmail($email)
    {
    
        try{
            $pdo = MysqlManager::get()->getDb();

            $sentence = "SELECT email FROM users WHERE email = ?";

            $preparedStament = $pdo->prepare($sentence);
            $preparedStament->bindParam(1, $email);
            $preparedStament->execute();

            if ($preparedStament->rowCount() > 0){
                $check = true;
            }else{
                $check = false;
            }

            return $check;

        }catch(PDOException $e){
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al intentar consultar el usuario: " . $e->getMessage()
            );
        }

    }
}
