# Calçada WMS

Aplicação PHP MVC sem framework para gestão de armazém, com Bootstrap, JavaScript, CSS responsivo e SQLite.

## Funcionalidades

- Gestão de utilizadores, roles, equipas e palavra-passe de acesso.
- Gestão de armazéns, secções e localizações.
- Gestão de artigos com unidade, preço ponderado e importação bulk por CSV exportado do SAGE.
- Inventário com alertas de mínimo e exportação CSV/Excel e PDF.
- Requisição rápida a armazém com pesquisa de artigos, edição de pedidos e entregas parciais com abatimento de stock.
- Gráficos mensais do valor dos pedidos por equipa e dashboard dos chefes filtrada às suas requisições com gastos semanais, mensais e anuais.

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
