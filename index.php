<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning");

// Trata requisições OPTIONS (preflight) para evitar erros de CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'Database.php';
require_once 'AcademiaService.php';

$db = (new Database())->getConnection();
if (!$db) {
    die(json_encode(["status" => "error", "message" => "Falha na conexão com o banco de dados"]));
}

$service = new AcademiaService($db);
$action = $_GET['action'] ?? '';

switch($action) {

    case 'login':
        $nome = $_POST['nome'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $senha = $_POST['senha'] ?? '';

        if (!$nome || !$cpf || !$senha) {
            echo json_encode(["status" => "error", "message" => "Preencha todos os campos de login"]);
            break;
        }

        $admin = $service->loginAdmin($nome, $cpf, $senha);
        echo json_encode($admin ? ["status" => "success", "admin" => $admin] : ["status" => "error", "message" => "Login ou senha inválidos"]);
        break;

    case 'listar_alunos':
        $alunos = $service->listarAlunos();
        echo json_encode(["status" => "success", "data" => $alunos]);
        break;

    case 'cadastrar_aluno':
        $nome = $_POST['nome'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $valor = $_POST['valor_base'] ?? 0;
        
        // Novos campos financeiros
        $status_pagamento = $_POST['status_pagamento'] ?? 'pendente';
        $tem_multa = $_POST['tem_multa'] ?? 0;
        $multa_perc = $_POST['multa_percentual'] ?? 0;
        $juros_perc = $_POST['juros_mensal_percentual'] ?? 0;

        // Limpeza de dados
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $telLimpo = preg_replace('/[^0-9]/', '', $telefone);

        if (strlen($cpfLimpo) != 11) {
            echo json_encode(["status" => "error", "message" => "CPF deve conter 11 dígitos"]);
            break;
        }

        // Verificação de duplicidade
        $stmt = $db->prepare("SELECT id FROM alunos WHERE cpf = ?");
        $stmt->execute([$cpfLimpo]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "Este CPF já está cadastrado no sistema"]);
            break;
        }

        // Execução do cadastro no Service
        $res = $service->cadastrarAluno(
            $nome, 
            $cpfLimpo, 
            $telLimpo, 
            $valor, 
            $status_pagamento, 
            $tem_multa, 
            $multa_perc, 
            $juros_perc
        );

        echo json_encode($res ? ["status" => "success"] : ["status" => "error", "message" => "Erro ao inserir no banco de dados"]);
        break;

    case 'apagar_aluno':
        $cpf = $_POST['cpf'] ?? '';
        if (!$cpf) {
            echo json_encode(["status" => "error", "message" => "CPF não fornecido para exclusão"]);
            break;
        }
        
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $apagado = $service->apagarAluno($cpfLimpo);
        
        if ($apagado) {
            echo json_encode(["status" => "success", "message" => "Aluno removido com sucesso"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Aluno não encontrado ou já removido"]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Ação não reconhecida"]);
        break;
}
