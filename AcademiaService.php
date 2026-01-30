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
        // Remove qualquer máscara do CPF para comparar apenas números
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
            $dataInicio = new DateTime($row['data_inicio']);
            $hoje = new DateTime();
            $intervalo = $dataInicio->diff($hoje);
            
            // Meses transcorridos (Começa em 0 no dia da matrícula)
            $mesesTranscorridos = ($intervalo->y * 12) + $intervalo->m;

            $valorMensalidade = (float)$row['valor_mensalidade'];
            $status = $row['status_pagamento'];
            
            if ($status === 'pendente') {
                // Se está pendente, cobramos o mês atual (0) + meses extras
                $quantidadeMensalidades = $mesesTranscorridos + 1;
                $subtotal = $valorMensalidade * $quantidadeMensalidades;

                // Aplicação de juros opcional (calculado sobre o subtotal acumulado)
                if ($row['aplicar_juros'] == 1 && $row['juros_valor'] > 0) {
                    $taxaJuros = (float)$row['juros_valor'] / 100;
                    $valorFinal = $subtotal + ($subtotal * $taxaJuros);
                } else {
                    $valorFinal = $subtotal;
                }
            } else {
                // Se já pagou, o valor exibido é apenas o valor base da mensalidade
                $valorFinal = $valorMensalidade;
            }

            $alunos[] = [
                "id" => $row['id'],
                "nome" => $row['nome'],
                "cpf" => $row['cpf'],
                "telefone" => $row['telefone'],
                "status" => $status,
                "meses_total" => $mesesTranscorridos,
                "mensalidade_base" => $valorMensalidade,
                "valor_total_devido" => round($valorFinal, 2),
                "juros_ativo" => (bool)$row['aplicar_juros'],
                "juros_valor" => (float)$row['juros_valor']
            ];
        }
        return $alunos;
    }

    /**
     * Busca um aluno específico pelo CPF
     */
    public function buscarAlunoPorCpf($cpf) {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $stmt = $this->db->prepare("SELECT * FROM alunos WHERE cpf = ?");
        $stmt->execute([$cpfLimpo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastra um novo aluno com limpeza de CPF
     */
    public function cadastrarAluno($nome, $cpf, $telefone, $valor, $status, $aplicarJuros, $valorJuros) {
        // Limpa o CPF antes de inserir para evitar erros de busca depois
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica se o CPF já existe
        if($this->buscarAlunoPorCpf($cpfLimpo)) {
            return false; 
        }

        $query = "INSERT INTO alunos (nome, cpf, telefone, valor_mensalidade, status_pagamento, aplicar_juros, juros_valor, data_inicio) 
                  VALUES (:nome, :cpf, :tel, :valor, :status, :juros_ativo, :juros_val, CURDATE())";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ":nome" => $nome,
            ":cpf" => $cpfLimpo,
            ":tel" => $telefone,
            ":valor" => $valor,
            ":status" => $status,
            ":juros_ativo" => $aplicarJuros ? 1 : 0,
            ":juros_val" => $valorJuros
        ]);
    }

    /**
     * Atualiza o status e REINICIA o ciclo de cobrança
     */
    public function atualizarStatus($cpf, $novoStatus) {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        
        // Se o status for 'pago', atualizamos a data_inicio para hoje.
        // Isso faz com que no próximo mês a dívida comece do zero novamente.
        if ($novoStatus === 'pago') {
            $query = "UPDATE alunos SET status_pagamento = 'pago', data_inicio = CURDATE() WHERE cpf = :cpf";
        } else {
            $query = "UPDATE alunos SET status_pagamento = 'pendente' WHERE cpf = :cpf";
        }
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([":cpf" => $cpfLimpo]);
    }

    /**
     * Remove um aluno
     */
    public function apagarAluno($cpf) {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $stmt = $this->db->prepare("DELETE FROM alunos WHERE cpf = ?");
        return $stmt->execute([$cpfLimpo]);
    }
}
