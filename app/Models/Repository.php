<?php
namespace App\Models;

use App\Core\Model;

class Repository extends Model
{
    public function all(string $table): array { return $this->db->query("SELECT * FROM {$table} ORDER BY id DESC")->fetchAll(); }
    public function insert(string $table, array $data): void
    {
        $cols = array_keys($data);
        $sql = "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";
        $this->db->prepare($sql)->execute($data);
    }
    public function delete(string $table, int $id): void { $this->db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]); }
    public function items(): array { return $this->all('items'); }
    public function warehouses(): array { return $this->all('warehouses'); }
    public function inventory(): array
    {
        return $this->db->query("SELECT inventory.*, items.name AS item, items.unit, items.weighted_price, warehouses.name AS warehouse,
            (inventory.quantity * items.weighted_price) AS stock_value
            FROM inventory JOIN items ON items.id=inventory.item_id JOIN warehouses ON warehouses.id=inventory.warehouse_id ORDER BY inventory.id DESC")->fetchAll();
    }
    public function requests(): array
    {
        return $this->db->query("SELECT requests.*, items.name AS item, items.weighted_price,
            (requests.quantity * items.weighted_price) AS request_value
            FROM requests JOIN items ON items.id=requests.item_id ORDER BY requests.created_at DESC")->fetchAll();
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
}
