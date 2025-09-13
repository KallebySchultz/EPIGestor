# Sistema Simplificado de Gestão de EPIs Klarbyte

Sistema web **extremamente simplificado** para gerenciamento de Equipamentos de Proteção Individual (EPIs), desenvolvido com interface estilo planilha.

## 📋 Sobre esta Versão Simplificada

Este sistema foi **completamente simplificado** para atender a necessidade de uma gestão **sem complicações desnecessárias**. Não há múltiplos tipos de usuários, interfaces complexas ou "firulas" - apenas o essencial para uma gestão eficiente.

## 🎯 Características Principais

### ✅ Interface Estilo Planilha
- **Listas editáveis** direto na tela, como numa planilha
- **Edição inline** - clique e edite qualquer campo
- **Auto-salvamento** quando você sai do campo
- **Visual limpo** sem elementos desnecessários

### ✅ Gestão Centralizada
- **Tudo é administrado** por administradores
- **Sem distinção** de tipos de usuário
- **Acesso único** com login simples
- **Controle total** de todas as funcionalidades

### ✅ Funcionalidades Essenciais
- **Gestão de EPIs**: Lista editável de equipamentos
- **Gestão de Funcionários**: Lista editável de pessoal  
- **Movimentações**: Retiradas, devoluções e ajustes
- **Dashboard**: Visão geral com informações básicas

## 🚀 Como Usar

### 1. Acesso ao Sistema
- Acesse através do arquivo `index.html`
- Clique em "Acessar Sistema"
- Use: **Usuário: `admin`** | **Senha: `admin123`**

### 2. Gestão de EPIs
- Vá em "EPIs" no menu
- **Adicione** novos EPIs no formulário do topo
- **Edite** qualquer campo clicando diretamente na tabela
- **Remove** EPIs com o botão 🗑️

### 3. Gestão de Funcionários  
- Vá em "Funcionários" no menu
- **Adicione** funcionários no formulário do topo
- **Edite** dados clicando nos campos da tabela
- Sistema auto-formata telefones e valida emails

### 4. Movimentações
- Vá em "Movimentações" no menu
- **Registre retiradas** quando funcionário pegar EPI
- **Registre devoluções** quando devolver
- **Faça ajustes** para corrigir estoque manualmente

## 🛠️ Instalação

### Requisitos Mínimos
- Servidor web (Apache/Nginx)
- PHP 7.4+
- MySQL 5.7+

### Passos de Instalação

1. **Extrair arquivos** na pasta do servidor web

2. **Criar banco de dados**:
   ```sql
   CREATE DATABASE klarbyte_epi_simple;
   ```

3. **Importar estrutura**:
   ```bash
   mysql -u root -p klarbyte_epi_simple < database_simplified.sql
   ```

4. **Configurar conexão** em `config/database_simple.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'klarbyte_epi_simple');  
   define('DB_USER', 'seu_usuario');
   define('DB_PASS', 'sua_senha');
   ```

5. **Acessar** `http://localhost/login.php`

## 📁 Estrutura Simplificada

```
KlarbyteEPI/
├── index.html                    # Página inicial simplificada
├── login.php                     # Login único e simples
├── database_simplified.sql       # Estrutura do banco simplificada
├── config/
│   ├── database_simple.php       # Conexão simplificada
│   └── auth.php                   # Autenticação básica
├── admin/
│   ├── dashboard.php              # Dashboard principal
│   ├── epis_simple.php            # Gestão de EPIs (estilo planilha)
│   ├── funcionarios_simple.php   # Gestão de funcionários
│   └── movimentacoes_simple.php  # Gestão de movimentações
└── assets/
    └── css/
        └── style_simple.css       # CSS limpo e simples
```

## 🗄️ Banco de Dados Simplificado

### Tabelas Básicas
1. **admin_login**: Login dos administradores
2. **epis**: EPIs com campos essenciais
3. **funcionarios**: Funcionários básicos  
4. **movimentacoes**: Histórico simples

### Sem Complexidade
- ❌ Múltiplas empresas
- ❌ Fornecedores complexos
- ❌ Tipos de usuário
- ❌ Permissões complicadas
- ❌ Relatórios avançados

## 🎨 Interface

### Design Limpo
- **Cores**: Azul e branco
- **Estilo**: Planilha eletrônica
- **Navegação**: Menu horizontal simples
- **Formulários**: Inline editing

### Funcionalidades da Interface
- **Edição direta** nas tabelas
- **Auto-salvamento** 
- **Indicadores visuais** para estoque baixo/esgotado
- **Formulários rápidos** para adição
- **Confirmações** para exclusões

## 🔧 Características Técnicas

### Simplicidade
- **PHP puro** para backend
- **JavaScript mínimo** apenas para UX
- **CSS próprio** sem frameworks externos
- **Banco MySQL** com estrutura básica

### Performance
- **Consultas otimizadas**
- **Índices básicos**
- **Código enxuto**
- **Sem dependências** externas

## 📝 Uso Diário

### Workflow Típico
1. **Login** no sistema
2. **Verificar dashboard** para visão geral
3. **Gerenciar EPIs** conforme necessário
4. **Registrar movimentações** quando necessário
5. **Manter funcionários** atualizados

### Dicas de Uso
- **Clique e edite** qualquer campo nas tabelas
- **Use Tab** para navegar entre campos
- **Enter** salva automaticamente
- **Cores** indicam status (estoque baixo, etc.)

## 🎯 Diferenças da Versão Anterior

| Anterior (Complexo) | Atual (Simplificado) |
|-------------------|---------------------|
| Múltiplos usuários | Apenas administradores |
| Painéis separados | Interface única |
| Formulários complexos | Edição inline |
| Muitas tabelas | Tabelas essenciais |
| Relatórios avançados | Visão básica |
| Configurações múltiplas | Configuração simples |

## 🤝 Suporte

Para dúvidas ou problemas:
- Verifique se o banco está configurado corretamente
- Confirme as permissões dos arquivos PHP
- Use as credenciais padrão: `admin` / `admin123`

---

**Sistema de Gestão de EPIs Klarbyte - Versão Simplificada**  
*Simplicidade e funcionalidade em primeiro lugar*