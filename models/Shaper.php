<?php

class Shaper {

    private $conn;
    private $table = "shaper";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function atualizarAtivo($id, $ativo) {
        // Ajustado para usar {$this->table} e o nome correto da coluna id_shaper
        $sql = "UPDATE {$this->table} SET ativo = :ativo WHERE id_shaper = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":id" => $id, 
            ":ativo" => $ativo
        ]);
    }

    public function listarAtivos() {
        $sql = "SELECT id_shaper AS id, nome FROM {$this->table} WHERE ativo = 1 ORDER BY nome ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    /* =========================
       LISTAR
    ========================= */
    public function listar() {

        $sql = "
            SELECT id_shaper AS id, nome, email, telefone, ativo
            FROM {$this->table}
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       BUSCAR
    ========================= */
    public function buscar($id) {

        $sql = "
            SELECT id_shaper AS id, nome, email, telefone 
            FROM {$this->table}
            WHERE id_shaper = :id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
	
	/* =========================
	   BUSCAR POR TODOS OS CAMPOS (LIKE)
	   ========================= */
	public function buscarPorCampos($termo) {

		$termo = "%{$termo}%"; // para usar no LIKE

		$sql = "
			SELECT id_shaper AS id, nome, email, telefone
			FROM {$this->table}
			WHERE nome LIKE :termo
			   OR email LIKE :termo
			   OR telefone LIKE :termo
		";

		$stmt = $this->conn->prepare($sql);
		$stmt->bindParam(':termo', $termo, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}	

    /* =========================
       CRIAR
    ========================= */
    public function criar($nome, $email, $telefone) {

        $sql = "
            INSERT INTO {$this->table} (nome, email, telefone)
            VALUES (:nome, :email, :telefone)
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefone', $telefone);

        return $stmt->execute();
    }

    /* =========================
       ATUALIZAR
    ========================= */
    public function atualizar($id, $nome, $email, $telefone) {

        $sql = "
            UPDATE {$this->table}
            SET nome = :nome, email = :email, telefone = :telefone
            WHERE id_shaper = :id
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /* =========================
       DELETAR
    ========================= */
    public function deletar($id) {

        $sql = "
            DELETE FROM {$this->table}
            WHERE id_shaper = :id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}