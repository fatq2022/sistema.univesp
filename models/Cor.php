<?php
class Cor {
    private $conn;
    private $table = "cor";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar($apenasAtivos = false) {
        $sql = " SELECT id_cor, descricao FROM {$this->table} ORDER BY descricao ASC ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar($id) {
        $sql = " SELECT id_cor, descricao FROM {$this->table} WHERE id_cor = :id ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function criar($descricao) {
        $sql = " INSERT INTO {$this->table} (descricao) VALUES (:descricao) ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':descricao', $descricao);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function atualizar($id, $descricao) {
        $sql = " UPDATE {$this->table} SET descricao = :descricao WHERE id_cor = :id ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function atualizarAtivo($id, $ativo) {
        // Mantido por compatibilidade com o controlador, retorna true sem executar na coluna inexistente
        return true;
    }

    public function deletar($id) {
        $sql = " DELETE FROM {$this->table} WHERE id_cor = :id ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}