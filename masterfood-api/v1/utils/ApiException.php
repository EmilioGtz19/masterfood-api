<?php

/**
 * Excepción personalizada para el envío de respuestas
 */
class ApiException extends Exception {
    private $status;
    private $apiCode;
    private $userMessage;
    private $moreInfo;
    private $developerMessage;

    public function __construct($status, $code, $message, $moreInfo, $developerMessage) {
        $this->status = $status;
        $this->apiCode = $code;
        $this->userMessage = $message;
        $this->moreInfo = $moreInfo;
        $this->developerMessage = $developerMessage;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getApiCode() {
        return $this->apiCode;
    }

    public function getUserMessage() {
        return $this->userMessage;
    }

    public function getMoreInfo() {
        return $this->moreInfo;
    }

    public function getDeveloperMessage() {
        return $this->developerMessage;
    }

    public function toArray() {
        $errorBody = array(
            "status" => $this->status,
            "code" => $this->apiCode,
            "message" => $this->userMessage,
            "moreInfo" => $this->moreInfo,
            "developerMessage" => $this->developerMessage
        );
        return $errorBody;
    }
}

?>