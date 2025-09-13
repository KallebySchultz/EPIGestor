# EPIGestor - Sistema de Gestão de EPIs Klarbyte

Sistema web completo para gestão de Equipamentos de Proteção Individual (EPIs) desenvolvido em PHP com interface moderna e intuitiva.

## 📋 Sobre o Sistema

O EPIGestor é uma solução robusta para empresas que precisam controlar e gerenciar seus equipamentos de proteção individual. O sistema oferece controle completo de estoque, movimentações, usuários e relatórios, garantindo que sua empresa esteja sempre em conformidade com as normas de segurança do trabalho.

### ✨ Principais Funcionalidades

- **Dashboard Interativo**: Visão geral do sistema com estatísticas em tempo real
- **Gestão de EPIs**: Cadastro, edição e controle completo dos equipamentos
- **Controle de Estoque**: Monitoramento de quantidades mínimas e alertas automáticos
- **Movimentações**: Registro de entradas, retiradas, devoluções e descartes
- **Gestão de Usuários**: Sistema de usuários com autenticação segura
- **Relatórios**: Histórico completo de movimentações com filtros avançados
- **Interface Responsiva**: Acesso via desktop, tablet ou smartphone

## 🎯 Módulos do Sistema

### 1. Dashboard
- Estatísticas gerais do estoque
- Valor total em estoque
- Alertas de estoque baixo
- Últimas movimentações registradas

### 2. Gestão de EPIs
- Cadastro de novos equipamentos
- Edição de informações dos EPIs
- Controle de valores unitários
- Definição de quantidades mínimas
- Ativação/desativação de itens

### 3. Movimentações
- Registro de entradas de estoque
- Controle de retiradas para funcionários
- Registro de devoluções
- Controle de descartes
- Histórico com filtros de busca

### 4. Usuários
- Cadastro de usuários do sistema
- Gestão de senhas e permissões
- Ativação/desativação de contas
- Controle de acesso por username

## 🔧 Requisitos Técnicos

### Servidor Web
- **PHP**: 7.4 ou superior
- **MySQL**: 5.7 ou superior
- **Apache/Nginx**: Servidor web configurado
- **Extensões PHP necessárias**:
  - PDO MySQL
  - Session
  - Password Hash

### Navegadores Suportados
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 📦 Instalação

### 1. Preparação do Ambiente
```bash
# Clone o repositório
git clone https://github.com/KallebySchultz/EPIGestor.git

# Navegue para o diretório
cd EPIGestor
```

### 2. Configuração do Banco de Dados
```sql
-- Execute o script SQL no MySQL
mysql -u root -p < database.sql
```

### 3. Configuração da Aplicação
Edite o arquivo `config.php` com suas configurações:

```php
private $host = 'localhost';           // Servidor MySQL
private $db_name = 'klarbyte_epi';     // Nome do banco
private $username = 'root';            // Usuário MySQL
private $password = '';                // Senha MySQL
```

### 4. Configuração do Servidor Web

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.html [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

## 🚀 Primeiros Passos

### 1. Acesso Inicial
- **URL**: `http://seudominio.com`
- **Usuário**: `Kalleby Schultz`
- **Senha**: `admin123`

### 2. Configuração Inicial
1. Faça login com as credenciais padrão
2. Acesse "Usuários" e altere a senha padrão
3. Cadastre outros usuários conforme necessário
4. Comece cadastrando seus EPIs no módulo "EPIs"

### 3. Fluxo de Trabalho Recomendado
1. **Cadastrar EPIs**: Registre todos os equipamentos
2. **Entrada Inicial**: Registre estoque inicial via "Movimentações"
3. **Definir Mínimos**: Configure quantidades mínimas para alertas
4. **Operação**: Use o sistema para retiradas e devoluções diárias

## 💡 Como Usar

### Cadastrando um EPI
1. Acesse **EPIs** no menu
2. Preencha os dados do equipamento:
   - Nome do EPI
   - Descrição detalhada
   - Valor unitário
   - Quantidade mínima em estoque
   - Saldo inicial (apenas no cadastro)
3. Clique em **Cadastrar**

### Registrando Movimentações
1. Acesse **Movimentações** no menu
2. Selecione o EPI desejado
3. Escolha o tipo de movimentação:
   - **Entrada**: Nova compra ou devolução
   - **Retirada**: Entrega para funcionário
   - **Devolução**: Retorno de EPI usado
   - **Descarte**: Descarte por dano ou vencimento
4. Preencha os dados e confirme

### Monitorando o Estoque
- **Dashboard**: Visão geral com alertas
- **EPIs**: Lista completa com status de estoque
- **Relatórios**: Histórico detalhado de movimentações

## 🛡️ Segurança

- **Autenticação**: Sistema de login com senha criptografada
- **Sanitização**: Todos os dados são sanitizados antes do processamento
- **Prepared Statements**: Proteção contra SQL Injection
- **Sessões Seguras**: Controle de sessão com timeout automático

## 📊 Estrutura do Banco de Dados

### Tabelas Principais
- **usuarios**: Controle de usuários do sistema
- **epis**: Cadastro de equipamentos de proteção
- **movimentacoes**: Histórico de todas as movimentações

### Relacionamentos
- Movimentações vinculadas aos EPIs e usuários
- Controle de integridade referencial
- Índices para performance otimizada

## 🎨 Interface

O sistema possui uma interface moderna e intuitiva:
- **Design Responsivo**: Adapta-se a qualquer dispositivo
- **Cores Corporativas**: Esquema visual profissional
- **Navegação Intuitiva**: Menu claro e organizado
- **Feedback Visual**: Alertas e confirmações em tempo real

## 📈 Relatórios e Estatísticas

- **Valor Total em Estoque**: Cálculo automático do investimento
- **Alertas de Estoque Baixo**: Notificações de reposição
- **Histórico Completo**: Rastreabilidade total das movimentações
- **Filtros Avançados**: Busca por período, tipo, responsável

## 🔄 Backup e Manutenção

### Backup Recomendado
```bash
# Backup do banco de dados
mysqldump -u root -p klarbyte_epi > backup_epi_$(date +%Y%m%d).sql

# Backup dos arquivos
tar -czf backup_files_$(date +%Y%m%d).tar.gz /caminho/para/epicenter/
```

### Manutenção Periódica
- Backup semanal do banco de dados
- Limpeza de logs antigos
- Atualização de senhas periodicamente
- Revisão de usuários ativos

## 🤝 Suporte

Para dúvidas ou suporte técnico:
- **Desenvolvedor**: Kalleby Schultz
- **GitHub**: [KallebySchultz/EPIGestor](https://github.com/KallebySchultz/EPIGestor)

## 📄 Licença

Este projeto é desenvolvido para uso interno da Klarbyte. Todos os direitos reservados.

---

**EPIGestor** - Sua segurança é nossa prioridade! 🛡️