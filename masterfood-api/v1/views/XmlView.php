<?php

require_once "View.php";

/**
 * Clase para imprimir en la salida respuestas con formato XML
 */
class XmlView extends View
{

    public function render($body)
    {
        // Set de estado de le respuesta
        if (isset($body["status"])) {
            http_response_code($body["status"]);
        }

        // Set del contenido de la respuesta
        header('Content-Type: text/xml; charset=utf-8');

        $xml = new SimpleXMLElement('<apiResponse/>');
        self::arrayToXml($body, $xml);
        print $xml->asXML();

        exit;
    }

    public function arrayToXml($data, &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $subnode = $xml->addChild($key);
                self::arrayToXml($value, $subnode);
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
}

?>