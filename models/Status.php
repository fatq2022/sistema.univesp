<?php
class Status {
    private $conn;
    private $table = "status";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        // 🔥 Adicionado 'ativo' na listagem
        $sql = "SELECT id_status AS id, descricao, ordem, ativo FROM {$this->table} ORDER BY ordem ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar($id) {
        // 🔥 Adicionado 'ativo' na busca
        $sql = "SELECT id_status AS id, descricao, ordem, ativo FROM {$this->table} WHERE id_status = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function criar($descricao, $ordem) {
        // 🔥 Garantindo que o novo status comece como ATIVO (1)
        $sql = "INSERT INTO {$this->table} (descricao, ordem, ativo) VALUES (:descricao, :ordem, 1)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":descricao" => $descricao,
            ":ordem" => $ordem
        ]);
    }

    public function atualizar($id, $descricao = null, $ordem = 0, $somenteOrdem = false) {
        if ($somenteOrdem) {
            $sql = "UPDATE {$this->table} SET ordem = :ordem WHERE id_status = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ":id" => $id,
                ":ordem" => $ordem
            ]);
        }
        $sql = "UPDATE {$this->table} SET descricao = :descricao, ordem = :ordem WHERE id_status = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":id" => $id,
            ":descricao" => $descricao,
            ":ordem" => $ordem
        ]);
    }

    // 🔥 NOVO MÉTODO: Para o switch de Ativar/Desativar
    public function atualizarAtivo($id, $ativo) {
        $sql = "UPDATE {$this->table} SET ativo = :ativo WHERE id_status = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":id" => $id,
            ":ativo" => $ativo
        ]);
    }

    public function deletar($id) {
        $sql = "DELETE FROM {$this->table} WHERE id_status = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":id" => $id]);
    }

    public function buscarSequencia($idStatusAtual) {
        $sqlAtual = "SELECT ordem FROM {$this->table} WHERE id_status = :id";
        $stmt = $this->conn->prepare($sqlAtual);
        $stmt->execute([':id' => $idStatusAtual]);
        $atual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$atual) {
            // Pega o primeiro status ativo do fluxo
            $sqlProx = "SELECT id_status, descricao FROM {$this->table} WHERE ativo = 1 ORDER BY ordem ASC LIMIT 1";
            $stmtProx = $this->conn->prepare($sqlProx);
            $stmtProx->execute();
        } else {
            // Pega o próximo status ativo baseado na ordem
            $sqlProx = "SELECT id_status, descricao FROM {$this->table} WHERE ordem > :ordem AND ativo = 1 ORDER BY ordem ASC LIMIT 1";
            $stmtProx = $this->conn->prepare($sqlProx);
            $stmtProx->execute([':ordem' => $atual['ordem']]);
        }
        return $stmtProx->fetch(PDO::FETCH_ASSOC);
    }
}
