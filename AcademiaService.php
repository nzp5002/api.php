<?php

class AcademiaService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Realiza o login do administrador
     */
    public function loginAdmin($nome, $cpf, $senha) {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $stmt = $this->db->prepare("SELECT * FROM admin WHERE nome = ? AND cpf = ? AND senha = ?");
        $stmt->execute([$nome, $cpfLimpo, $senha]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista todos os alunos com cálculos financeiros em tempo real
     */
    public function listarAlunos() {
        $query = "SELECT * FROM alunos ORDER BY nome ASC";
        $stmt = $this->db->query($query);
        $alunos = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Cálculo de meses (Diferença entre data de início e hoje)
            $dataInicio = new DateTime($row['data_inicio']);
            $hoje = new DateTime();
            $intervalo = $dataInicio->diff($hoje);
            
            // Total de meses completos transcorridos
            $mesesTranscorridos = ($intervalo->y * 12) + $intervalo->m;

            $valorMensalidade = (float)$row['valor_mensalidade'];
            $status = $row['status_pagamento']; // 'pago' ou 'pendente'
            
            // Lógica de Cobrança:
            // Se estiver PAGO: O valor devido é apenas a mensalidade base do mês atual.
            // Se estiver PENDENTE: Acumula (meses transcorridos + 1) * valor base.
            if ($status === 'pendente') {
                $quantidadeMensalidades = $mesesTranscorridos + 1;
                $subtotal = $valorMensalidade * $quantidadeMensalidades;

                // Aplicação opcional de juros (1% a 50%)
                if ($row['aplicar_juros'] == 1 && $row['juros_valor'] > 0) {
                    $taxaJuros = (float)$row['juros_valor'] / 100;
                    $valorComJuros = $subtotal + ($subtotal * $taxaJuros);
                } else {
                    $valorComJuros = $subtotal;
                }
            } else {
                $valorComJuros = $valorMensalidade;
            }

            $alunos[] = [
                "id" => $row['id'],
                "nome" => $row['nome'],
                "cpf" => $row['cpf'],
                "telefone" => $row['telefone'],
                "status" => $status,
                "meses_total" => $mesesTranscorridos,
                "mensalidade_base" => $valorMensalidade,
                "valor_total_devido" => round($valorComJuros, 2),
                "juros_ativo" => (bool)$row['aplicar_juros'],
                "juros_valor" => $row['juros_valor']
            ];
        }
        return $alunos;
    }

    /**
     * Cadastra um novo aluno
     */
    public function cadastrarAluno($nome, $cpf, $telefone, $valor, $status, $aplicarJuros, $valorJuros) {
        $query = "INSERT INTO alunos (nome, cpf, telefone, valor_mensalidade, status_pagamento, aplicar_juros, juros_valor, data_inicio) 
                  VALUES (:nome, :cpf, :tel, :valor, :status, :juros_ativo, :juros_val, CURDATE())";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ":nome" => $nome,
            ":cpf" => $cpf,
            ":tel" => $telefone,
            ":valor" => $valor,
            ":status" => $status,
            ":juros_ativo" => $aplicarJuros,
            ":juros_val" => $valorJuros
        ]);
    }

    /**
     * Atualiza o status de pagamento (Baixa na dívida)
     * Quando o aluno paga, a data_inicio é resetada para hoje para reiniciar o ciclo de meses.
     */
    public function atualizarStatus($cpf, $novoStatus) {
        $query = "UPDATE alunos SET status_pagamento = :status, data_inicio = CURDATE() WHERE cpf = :cpf";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ":status" => $novoStatus,
            ":cpf" => $cpf
        ]);
    }

    /**
     * Remove um aluno do sistema
     */
    public function apagarAluno($cpf) {
        $stmt = $this->db->prepare("DELETE FROM alunos WHERE cpf = ?");
        return $stmt->execute([$cpf]);
    }
}
