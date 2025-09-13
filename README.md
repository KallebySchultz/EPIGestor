# Sistema de Gestão de EPIs Klarbyte

Sistema web para controle de equipamentos de proteção individual, desenvolvido em HTML, CSS, PHP e MySQL.

## Características do Sistema

- **Interface responsiva** com tema azul e branco
- **Gestão completa de EPIs** com controle de validade e estoque
- **Histórico de movimentações** (entradas, retiradas, devoluções, descartes)
- **Alertas visuais** para estoque baixo e produtos vencidos
- **Sistema de usuários** com autenticação
- **Dashboard** com estatísticas em tempo real

## Funcionalidades

### Gestão de EPIs
- Cadastro de EPIs com nome, descrição, validade, quantidade mínima e saldo
- Edição e exclusão de equipamentos
- Busca por nome ou descrição
- Status visual (OK, Estoque Baixo, Vencido)

### Controle de Estoque
- Registro de entradas de estoque
- Controle de retiradas com responsável e empresa
- Registro de devoluções
- Controle de descartes
- Histórico completo de movimentações
- Atualização automática do saldo

### Sistema de Usuários
- Login com email e senha
- Criação e edição de usuários
- Ativação/desativação de contas
- Todos os usuários têm acesso completo (não há níveis diferentes)

### Dashboard
- Estatísticas gerais (total de EPIs, vencidos, estoque baixo, movimentações do dia)
- Lista de EPIs requerendo atenção
- Últimas movimentações registradas
- Alertas visuais para situações críticas

## Requisitos do Sistema

- **Servidor Web** (Apache/Nginx)
- **PHP 7.4+** com extensões PDO e MySQL
- **MySQL 5.7+** ou **MariaDB 10.2+**

## Instalação

### 1. Configuração do Banco de Dados

Execute o script SQL `database.sql` no seu servidor MySQL:

```sql
mysql -u root -p < database.sql
```

Ou importe o arquivo através do phpMyAdmin ou outra ferramenta de sua preferência.

### 2. Configuração da Conexão

Edite o arquivo `config.php` e ajuste as configurações de conexão com o banco:

```php
private $host = 'localhost';
private $db_name = 'klarbyte_epi';
private $username = 'root';
private $password = '';
```

### 3. Upload dos Arquivos

Faça upload de todos os arquivos para o diretório do seu servidor web.

### 4. Permissões

Certifique-se de que os arquivos PHP tenham permissões adequadas de leitura.

## Primeiro Acesso

### Login Padrão
- **Email:** admin@klarbyte.com
- **Senha:** password

**Importante:** Altere a senha padrão após o primeiro login!

## Estrutura de Arquivos

```
├── index.html          # Página inicial (redireciona para login)
├── login.php           # Página de login
├── logout.php          # Script de logout
├── config.php          # Configurações e funções auxiliares
├── dashboard.php       # Dashboard principal
├── epis.php           # Gestão de EPIs
├── movimentacoes.php  # Controle de movimentações
├── usuarios.php       # Gestão de usuários
├── style.css          # Estilos CSS
└── database.sql       # Script de criação do banco
```

## Uso do Sistema

### 1. Cadastro de EPIs
1. Acesse a seção "EPIs"
2. Preencha o formulário com nome, descrição, validade, quantidade mínima e saldo inicial
3. Clique em "Cadastrar"

### 2. Movimentações de Estoque
1. Acesse a seção "Movimentações"
2. Selecione o EPI e o tipo de movimentação
3. Informe a quantidade
4. Para retiradas, preencha responsável e empresa
5. Adicione observações se necessário
6. Clique em "Registrar Movimentação"

### 3. Gestão de Usuários
1. Acesse a seção "Usuários"
2. Preencha nome, email e senha
3. Clique em "Criar Usuário"
4. Use as ações para editar ou desativar usuários

## Recursos Visuais

### Alertas de Status
- **Verde (OK):** EPI com estoque adequado e dentro da validade
- **Amarelo (Estoque Baixo):** Quantidade igual ou abaixo do mínimo
- **Vermelho (Vencido):** Produto com validade vencida

### Dashboard
- Cards com estatísticas principais
- Tabela de EPIs requerendo atenção
- Histórico das últimas movimentações
- Alertas automáticos para situações críticas

## Segurança

- Senhas são armazenadas com hash seguro (password_hash)
- Validação e sanitização de dados de entrada
- Proteção contra SQL injection através de prepared statements
- Controle de sessão para acesso às páginas

## Suporte

Para dúvidas ou problemas:
1. Verifique se todas as dependências estão instaladas
2. Confirme as configurações de conexão com o banco
3. Verifique os logs de erro do servidor web
4. Certifique-se de que o banco de dados foi criado corretamente

## Licença

Sistema desenvolvido para uso interno da Klarbyte.