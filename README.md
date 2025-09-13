# Sistema de Gestão de EPIs Klarbyte

Sistema web completo para gerenciamento de Equipamentos de Proteção Individual (EPIs), desenvolvido com HTML, CSS, JavaScript, PHP e MySQL.

## 📋 Visão Geral

O Sistema de Gestão de EPIs Klarbyte é uma solução completa para controle de equipamentos de proteção individual em empresas. O sistema permite o cadastro de EPIs, funcionários, controle de estoque, movimentações e geração de relatórios.

## 🚀 Funcionalidades Principais

### Painel Administrativo
- **Dashboard Completo**: Visão geral com estatísticas e alertas
- **Gestão de EPIs**: Cadastro, edição e controle de equipamentos
- **Gestão de Funcionários**: Cadastro e controle de usuários
- **Controle de Estoque**: Monitoramento de níveis e alertas
- **Movimentações**: Registro de entradas, retiradas, devoluções e descartes
- **Relatórios**: Geração de relatórios detalhados e análises

### Painel do Funcionário
- **Dashboard Pessoal**: Visão das próprias atividades
- **EPIs Disponíveis**: Consulta de equipamentos disponíveis
- **Retirada de EPIs**: Sistema para retirar equipamentos
- **Devolução de EPIs**: Sistema para devolver equipamentos
- **Histórico Pessoal**: Consulta do próprio histórico de movimentações

### Características Técnicas
- **Interface Responsiva**: Funciona em dispositivos móveis e desktop
- **Alertas Visuais**: Notificações para estoque baixo e vencimentos
- **Segurança**: Controle de acesso diferenciado
- **Performance**: Consultas otimizadas e índices no banco
- **Usabilidade**: Interface intuitiva e fácil de usar

## 🛠️ Tecnologias Utilizadas

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP (apenas para conexão com banco)
- **Banco de Dados**: MySQL
- **Design**: CSS puro, responsivo
- **Segurança**: Prepared statements, validações

## 📁 Estrutura do Projeto

```
KlarbyteEPI/
├── admin/                      # Painel administrativo
│   ├── index.php              # Dashboard admin
│   ├── epis.php               # Gestão de EPIs
│   ├── funcionarios.php       # Gestão de funcionários
│   ├── movimentacoes.php      # Controle de movimentações
│   ├── estoque.php            # Controle de estoque
│   └── relatorios.php         # Relatórios e análises
├── user/                       # Painel do funcionário
│   ├── index.php              # Dashboard funcionário
│   ├── epis.php               # EPIs disponíveis
│   ├── retirar.php            # Retirada de EPIs
│   ├── devolver.php           # Devolução de EPIs
│   └── movimentacoes.php      # Histórico pessoal
├── config/                     # Configurações
│   └── database.php           # Conexão com banco
├── includes/                   # Arquivos compartilhados
│   ├── header.php             # Cabeçalho comum
│   └── footer.php             # Rodapé comum
├── assets/                     # Recursos estáticos
│   ├── css/
│   │   └── style.css          # Estilos principais
│   └── js/
│       └── main.js            # JavaScript principal
├── database.sql               # Estrutura do banco
└── index.html                 # Página inicial
```

## 🗄️ Banco de Dados

### Tabelas Principais

1. **epis**: Cadastro de equipamentos
2. **funcionarios**: Cadastro de funcionários
3. **empresas**: Cadastro de empresas
4. **fornecedores**: Cadastro de fornecedores
5. **movimentacoes**: Histórico de movimentações
6. **usuarios**: Usuários do sistema

### Relacionamentos
- Funcionários pertencem a empresas
- EPIs podem ter fornecedores
- Movimentações relacionam EPIs e funcionários
- Controle de estoque automatizado

## ⚙️ Instalação e Configuração

### Pré-requisitos
- Servidor web (Apache/Nginx)
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Navegador web moderno

### Passos de Instalação

1. **Clone o repositório**:
   ```bash
   git clone https://github.com/KallebySchultz/KlarbyteEPI.git
   cd KlarbyteEPI
   ```

2. **Configure o banco de dados**:
   - Crie um banco de dados MySQL
   - Execute o arquivo `database.sql`
   - Configure as credenciais em `config/database.php`

