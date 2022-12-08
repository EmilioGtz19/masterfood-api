<?php

require_once 'data/MysqlManager.php';

class Recipes
{

    public static function get($urlSegments)
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
            case "list":
                return self::getRecipesList();
                break;
            case "recipesUser":
                return self::getRecipesListFromUser();
                break;
            case "recipeId":
                return self::getRecipesListFromRecipeId();
                break;
            default:
                throw new ApiException(
                    404,
                    0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost",
                    "No se encontró el segmento \"recipes/$urlSegments[0]\"."
                );
        }
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
            case "insert":
                return self::saveRecipe();
                break;
            case "upload":
                return self::insertImage();
            default:
                throw new ApiException(
                    404,
                    0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost",
                    "No se encontró el segmento \"recipes/$urlSegments[0]\"."
                );
        }
    }

    public static function saveRecipe()
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
            !isset($decodedParameters["user_id"]) ||
            !isset($decodedParameters["title"]) ||
            !isset($decodedParameters["food_type_id"]) ||
            !isset($decodedParameters["amount_people"]) ||
            !isset($decodedParameters["ingredients"]) ||
            !isset($decodedParameters["utensils"]) ||
            !isset($decodedParameters["steps"]) ||
            !isset($decodedParameters["difficulty_id"]) ||
            !isset($decodedParameters["nationality"])
        ) {

            throw new ApiException(
                400,
                0,
                "Verifique los datos del usuario tengan formato correcto",
                "http://localhost",
                "Uno de los atributos del usuario no está definido en los parámetros"
            );
        }

        // Insertar rectea
        $dbResult = self::insertRecipe($decodedParameters);

        // Procesar resultado de la inserción
        if ($dbResult) {
            return ["status" => 201, "message" => "Receta registrada"];
        } else {
            throw new ApiException(
                500,
                0,
                "Error del servidor",
                "http://localhost",
                "Error en la base de datos al ejecutar la inserción de la receta."
            );
        }

    }

    public static function insertRecipe($decodedParameters)
    {
          //Extraer datos de receta
          $user_id = $decodedParameters["user_id"];
          $title = $decodedParameters["title"];
          $food_type_id = $decodedParameters["food_type_id"];
          $amount_people = $decodedParameters["amount_people"];
          $ingredients = json_encode($decodedParameters["ingredients"]);
          $steps = json_encode($decodedParameters["steps"]);
          $utensils = json_encode($decodedParameters["utensils"]);
          $difficulty = $decodedParameters["difficulty_id"];
          $nationality = $decodedParameters["nationality"];

          try {
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia INSERT
            $sentence = "INSERT INTO recipes (user_id,title,food_type_id,amount_people,ingredients,utensils,difficulty_id,nationality,steps)" .
                " VALUES (?,?,?,?,?,?,?,?,?)";

            // Preparar sentencia
            $preparedStament = $pdo->prepare($sentence);
            $preparedStament->bindParam(1, $user_id);
            $preparedStament->bindParam(2, $title);
            $preparedStament->bindParam(3, $food_type_id);
            $preparedStament->bindParam(4, $amount_people);

            $preparedStament->bindParam(5, $ingredients);
            $preparedStament->bindParam(6, $utensils);

            $preparedStament->bindParam(7, $difficulty);
            $preparedStament->bindParam(8, $nationality);

            $preparedStament->bindParam(9, $steps);

            // Ejecutar sentencia
            return $preparedStament->execute();
        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al intentar insertar la receta: " . $e->getMessage()
            );
        }
          
    }

    public static function getRecipesList(){
        try{
            $pdo = MysqlManager::get()->getDb();

            $sentence = "SELECT recipes.user_id, recipe_id, first_name, title, difficulty, nationality  FROM recipes
            INNER JOIN users ON users.user_id = recipes.user_id
            INNER JOIN difficulty ON recipes.difficulty_id = difficulty.difficulty_id";
            $preparedStament = $pdo->query($sentence);           
            $preparedStament->execute();
            $result = $preparedStament->fetchAll(PDO::FETCH_ASSOC); 
            
            return $result;

        }catch(PDOException $e){
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al intentar insertar la recera: " . $e->getMessage()
            );
        }
    }

    public static function getRecipesListFromUser(){
        
        $user_id = $_GET['id'];

        try{
            $pdo = MysqlManager::get()->getDb();

            $sentence = "SELECT recipes.user_id, recipe_id, first_name, title, difficulty, nationality  FROM recipes
            INNER JOIN users ON users.user_id = recipes.user_id
            INNER JOIN difficulty ON recipes.difficulty_id = difficulty.difficulty_id
            WHERE recipes.user_id = $user_id";
            $preparedStament = $pdo->query($sentence);           
            $preparedStament->execute();
            $result = $preparedStament->fetchAll(PDO::FETCH_ASSOC); 
            
            return $result;

        }catch(PDOException $e){
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al intentar traer las recetas: " . $e->getMessage()
            );
        }       
    }

    public static function getRecipesListFromRecipeId(){
        
        $recipe_id = $_GET['id'];

        try{
            $pdo = MysqlManager::get()->getDb();

            $sentence = "SELECT title FROM recipes WHERE recipe_id = $recipe_id";
            $preparedStament = $pdo->query($sentence);           
            $preparedStament->execute();
            $result = $preparedStament->fetch(PDO::FETCH_ASSOC); 
            
            return $result;

        }catch(PDOException $e){
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al intentar traer las recetas: " . $e->getMessage()
            );
        }       
    }




    public static function insertImage(){
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);

        return $_FILES["uploadedfile"];

        if (isset($_FILES["uploadedfile"]) ||
            isset($_POST["user_id"]) ||
            isset($_POST["recipe_id"])){

            $user_id = $_POST["user_id"];
            $recipe_id = $_POST["recipe_id"];
            $target_path = "images/";
            
            $extension = pathinfo($_FILES["uploadedfile"]["name"], PATHINFO_EXTENSION);
            $rename = "uI".$user_id."rI".$recipe_id;
            
            if(move_uploaded_file($_FILES["uploadedfile"]["tmp_name"], "images/" . $rename . "." . $extension)) {
                return [
                    "target_path" => $target_path, 
                    "extension" => $extension,
                    "user_id" => $user_id,
                    "recipe_id" => $recipe_id,
                    "rename" => $rename
                ];
            } else{
                return ["status" => 500, "message" => "Ha ocurrido un error"];
            }
            
            
        }
        /*
        $target_path = "images/";
        $target_path = $target_path . basename($_FILES['uploadedfile']['name']); 
        $extension = pathinfo( $target_path, PATHINFO_EXTENSION);
        

        rename($target_path,"images/idUser39idRecipe30idStep1.".$extension);

        if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
            return ["status" => 201, "message" => "Imagen subida al servidor"];
        } else{
            return ["status" => 500, "message" => "Ha ocurrido un error"];
        }
        */
    }

    public static function insertImage2(){
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);

        if (
            !isset($decodedParameters["uploadedfile"]) ||
            !isset($decodedParameters["user_id"]) ||
            !isset($decodedParameters["recipe_id"])
        ){
            
        }
        
    }
}
