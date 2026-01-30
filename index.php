<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning");

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
        $senha = $_POST['senha'] ?? ''; // adiciona senha

        if (!$nome || !$cpf || !$senha) {
            echo json_encode(["status" => "error", "message" => "Preencha todos os campos"]);
            break;
        }

        $admin = $service->loginAdmin($nome, $cpf, $senha); // agora envia senha
        echo json_encode($admin ? ["status" => "success", "admin" => $admin] : ["status" => "error", "message"=>"Login inválido"]);
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

        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $telLimpo = preg_replace('/[^0-9]/', '', $telefone);

        if (strlen($cpfLimpo) != 11) {
            echo json_encode(["status" => "error", "message" => "CPF Inválido"]);
            break;
        }

        $stmt = $db->prepare("SELECT id FROM alunos WHERE cpf = ?");
        $stmt->execute([$cpfLimpo]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "CPF já cadastrado"]);
            break;
        }

        $res = $service->cadastrarAluno($nome, $cpfLimpo, $telLimpo, $valor);
        echo json_encode($res ? ["status" => "success"] : ["status" => "error"]);
        break;

    case 'apagar_aluno':
        $cpf = $_POST['cpf'] ?? '';
        if (!$cpf) {
            echo json_encode(["status"=>"error","message"=>"CPF não fornecido"]);
            break;
        }
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $apagado = $service->apagarAluno($cpfLimpo);
        if ($apagado) {
            echo json_encode(["status" => "success", "message" => "Aluno apagado com sucesso"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Aluno não encontrado"]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Ação inválida"]);
}
