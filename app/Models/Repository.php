<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class Repository extends Model
{
    private array $allowedTables = ['users','warehouses','warehouse_locations','items','inventory','requests'];

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
        if (!$data) return;
        $sets = implode(',', array_map(fn($col) => "{$col} = :{$col}", array_keys($data)));
        $data['id'] = $id;
        $this->db->prepare("UPDATE {$table} SET {$sets} WHERE id = :id")->execute($data);
    }

    public function delete(string $table, int $id): void
    {
        $this->guardTable($table);
        $this->db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
    }

    public function userByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :login OR name = :login LIMIT 1');
        $stmt->execute(['login'=>$login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function items(): array { return $this->all('items'); }
    public function warehouses(): array { return $this->all('warehouses'); }

    public function warehouseLocations(?int $warehouseId = null): array
    {
        $where = $warehouseId ? 'WHERE warehouse_locations.warehouse_id = :warehouse_id' : '';
        $stmt = $this->db->prepare("SELECT warehouse_locations.*, warehouses.name AS warehouse FROM warehouse_locations JOIN warehouses ON warehouses.id = warehouse_locations.warehouse_id {$where} ORDER BY warehouses.name, warehouse_locations.type, warehouse_locations.code");
        $stmt->execute($warehouseId ? ['warehouse_id'=>$warehouseId] : []);
        return $stmt->fetchAll();
    }

    public function saveInventory(array $data): void
    {
        $stmt = $this->db->prepare('SELECT id, quantity FROM inventory WHERE item_id = ? AND warehouse_id = ?');
        $stmt->execute([(int)$data['item_id'], (int)$data['warehouse_id']]);
        $existing = $stmt->fetch();
        if ($existing) {
            $this->db->prepare('UPDATE inventory SET quantity = quantity + :quantity, min_quantity = :min_quantity WHERE id = :id')->execute([
                'quantity'=>(float)$data['quantity'], 'min_quantity'=>(float)$data['min_quantity'], 'id'=>(int)$existing['id'],
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

    public function requests(?array $user = null): array
    {
        $where = '';
        $params = [];
        if ($user && !in_array(strtolower((string)($user['role'] ?? '')), ['admin', 'compras'], true)) {
            $where = 'WHERE requests.requester = :name';
            $params = ['name'=>$user['name'] ?? ''];
        }
        $stmt = $this->db->prepare("SELECT requests.*, items.name AS item, items.weighted_price, warehouses.name AS warehouse,
            (requests.quantity * items.weighted_price) AS request_value,
            ((requests.quantity - requests.delivered_quantity) * items.weighted_price) AS pending_value
            FROM requests JOIN items ON items.id=requests.item_id
            LEFT JOIN warehouses ON warehouses.id=requests.warehouse_id
            {$where}
            ORDER BY requests.created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function deliverRequest(int $id, float $quantity): void
    {
        $request = $this->find('requests', $id);
        if (!$request) return;
        $remaining = max(0, (float)$request['quantity'] - (float)$request['delivered_quantity']);
        $delivered = min(max($quantity, 0), $remaining);
        if ($delivered <= 0) return;
        $newDelivered = (float)$request['delivered_quantity'] + $delivered;
        $status = $newDelivered >= (float)$request['quantity'] ? 'Entregue' : 'Parcial';
        $this->db->prepare('UPDATE requests SET delivered_quantity = :delivered, status = :status WHERE id = :id')->execute(['delivered'=>$newDelivered,'status'=>$status,'id'=>$id]);
        if (!empty($request['warehouse_id'])) $this->adjustInventory((int)$request['item_id'], (int)$request['warehouse_id'], -$delivered);
    }

    public function setRequestStatus(int $id, string $status): void
    {
        $this->db->prepare('UPDATE requests SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public function importItems(array $file, bool $withLocation = false): array
    {
        $result = ['created'=>0,'updated'=>0,'stocked'=>0,'errors'=>[]];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { $result['errors'][] = 'Ficheiro inválido.'; return $result; }
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) { $result['errors'][] = 'Não foi possível abrir o CSV.'; return $result; }
        $line = 0;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $line++;
            if (count($row) === 1) $row = str_getcsv($row[0], ',');
            if ($line === 1 && preg_match('/nome|name|artigo/i', $row[0] ?? '')) continue;
            [$name,$designation,$unit,$price,$warehouse,$section,$location,$quantity,$minQuantity] = array_pad(array_map('trim', $row), 9, '');
            if ($name === '') { $result['errors'][] = "Linha {$line}: nome em falta."; continue; }
            $price = (float)str_replace(',', '.', $price ?: '0');
            $stmt = $this->db->prepare('SELECT id FROM items WHERE name = ? LIMIT 1');
            $stmt->execute([$name]);
            $id = $stmt->fetchColumn();
            $data = ['name'=>$name,'designation'=>$designation ?: $name,'unit'=>$unit ?: 'un','weighted_price'=>$price];
            if ($id) {
                $this->update('items', (int)$id, $data);
                $itemId = (int)$id;
                $result['updated']++;
            } else {
                $this->insert('items', $data);
                $itemId = (int)$this->db->lastInsertId();
                $result['created']++;
            }
            if ($withLocation) {
                if ($warehouse === '') { $result['errors'][] = "Linha {$line}: armazém em falta."; continue; }
                $warehouseId = $this->findOrCreateWarehouse($warehouse, $section, $location);
                if ($section !== '') $this->findOrCreateWarehouseLocation($warehouseId, 'Setor', $section, $section);
                if ($location !== '') $this->findOrCreateWarehouseLocation($warehouseId, 'Posição', $location, $location);
                $this->saveInventory(['item_id'=>$itemId,'warehouse_id'=>$warehouseId,'quantity'=>(float)str_replace(',', '.', $quantity ?: '0'),'min_quantity'=>(float)str_replace(',', '.', $minQuantity ?: '0')]);
                $result['stocked']++;
            }
        }
        fclose($handle);
        return $result;
    }

    private function findOrCreateWarehouse(string $name, string $section = '', string $location = ''): int
    {
        $stmt = $this->db->prepare('SELECT * FROM warehouses WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $stmt->execute([$name]);
        $warehouse = $stmt->fetch();
        if ($warehouse) {
            $updates = [];
            if ($section !== '' && in_array(trim((string)$warehouse['section']), ['', '-'], true)) $updates['section'] = $section;
            if ($location !== '' && in_array(trim((string)$warehouse['location']), ['', '-'], true)) $updates['location'] = $location;
            if ($updates) $this->update('warehouses', (int)$warehouse['id'], $updates);
            return (int)$warehouse['id'];
        }
        $this->insert('warehouses', ['name'=>$name, 'section'=>$section ?: '-', 'location'=>$location ?: '-']);
        return (int)$this->db->lastInsertId();
    }

    private function findOrCreateWarehouseLocation(int $warehouseId, string $type, string $code, string $description = ''): void
    {
        $stmt = $this->db->prepare('SELECT id FROM warehouse_locations WHERE warehouse_id = ? AND LOWER(type) = LOWER(?) AND LOWER(code) = LOWER(?) LIMIT 1');
        $stmt->execute([$warehouseId, $type, $code]);
        if ($stmt->fetchColumn()) return;
        $this->insert('warehouse_locations', ['warehouse_id'=>$warehouseId, 'type'=>$type, 'code'=>$code, 'description'=>$description]);
    }

    public function spendingByPeriod(?array $user = null): array
    {
        $filter = $this->isChief($user) ? 'AND requests.requester = :name' : '';
        $params = $filter ? ['name'=>$user['name'] ?? ''] : [];
        $sql = "SELECT
            COALESCE(SUM(CASE WHEN date(requests.created_at) >= date('now','-6 days') THEN requests.quantity*items.weighted_price END),0) week,
            COALESCE(SUM(CASE WHEN strftime('%Y-%m',requests.created_at)=strftime('%Y-%m','now') THEN requests.quantity*items.weighted_price END),0) month,
            COALESCE(SUM(CASE WHEN strftime('%Y',requests.created_at)=strftime('%Y','now') THEN requests.quantity*items.weighted_price END),0) year
            FROM requests JOIN items ON items.id=requests.item_id WHERE requests.status != 'Cancelado' {$filter}";
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetch() ?: ['week'=>0,'month'=>0,'year'=>0];
    }

    public function articleSpend(?array $user = null): array
    {
        $filter = $this->isChief($user) ? 'WHERE requests.requester = :name' : '';
        $stmt = $this->db->prepare("SELECT items.name item, ROUND(SUM(requests.quantity*items.weighted_price),2) total FROM requests JOIN items ON items.id=requests.item_id {$filter} GROUP BY items.id ORDER BY total DESC LIMIT 8");
        $stmt->execute($filter ? ['name'=>$user['name'] ?? ''] : []); return $stmt->fetchAll();
    }


    public function monthlyByTeam(?array $user = null): array
    {
        $filter = $this->isChief($user) ? 'WHERE requests.requester = :name' : '';
        $stmt = $this->db->prepare("SELECT strftime('%Y-%m', requests.created_at) AS month, requests.team,
            ROUND(SUM(requests.quantity * items.weighted_price),2) AS total
            FROM requests JOIN items ON items.id=requests.item_id
            {$filter}
            GROUP BY month, requests.team ORDER BY month ASC");
        $stmt->execute($filter ? ['name'=>$user['name'] ?? ''] : []);
        return $stmt->fetchAll();
    }

    public function dashboard(?array $user = null): array
    {
        return ['users'=>(int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn(),'warehouses'=>(int)$this->db->query('SELECT COUNT(*) FROM warehouses')->fetchColumn(),'items'=>(int)$this->db->query('SELECT COUNT(*) FROM items')->fetchColumn(),'stock_value'=>(float)$this->db->query('SELECT COALESCE(SUM(inventory.quantity*items.weighted_price),0) FROM inventory JOIN items ON items.id=inventory.item_id')->fetchColumn(),'spending'=>$this->spendingByPeriod($user),'article_spend'=>$this->articleSpend($user)];
    }

    private function isChief(?array $user = null): bool
    {
        return strtolower((string)($user['role'] ?? '')) === 'chefe';
    }

    private function guardTable(string $table): void
    {
        if (!in_array($table, $this->allowedTables, true)) throw new \InvalidArgumentException('Tabela inválida.');
    }
}