3. **Configure o servidor web**:
   - Aponte o DocumentRoot para a pasta do projeto
   - Certifique-se que o PHP está funcionando

4. **Acesse o sistema**:
   - Abra `http://localhost/` no navegador
   - Escolha entre painel administrativo ou de funcionário

### Configuração do Banco

Edite o arquivo `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'klarbyte_epi');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
```

## 👥 Uso do Sistema

### Acesso Administrativo
- URL: `/admin/`
- Funcionalidades completas de gestão
- Controle total do sistema

### Acesso de Funcionário
- URL: `/user/`
- Consulta de EPIs disponíveis
- Retirada e devolução de equipamentos
- Consulta do histórico pessoal

### Dados Iniciais
O sistema inclui:
- Usuário administrador padrão
- Empresa exemplo
- Fornecedor exemplo

## 📊 Funcionalidades Detalhadas

### Controle de Estoque
- Monitoramento automático de níveis
- Alertas para estoque baixo
- Controle de validade
- Relatórios de vencimentos

### Movimentações
- **Entrada**: Recebimento de novos EPIs
- **Retirada**: Funcionário retira EPI
- **Devolução**: Funcionário devolve EPI
- **Descarte**: Remoção de EPIs danificados

### Relatórios
- Movimentações por período
- Estoque atual
- Funcionários e atividades
- Análise de uso de EPIs
- Relatório de vencimentos

### Alertas e Notificações
- Estoque abaixo do mínimo
- EPIs próximos ao vencimento
- EPIs vencidos
- Alta utilização de equipamentos

## 🔒 Segurança

### Medidas Implementadas
- Prepared statements para evitar SQL injection
- Validação de dados no frontend e backend
- Sanitização de entradas de usuário
- Controle de acesso por perfil

### Melhores Práticas
- Senhas devem ser hasheadas (implementação futura)
- Sessões seguras (implementação futura)
- Logs de auditoria (implementação futura)
- Backup regular do banco de dados

## 🎨 Interface e Usabilidade

### Design Responsivo
- Funciona em dispositivos móveis
- Layout adaptável
- Interface intuitiva
- Cores azul e branco (identidade Klarbyte)

### Recursos de Interface
- Tabelas filtráveis e ordenáveis
- Formulários com validação em tempo real
- Alertas visuais coloridos
- Navegação simplificada

## 🔧 Manutenção e Suporte

### Logs do Sistema
- Erros são registrados automaticamente
- Movimentações ficam registradas permanentemente
- Histórico completo de alterações

### Backup
Faça backup regular dos seguintes itens:
- Banco de dados MySQL
- Arquivos de configuração
- Logs do sistema

### Atualizações
Para atualizar o sistema:
1. Faça backup completo
2. Baixe a nova versão
3. Execute scripts de atualização do banco
4. Teste todas as funcionalidades

## 📈 Melhorias Futuras

### Funcionalidades Planejadas
- Sistema de autenticação completo
- Integração com Active Directory
- API REST para integrações
- Aplicativo móvel
- Dashboard com gráficos avançados
- Relatórios em PDF
- Sistema de notificações por email
- Controle de calibração de EPIs
- Gestão de treinamentos

### Otimizações Técnicas
- Cache de consultas
- Compressão de arquivos
- CDN para recursos estáticos
- Monitoramento de performance

## 🤝 Contribuição

Para contribuir com o projeto:

1. Faça um fork do repositório
2. Crie uma branch para sua feature
3. Implemente as mudanças
4. Teste thoroughlymente
5. Envie um pull request

### Padrões de Código
- Comente funções complexas
- Use nomes descritivos para variáveis
- Mantenha consistência no estilo
- Valide todas as entradas de usuário

## 📝 Licença

Este projeto está licenciado sob a MIT License - veja o arquivo LICENSE para detalhes.

## 📞 Suporte

Para suporte técnico ou dúvidas:
- Email: suporte@klarbyte.com
- Documentação: Wiki do projeto
- Issues: GitHub Issues

## 🏆 Créditos

Desenvolvido por **Klarbyte Sistemas** para o controle eficiente de Equipamentos de Proteção Individual.

Sistema criado com foco na segurança do trabalho e compliance com normas regulamentadoras.

---

**Sistema de Gestão de EPIs Klarbyte - Versão 1.0**
*Proteção e Controle ao seu alcance*