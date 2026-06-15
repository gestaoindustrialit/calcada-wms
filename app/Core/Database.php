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

    private static function migrate(): void
    {
        $sql = file_get_contents(dirname(__DIR__, 2) . '/database.sql');
        self::$pdo->exec($sql);
        $count = (int) self::$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            self::$pdo->exec("INSERT INTO users (name,email,role,team) VALUES
                ('Ana Silva','ana@empresa.local','Chefe','Operações'),
                ('Bruno Costa','bruno@empresa.local','Compras','Compras'),
                ('Carla Rocha','carla@empresa.local','Stock','Armazém')");
            self::$pdo->exec("INSERT INTO warehouses (name,section,location) VALUES
                ('Armazém Central','A - Matérias primas','Rua 1, Lisboa'),
                ('Armazém Norte','B - Consumíveis','Porto'),
                ('Armazém Sul','C - Expedição','Faro')");
            self::$pdo->exec("INSERT INTO items (name,designation,unit,weighted_price) VALUES
                ('Parafuso M8','Fixação zincada','un',0.18),
                ('Tinta branca','Balde 15L','lt',4.75),
                ('Luvas nitrilo','Caixa 100 unidades','cx',8.90)");
            self::$pdo->exec("INSERT INTO inventory (item_id,warehouse_id,quantity,min_quantity) VALUES
                (1,1,2500,500),(2,2,80,20),(3,1,120,30)");
            self::$pdo->exec("INSERT INTO requests (requester,team,item_id,quantity,status,notes,created_at) VALUES
                ('Miguel','Manutenção',1,200,'Aprovado','Reposição preventiva',date('now','-2 months')),
                ('Rita','Produção',2,10,'Pendente','Linha 3',date('now','-1 month')),
                ('João','Manutenção',3,5,'Entregue','EPI urgente',date('now'))");
        }
    }
}
