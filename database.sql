CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    role TEXT NOT NULL,
    team TEXT NOT NULL DEFAULT 'Geral',
    password_hash TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS warehouses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    section TEXT NOT NULL,
    location TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS warehouse_locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    warehouse_id INTEGER NOT NULL,
    type TEXT NOT NULL DEFAULT 'Setor',
    code TEXT NOT NULL,
    description TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(warehouse_id) REFERENCES warehouses(id)
);
CREATE TABLE IF NOT EXISTS items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    designation TEXT NOT NULL,
    unit TEXT NOT NULL,
    weighted_price REAL NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER NOT NULL,
    warehouse_id INTEGER NOT NULL,
    location TEXT NOT NULL DEFAULT '',
    quantity REAL NOT NULL DEFAULT 0,
    min_quantity REAL NOT NULL DEFAULT 0,
    FOREIGN KEY(item_id) REFERENCES items(id),
    FOREIGN KEY(warehouse_id) REFERENCES warehouses(id)
);
CREATE TABLE IF NOT EXISTS requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    requester TEXT NOT NULL,
    team TEXT NOT NULL,
    item_id INTEGER NOT NULL,
    warehouse_id INTEGER,
    quantity REAL NOT NULL,
    delivered_quantity REAL NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'Pendente',
    notes TEXT,
    request_group TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS action_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name TEXT NOT NULL,
    row_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    before_data TEXT,
    after_data TEXT,
    user_name TEXT,
    user_role TEXT,
    note TEXT,
    reverted INTEGER NOT NULL DEFAULT 0,
    reverted_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS purchase_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    requester_name TEXT,
    requester_team TEXT,
    article_name TEXT NOT NULL,
    quantity REAL NOT NULL,
    urgency INTEGER NOT NULL DEFAULT 1,
    link TEXT,
    status TEXT NOT NULL DEFAULT 'Pendente',
    status_changed_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS purchase_request_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_request_id INTEGER NOT NULL,
    old_status TEXT,
    new_status TEXT NOT NULL,
    changed_by TEXT,
    changed_role TEXT,
    changed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(purchase_request_id) REFERENCES purchase_requests(id)
);

CREATE TABLE IF NOT EXISTS material_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    responsible TEXT NOT NULL,
    requester_name TEXT,
    requester_team TEXT,
    department TEXT NOT NULL,
    product TEXT NOT NULL,
    operation TEXT NOT NULL,
    quantity REAL NOT NULL,
    completed_quantity REAL NOT NULL DEFAULT 0,
    urgency INTEGER NOT NULL DEFAULT 1,
    due_date TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'A Aguardar',
    notes TEXT,
    attachment_name TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
