<?php

require 'controllers/Users.php';
require 'controllers/Recipes.php';
require 'views/XmlView.php';
require 'views/JsonView.php';
require 'utils/ApiException.php';

// Obtener valor del parámetro 'format' para el formato de la respuesta
$format = isset($_GET['format']) ? $_GET['format'] : 'json';

// Crear representación de la vista para el formato elegido
if (strcasecmp($format, 'xml') == 0) {
    $apiView = new XmlView();
} else {
    $apiView = new JsonView();
}

// Registrar manejador de excepciones
set_exception_handler(
    function (ApiException $exception) use ($apiView) {
        http_response_code($exception->getStatus());
        $apiView->render($exception->toArray());
    }
);

// Prefabricar excepciones posibles
$resourceNotFound = new ApiException(404, 1000, "El recurso al que intentas acceder no existe", "http://localhost",
    "No existe un resource definido en: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");


// Extraer segmento de la url
if (isset($_GET['PATH_INFO'])) {
    $urlSegments = explode('/', $_GET['PATH_INFO']);
} else {
    throw $resourceNotFound;
}

// Obtener recurso
$resource = array_shift($urlSegments);
$apiResources = array('Users','Recipes');

// Comprobar si existe el recurso
if (!in_array($resource, $apiResources)) {
    throw $resourceNotFound;
}

// Transformar método HTTP a minúsculas
$httpMethod = strtolower($_SERVER['REQUEST_METHOD']);

// Determinar acción según el método HTTP
switch ($httpMethod) {
    case 'get':
    case 'post':
    case 'put':
    case 'delete':
        if (method_exists($resource, $httpMethod)) {
            $apiResponse = call_user_func(array($resource, $httpMethod), $urlSegments);
            $apiView->render($apiResponse);
            break;
        }
    default:
        // Método no permitido sobre el recurso
        $methodNotAllowed = new ApiException(
            405,
            1001,
            "Acción no permitida",
            "http://localhost",
            "No se puede aplicar el método $_SERVER[REQUEST_METHOD] sobre el recurso \"$resource\"");
        $apiView->render($methodNotAllowed->toArray());

}

?>


