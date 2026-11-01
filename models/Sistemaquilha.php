<?php
class Sistemaquilha {
    private $conn;
    private $table = "sistemaquilha";

    public function __construct($db) {
        $this->conn = $db;
    }

    /* ========================= LISTAR ========================= */
    public function listar($apenasAtivos = false) {
        $sql = " SELECT id_sistemaquilha, descricao FROM {$this->table} ";
        
        // Mantemos o parâmetro na função para não quebrar o controlador, 
        // mas ignoramos a cláusula WHERE ativo pois a coluna foi removida da BD.
        
        $sql .= " ORDER BY descricao ASC ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ========================= BUSCAR ========================= */
    public function buscar($id) {
        $sql = " SELECT id_sistemaquilha, descricao FROM {$this->table} WHERE id_sistemaquilha = :id ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ========================= CRIAR ========================= */
    public function criar($descricao) {
        $sql = " INSERT INTO {$this->table} (descricao) VALUES (:descricao) ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':descricao', $descricao);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /* ========================= ATUALIZAR ========================= */
    public function atualizar($id, $descricao) {
        $sql = " UPDATE {$this->table} SET descricao = :descricao WHERE id_sistemaquilha = :id ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /* ========================= ATUALIZAR ATIVO ========================= */
    public function atualizarAtivo($id, $ativo) {
        // Mantido por compatibilidade caso o controlador o chame, 
        // mas retorna true para evitar erros de execução.
        return true;
    }

    /* ========================= DELETAR ========================= */
    public function deletar($id) {
        $sql = " DELETE FROM {$this->table} WHERE id_sistemaquilha = :id ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}