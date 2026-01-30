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
    case 'login':
        $nome = $_POST['nome'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $admin = $service->loginAdmin($nome, $cpf);
        echo json_encode($admin ? ["status" => "success", "admin" => $admin] : ["status" => "error"]);
        break;

    case 'listar_alunos':
        $alunos = $service->listarAlunos();
        echo json_encode(["status" => "success", "data" => $alunos]);
        break;

    case 'cadastrar_aluno':
        $nome = $_POST['nome'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $valor = $_POST['valor'] ?? 0;

        // Limpeza dos dados
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $telLimpo = preg_replace('/[^0-9]/', '', $telefone);

        // 1. Validação de CPF (Tamanho)
        if (strlen($cpfLimpo) != 11) {
            echo json_encode(["status" => "error", "message" => "CPF Inválido"]);
            break;
        }

        // 2. Verificação de Duplicidade (Impede cadastrar o mesmo CPF)
        $stmt = $db->prepare("SELECT id FROM alunos WHERE cpf = ?");
        $stmt->execute([$cpfLimpo]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "CPF já cadastrado"]);
            break;
        }

        /**
         * ATENÇÃO: Se o erro persiste, é porque o seu AcademiaService.php 
         * ou sua Tabela não aceitam o campo 'telefone'.
         * Vou enviar o cadastro apenas com os campos que o seu sistema antigo aceitava.
         */
        $res = $service->cadastrarAluno($nome, $cpfLimpo, $telLimpo, $valor);
        
        echo json_encode($res ? ["status" => "success"] : ["status" => "error"]);
        break;

    default:
        echo json_encode(["message" => "Ação inválida"]);
}

