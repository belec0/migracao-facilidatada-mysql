# 🧩 Sistema de Migração de Banco de Dados - PHP

Este projeto é uma interface web para gerenciar a **migração de dados e estrutura** entre três ambientes de banco de dados MySQL:

- **Local → Homologação**
- **Homologação → Produção**
- **Produção → Local**

O sistema compara, sincroniza e transfere tabelas com total controle de alterações e integridade.

---

## 🚀 Funcionalidades

- 🔍 **Verificar diferenças** entre bancos
- 🔄 **Sincronização de estrutura** de tabelas (add/remoção/modificação de colunas)
- 💾 **Migração de dados seletiva**, apenas de tabelas com alteração
- 🔐 Acesso restrito por login e senha
- 💬 Relatórios visuais com `SweetAlert` após migração
- 📊 Barra de progresso animada durante a execução

---

## ⚙️ Estrutura do Projeto

```
📁 migrador/
├── painel.php               # Painel com interface de controle e visualização
├── conexao.php              # Conexão com os 3 bancos usando variáveis do .env
├── .env                     # Arquivo com variáveis sensíveis (host, senha, banco)
├── verificar_todos.php      # Compara todos os bancos
├── migrar_homolog_iniciar.php
├── migrar_homolog_tabela.php
├── migrar_producao_iniciar.php
├── migrar_producao_tabela.php
├── migrar_local_iniciar.php
├── migrar_local_tabela.php
```

---

## 🔐 Configuração de Ambiente

O sistema usa um **arquivo `.env`** para gerenciar dados sensíveis como senhas, usuários e nomes dos bancos.

### 📁 Exemplo de `.env`

```env
# Banco Local
DB_LOCAL_HOST=localhost
DB_LOCAL_USER=root
DB_LOCAL_PASS=
DB_LOCAL_NAME=banco

# Banco Homologação
DB_HML_HOST=localhost
DB_HML_USER=root
DB_HML_PASS=
DB_HML_NAME=banco_hml

# Banco Produção
DB_PRD_HOST=localhost
DB_PRD_USER=root
DB_PRD_PASS=
DB_PRD_NAME=banco_prd

# Senha para operações de migração
MIGRATION_PASSWORD=senha123
```

> ⚠️ **Nunca envie esse arquivo para repositórios públicos!**

---

## 🛠️ Como alterar as variáveis de acesso

1. **Abra o arquivo `.env` na raiz do projeto**
2. **Altere os valores desejados**, como:

```env
DB_LOCAL_USER=novo_usuario
DB_LOCAL_PASS=minha_senha123
```

3. **Salve o arquivo**
4. O sistema lerá automaticamente as variáveis em `conexao.php` sem precisar de Composer ou bibliotecas externas.

---

## ✅ Requisitos

- PHP 7.4 ou superior
- Extensão MySQLi habilitada
- Servidor Apache ou Nginx (XAMPP, Laragon, etc.)

---

## 📌 Observações

- A migração **não sobrescreve tabelas que já estejam idênticas**.
- A comparação é feita por:
  - Presença da tabela
  - Colunas adicionadas/removidas
  - Tipos, `NULL`, e `DEFAULT` das colunas
- A migração **Produção → Local** sobrescreve **somente tabelas alteradas**.