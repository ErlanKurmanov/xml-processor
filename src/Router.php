<?php
require_once __DIR__ . '/Services/FamilyService.php';

class Router {
    private FamilyService $service;

    public function __construct() {
        $this->service = new FamilyService();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));

        header('Content-Type: application/json');

        try {
            // POST /api/family/process (Синхронно)
            if ($method === 'POST' && $path === '/api/family/process') {
                $input = file_get_contents('php://input');
                header('Content-Type: application/xml');
                echo $this->service->processLogic($input);
                return;
            }

            // POST /api/family/upload (Асинхронно - старт)
            if ($method === 'POST' && $path === '/api/family/upload') {
                $input = file_get_contents('php://input');
                $id = $this->service->createTask($input);
                echo json_encode(['taskId' => $id, 'status' => 'PENDING']);
                return;
            }

            // GET /api/family/{taskId}/result (Асинхронно - проверка)
            if ($method === 'GET' && isset($parts[2]) && $parts[3] === 'result') {
                $task = $this->service->getTask((int)$parts[2]);
                if (!$task) throw new Exception("Task not found", 404);

                if ($task['status'] === 'DONE') {
                    header('Content-Type: application/xml');
                    echo $task['result_xml'];
                } else {
                    echo json_encode(['status' => $task['status']]);
                }
                return;
            }

            // GET /api/family/members/{id}
            if ($method === 'GET' && isset($parts[3]) && !isset($parts[4])) {
                $data = $this->service->getMember((int)$parts[3]);
                echo json_encode($data ?: ['error' => 'Not found']);
                return;
            }

            // GET /api/family/members/{id}/real-estate
            if ($method === 'GET' && isset($parts[4]) && $parts[4] === 'real-estate') {
                $data = $this->service->getMemberRealEstate((int)$parts[3]);
                echo json_encode($data);
                return;
            }

            throw new Exception("Route not found", 404);

        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}