<?php
namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dbPath = dirname(__DIR__, 2) . '/data/wms.sqlite';
            self::$pdo = new PDO('sqlite:' . $dbPath);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::migrate();
        }
        return self::$pdo;
    }

    private static function ensureColumn(string $table, string $column, string $definition): void
    {
        $columns = self::$pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll();
        foreach ($columns as $existing) {
            if ($existing['name'] === $column) {
                return;
            }
        }
        self::$pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
    }

    private static function migrate(): void
    {
        $sql = file_get_contents(dirname(__DIR__, 2) . '/database.sql');
        self::$pdo->exec($sql);
        self::ensureColumn('requests', 'warehouse_id', 'INTEGER');
        self::ensureColumn('users', 'password_hash', 'TEXT');
        self::ensureColumn('requests', 'delivered_quantity', 'REAL NOT NULL DEFAULT 0');
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS warehouse_locations (id INTEGER PRIMARY KEY AUTOINCREMENT, warehouse_id INTEGER NOT NULL, type TEXT NOT NULL DEFAULT 'Setor', code TEXT NOT NULL, description TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(warehouse_id) REFERENCES warehouses(id))");
        if ((int) self::$pdo->query('SELECT COUNT(*) FROM warehouse_locations')->fetchColumn() === 0 && (int) self::$pdo->query('SELECT COUNT(*) FROM warehouses')->fetchColumn() > 0) {
            self::$pdo->exec("INSERT INTO warehouse_locations (warehouse_id,type,code,description) SELECT id,'Setor',section,location FROM warehouses");
        }
        $count = (int) self::$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            self::$pdo->exec("INSERT INTO users (name,email,role,team,password_hash) VALUES
                ('Ana Silva','ana@empresa.local','Chefe','Operações','admin123'),
                ('Bruno Costa','bruno@empresa.local','Compras','Compras','admin123'),
                ('Carla Rocha','carla@empresa.local','Stock','Armazém','admin123')");
            self::$pdo->exec("INSERT INTO warehouses (name,section,location) VALUES
                ('Armazém Central','A - Matérias primas','Rua 1, Lisboa'),
                ('Armazém Norte','B - Consumíveis','Porto'),
                ('Armazém Sul','C - Expedição','Faro')");
            self::$pdo->exec("INSERT INTO warehouse_locations (warehouse_id,type,code,description) VALUES
                (1,'Setor','A','Matérias primas'),(1,'Posição','A-01','Parafusaria'),(2,'Setor','B','Consumíveis'),(3,'Posição','C-EXP','Expedição')");
            self::$pdo->exec("INSERT INTO items (name,designation,unit,weighted_price) VALUES
                ('Parafuso M8','Fixação zincada','un',0.18),
                ('Tinta branca','Balde 15L','lt',4.75),
                ('Luvas nitrilo','Caixa 100 unidades','cx',8.90)");
            self::$pdo->exec("INSERT INTO inventory (item_id,warehouse_id,quantity,min_quantity) VALUES
                (1,1,2500,500),(2,2,80,20),(3,1,120,30)");
            self::$pdo->exec("INSERT INTO requests (requester,team,item_id,warehouse_id,quantity,status,notes,created_at) VALUES
                ('Miguel','Manutenção',1,1,200,'Aprovado','Reposição preventiva',date('now','-2 months')),
                ('Rita','Produção',2,2,10,'Pendente','Linha 3',date('now','-1 month')),
                ('João','Manutenção',3,1,5,'Entregue','EPI urgente',date('now'))");
        }
    }
}
