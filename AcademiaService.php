<?php
class AcademiaService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /* ================= LOGIN ================= */

    public function loginAdmin($nome, $cpf, $senha) {
        $sql = "SELECT id 
                FROM admin 
                WHERE nome = ? AND cpf = ? AND senha = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nome, $cpf, $senha]);

        return $stmt->rowCount() === 1;
    }

    /* ================= LISTAR ALUNOS ================= */

    public function listarAlunos() {
        $sql = "SELECT * FROM alunos";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $alunos = [];

        while ($row = $stmt->fetch()) {

            $dataInicio = new DateTime($row['data_inicio']);
            $hoje = new DateTime();

            $diff = $dataInicio->diff($hoje);
            $mesesTranscorridos = max(1, ($diff->y * 12) + $diff->m);

            $valorMensalidade = (float)$row['mensalidade'];
            $valorTotal = $mesesTranscorridos * $valorMensalidade;

            $valorPago = (float)$row['valor_pago'];
            $valorFinal = max(0, $valorTotal - $valorPago);

            $status = $valorFinal > 0 ? 'pendente' : 'pago';

            $alunos[] = [
                "nome" => $row['nome'],
                "cpf" => $row['cpf'],
                "telefone" => $row['telefone'],
                "status" => $status,
                "meses_total" => $mesesTranscorridos,
                "mensalidade_base" => $valorMensalidade,
                "valor_total_devido" => round($valorFinal, 2)
            ];
        }

        return $alunos;
    }

    /* ================= ATUALIZAR STATUS ================= */

    public function atualizarStatus($cpf, $novoStatus) {
        if ($novoStatus !== 'pago') {
            return false;
        }

        $sql = "UPDATE alunos 
                SET valor_pago = (mensalidade * 999) 
                WHERE cpf = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$cpf]);
    }

    /* ================= APAGAR ALUNO ================= */

    public function apagarAluno($cpf) {
        $stmt = $this->db->prepare("DELETE FROM alunos WHERE cpf = ?");
        return $stmt->execute([$cpf]);
    }
}
