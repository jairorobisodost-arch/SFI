<?php
/**
 * SFI Queuing System - Standardized JSON Response Helper
 */

class Response {
    /**
     * Send a success JSON response.
     */
    public static function success($message = 'Success', $data = [], $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send an error JSON response.
     */
    public static function error($message = 'An error occurred', $errors = [], $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors'  => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send a JSON response with custom structure.
     */
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
