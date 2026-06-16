<?php
namespace App\Models;

use App\Core\Model;

class Repository extends Model
{
    private array $allowedTables = ['users','warehouses','items','inventory','requests'];

    public function all(string $table): array
    {
        $this->guardTable($table);
        return $this->db->query("SELECT * FROM {$table} ORDER BY id DESC")->fetchAll();
    }

    public function find(string $table, int $id): ?array
    {
        $this->guardTable($table);
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insert(string $table, array $data): void
    {
        $this->guardTable($table);
        $cols = array_keys($data);
        $sql = "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";
        $this->db->prepare($sql)->execute($data);
    }

    public function update(string $table, int $id, array $data): void
    {
        $this->guardTable($table);
        $sets = implode(',', array_map(fn($col) => "{$col} = :{$col}", array_keys($data)));
        $data['id'] = $id;
        $this->db->prepare("UPDATE {$table} SET {$sets} WHERE id = :id")->execute($data);
    }

    public function delete(string $table, int $id): void
    {
        $this->guardTable($table);
        $this->db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
    }

    public function items(): array { return $this->all('items'); }
    public function warehouses(): array { return $this->all('warehouses'); }

    public function saveInventory(array $data): void
    {
        $stmt = $this->db->prepare('SELECT id, quantity FROM inventory WHERE item_id = ? AND warehouse_id = ?');
        $stmt->execute([(int)$data['item_id'], (int)$data['warehouse_id']]);
        $existing = $stmt->fetch();
        if ($existing) {
            $this->db->prepare('UPDATE inventory SET quantity = quantity + :quantity, min_quantity = :min_quantity WHERE id = :id')->execute([
                'quantity'=>(float)$data['quantity'],
                'min_quantity'=>(float)$data['min_quantity'],
                'id'=>(int)$existing['id'],
            ]);
            return;
        }
        $this->insert('inventory', $data);
    }

    public function adjustInventory(int $itemId, int $warehouseId, float $quantity): void
    {
        $stmt = $this->db->prepare('UPDATE inventory SET quantity = MAX(quantity + :quantity, 0) WHERE item_id = :item_id AND warehouse_id = :warehouse_id');
        $stmt->execute(['quantity'=>$quantity, 'item_id'=>$itemId, 'warehouse_id'=>$warehouseId]);
    }

    public function inventory(): array
    {
        return $this->db->query("SELECT inventory.*, items.name AS item, items.unit, items.weighted_price, warehouses.name AS warehouse,
            (inventory.quantity * items.weighted_price) AS stock_value
            FROM inventory JOIN items ON items.id=inventory.item_id JOIN warehouses ON warehouses.id=inventory.warehouse_id ORDER BY inventory.id DESC")->fetchAll();
    }

    public function requests(): array
    {
        return $this->db->query("SELECT requests.*, items.name AS item, items.weighted_price, warehouses.name AS warehouse,
            (requests.quantity * items.weighted_price) AS request_value
            FROM requests JOIN items ON items.id=requests.item_id
            LEFT JOIN warehouses ON warehouses.id=requests.warehouse_id
            ORDER BY requests.created_at DESC")->fetchAll();
    }

    public function setRequestStatus(int $id, string $status): void
    {
        $this->db->prepare('UPDATE requests SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public function monthlyByTeam(): array
    {
        return $this->db->query("SELECT strftime('%Y-%m', requests.created_at) AS month, requests.team,
            ROUND(SUM(requests.quantity * items.weighted_price),2) AS total
            FROM requests JOIN items ON items.id=requests.item_id
            GROUP BY month, requests.team ORDER BY month ASC")->fetchAll();
    }

    public function dashboard(): array
    {
        return [
            'users'=>(int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'warehouses'=>(int)$this->db->query('SELECT COUNT(*) FROM warehouses')->fetchColumn(),
            'items'=>(int)$this->db->query('SELECT COUNT(*) FROM items')->fetchColumn(),
            'stock_value'=>(float)$this->db->query('SELECT COALESCE(SUM(inventory.quantity*items.weighted_price),0) FROM inventory JOIN items ON items.id=inventory.item_id')->fetchColumn(),
        ];
    }

    private function guardTable(string $table): void
    {
        if (!in_array($table, $this->allowedTables, true)) {
            throw new \InvalidArgumentException('Tabela inválida.');
        }
    }
}
