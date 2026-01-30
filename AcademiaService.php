<?php

class AcademiaService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function loginAdmin($nome, $cpf, $senha) {
        // Nota: Idealmente use password_verify se a senha estiver hashada
        $query = "SELECT * FROM administradores WHERE nome = :nome AND cpf = :cpf AND senha = :senha LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([":nome" => $nome, ":cpf" => $cpf, ":senha" => $senha]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cadastrarAluno($nome, $cpf, $telefone, $valor, $status_pagamento, $tem_multa, $multa_perc, $juros_perc) {
        $query = "INSERT INTO alunos (nome, cpf, telefone, valor_mensalidade, data_inicio, status_pagamento, tem_multa, multa_percentual, juros_mensal_percentual)
                  VALUES (:nome, :cpf, :telefone, :valor, CURDATE(), :status_p, :tem_m, :multa_p, :juros_p)";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ":nome"     => $nome,
            ":cpf"      => $cpf,
            ":telefone" => $telefone,
            ":valor"    => $valor,
            ":status_p" => $status_pagamento,
            ":tem_m"    => $tem_multa,
            ":multa_p"  => $multa_perc,
            ":juros_p"  => $juros_perc
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
            
            // Total de meses desde o início (considera anos bissextos automaticamente)
            $mesesTranscorridos = ($intervalo->y * 12) + $intervalo->m;
            if ($mesesTranscorridos == 0) $mesesTranscorridos = 1; // Mês atual

            $valorBase = (float)$row['valor_mensalidade'];
            $valorTotalAcumulado = $valorBase;
            $status = $row['status_pagamento'];

            // Lógica de Acúmulo e Multa para Pendentes
            if ($status === 'pendente') {
                $multa = 0;
                $juros = 0;

                if ($row['tem_multa']) {
                    // Aplica multa fixa sobre o valor base
                    $multa = $valorBase * ($row['multa_percentual'] / 100);
                    // Aplica juros simples por cada mês de atraso
                    $juros = ($valorBase * ($row['juros_mensal_percentual'] / 100)) * $mesesTranscorridos;
                }

                // Acumula as mensalidades dos meses que passaram + taxas
                $valorTotalAcumulado = ($valorBase * $mesesTranscorridos) + $multa + $juros;
            }

            $alunos[] = [
                "nome" => $row['nome'],
                "cpf" => $row['cpf'],
                "telefone" => $row['telefone'],
                "status" => $status,
                "mensalidade_base" => $valorBase,
                "valor_total_devido" => round($valorTotalAcumulado, 2),
                "meses_total" => $mesesTranscorridos
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
