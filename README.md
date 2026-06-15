# Calçada WMS

Aplicação PHP MVC sem framework para gestão de armazém, com Bootstrap, JavaScript, CSS responsivo e SQLite.

## Funcionalidades

- Gestão de utilizadores, roles e equipas.
- Gestão de armazéns, secções e localizações.
- Gestão de artigos com unidade e preço ponderado.
- Inventário com alertas de mínimo e exportação CSV/Excel e PDF.
- Requisição rápida a armazém para comunicação entre chefes de equipa e compras/stock.
- Gráficos mensais do valor dos pedidos por equipa.

## Instalar

Execute o instalador para validar requisitos, criar a pasta `data/` e inicializar a base de dados SQLite:

```bash
php install.php
```

Também pode abrir `/install.php` no navegador quando estiver a servir o projeto.

## Executar localmente

```bash
php -S localhost:8000 -t public
```

A base de dados SQLite é criada automaticamente em `data/wms.sqlite` na primeira execução, caso ainda não tenha sido criada pelo instalador.


## Login de administração

O WMS exige autenticação antes de abrir as páginas de gestão. As credenciais iniciais são:

- Utilizador: `admin`
- Palavra-passe: `admin123`

Em produção, configure as variáveis de ambiente `WMS_ADMIN_USER` e `WMS_ADMIN_PASSWORD` para substituir estes valores.
