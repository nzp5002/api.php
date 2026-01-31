<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

/* ⚠️ Em produção, troque pelo seu domínio */
header("Access-Control-Allow-Origin: *");

require_once "Database.php";
require_once "AcademiaService.php";

/* Sanitização básica */
foreach ($_POST as $k => $v) {
    $_POST[$k] = trim($v);
}

$db = (new Database())->getConnection();
$service = new AcademiaService($db);

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'login':
        $ok = $service->loginAdmin(
            $_POST['nome'] ?? '',
            $_POST['cpf'] ?? '',
            $_POST['senha'] ?? ''
        );

        echo json_encode([
            "status" => $ok ? "success" : "error"
        ]);
        break;

    case 'listar_alunos':
        echo json_encode([
            "data" => $service->listarAlunos()
        ]);
        break;

    case 'atualizar_status':
        $ok = $service->atualizarStatus(
            $_POST['cpf'] ?? '',
            $_POST['novo_status'] ?? ''
        );

        echo json_encode(["success" => $ok]);
        break;

    case 'apagar_aluno':
        $ok = $service->apagarAluno($_POST['cpf'] ?? '');
        echo json_encode(["success" => $ok]);
        break;

    default:
        http_response_code(400);
        echo json_encode(["error" => "Ação inválida"]);
}
