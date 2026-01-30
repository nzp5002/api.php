<?php

// --- CORS e preflight ---
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'Database.php';
require_once 'AcademiaService.php';

$db = (new Database())->getConnection();
if (!$db) {
    die(json_encode(["status" => "error", "message" => "Falha na conexão"]));
}

$service = new AcademiaService($db);
$action = $_GET['action'] ?? '';

switch($action) {

    // --- LOGIN ADMIN ---
    case 'login':
        $nome = $_POST['nome'] ?? '';
        $cpf  = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $admin = $service->loginAdmin($nome, $cpf);

        if ($admin && password_verify($senha, $admin['senha'])) {
            echo json_encode(["status" => "success", "admin" => $admin]);
        } else {
            echo json_encode(["status" => "error", "message" => "Login inválido"]);
        }
        break;

    // --- LISTAR ALUNOS ---
    case 'listar_alunos':
        $alunos = $service->listarAlunos();
        echo json_encode(["status" => "success", "data" => $alunos]);
        break;

    // --- CADASTRAR ALUNO ---
    case 'cadastrar_aluno':
        $nome = $_POST['nome'] ?? '';
        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
        $valor = $_POST['valor'] ?? 0;

        // Validação CPF
        if (strlen($cpf) != 11) {
            echo json_encode(["status" => "error", "message" => "CPF Inválido"]);
            break;
        }

        // Verifica duplicidade
        $stmt = $db->prepare("SELECT id FROM alunos WHERE cpf = ?");
        $stmt->execute([$cpf]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "CPF já cadastrado"]);
            break;
        }

        $res = $service->cadastrarAluno($nome, $cpf, $telefone, $valor);
        echo json_encode($res ? ["status" => "success"] : ["status" => "error"]);
        break;

    // --- APAGAR ALUNO ---
    case 'apagar_aluno':
        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        if (!$cpf) {
            echo json_encode(["status" => "error", "message" => "CPF não informado"]);
            break;
        }

        $apagado = $service->apagarAluno($cpf);
        if ($apagado) {
            echo json_encode(["status" => "success", "message" => "Aluno apagado com sucesso"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Aluno não encontrado"]);
        }
        break;

    // --- DEFAULT ---
    default:
        echo json_encode(["status" => "error", "message" => "Ação inválida"]);
        break;
}
