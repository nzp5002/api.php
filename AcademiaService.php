<?php

class AcademiaService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // --- LOGIN ADMIN ---
    public function loginAdmin($nome, $cpf) {
        $query = "SELECT * FROM administradores WHERE nome = :nome AND cpf = :cpf LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":cpf", $cpf);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- LISTAR ALUNOS (ADMIN) ---
    public function adminListarAlunos() {
        return $this->listarAlunos(); // reutiliza método já existente
    }

    // --- APAGAR ALUNO (ADMIN) ---
    public function adminApagarAluno($cpf) {
        return $this->apagarAluno($cpf); // reutiliza método já existente
    }

    // --- RESTO DO CÓDIGO EXISTENTE ---
    public function cadastrarAluno($nome, $cpf, $telefone, $valor) {
        $query = "INSERT INTO alunos (nome, cpf, telefone, valor_mensalidade, data_inicio)
                  VALUES (:nome, :cpf, :telefone, :valor, CURDATE())";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ":nome"     => $nome,
            ":cpf"      => $cpf,
            ":telefone" => $telefone,
            ":valor"    => $valor
        ]);
    }

    public function listarAlunos() {
        $query = "SELECT * FROM alunos ORDER BY nome ASC";
        $stmt = $this->db->query($query);
        $alunos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dataInicio = new DateTime($row['data_inicio']);
            $hoje = new DateTime();
            $intervalo = $dataInicio->diff($hoje);
            $meses = ($intervalo->y * 12) + $intervalo->m;

            $alunos[] = [
                "nome" => $row['nome'],
                "cpf" => $row['cpf'],
                "telefone" => $row['telefone'],
                "mensalidade" => (float)$row['valor_mensalidade'],
                "meses_na_academia" => $meses
            ];
        }
        return $alunos;
    }

    public function apagarAluno($cpf) {
        $stmt = $this->db->prepare("DELETE FROM alunos WHERE cpf = ?");
        $stmt->execute([$cpf]);
        return $stmt->rowCount() > 0;
    }
}
