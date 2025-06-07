# API Bank Application

## Descrição

Este projeto consiste em uma API PHP para operações bancárias, com balanceamento de carga via Nginx e múltiplos
containers PHP para alta disponibilidade.

---

## Como rodar o sistema

1. **Pré-requisitos:**

    - Docker e Docker Compose instalados na sua máquina.
    - Porta 8080 livre para uso no host.


2. **Executar os containers:**

   No terminal, na pasta do projeto, rode:

   ```bash
   docker-compose up --build
   ```

   Isso irá:

    - Construir as imagens PHP (`php1` e `php2`).
    - Subir o container SQL Server.
    - Subir o Nginx com balanceamento para os containers PHP.
    - Criar as tabelas (Conta e Transacoes) e inserir contas fictícias no banco de dados.


3. **Acessar a API:**

   A API estará disponível em:

   ```
   http://localhost:8080/
   ```

   Exemplo de endpoint para listar contas:

   ```
   http://localhost:8080/api/contas
   ```

   Exemplo de endpoint para listar transações:

   ```
   http://localhost:8080/api/transacoes
   ```

   Exemplo de endpoint para efetuar transferência:

   ```
   http://localhost:8080/api/transferencia
   ```

---

## Endpoints principais

- `/api/contas` - Retorna lista de contas.
- `/api/transacoes` - Retorna lista de transações.
- `/api/transferencia` - Realiza transferência entre contas via POST.

---

## Como simular os testes de carga

1. Certifique-se que o sistema está rodando (conforme passo anterior).

2. Execute o script PHP de stress test que realiza requisições concorrentes para transferências:

   ```bash
   php stress-test.php
   ```

   O script:

    - Consulta as contas disponíveis via API.
    - Envia múltiplas requisições concorrentes de transferência entre contas usando cURL multi-handle.
    - Imprime as respostas para validação.

### Dicas extras

- Se estiver usando Windows, garanta que o PHP esteja disponível no PATH do sistema para que o comando `php` funcione no
  terminal.
- Se o balanceador não estiver acessível via `127.0.0.1` (por exemplo, se estiver usando VM, Docker Toolbox, ou outro
  ambiente), ajuste o endereço IP no arquivo `stress-test.php` para o IP correto.

---
---

## Estrutura de Arquivos

- `.env` — Variáveis de ambiente
- `config.php` — Configurações do banco de dados
- `docker-compose.yml` — Configuração dos containers Docker
- `Dockerfile` — Imagem da aplicação PHP
- `nginx.conf` — Configuração do servidor Nginx
- `api/` — Código da API
- `scripts/` — Scripts auxiliares
- `index.php` — Ponto de entrada da aplicação
- `stress-test.php` — Script para teste de carga

---

## 🗃️ Banco de dados

- **SGBD**: SQL Server 2022
- **Usuário**: `sa`
- **Senha**: `YourStrong!Passw0rd`
- **Banco criado automaticamente**: `master`
- **Tabelas criadas**:
    - `CONTAS`
    - `TRANSACOES`
- **Dados inseridos automaticamente**: 150 contas com dados fictícios

---

## Configuração das Chaves do Banco de Dados

As credenciais para conexão com o banco de dados SQL Server estão configuradas nos seguintes arquivos:

### 1. Variável de ambiente no `.env`

```env
SA_PASSWORD=YourStrong!Passw0rd
```

Essa variável é usada para definir a senha do usuário `sa` no container do SQL Server dentro do ambiente Docker.

### 2. Configuração em `config.php`

O arquivo `config.php` contém as credenciais para a conexão com o banco de dados usadas pela aplicação PHP:

```php
<?php

return [
    'db_host' => 'sqlserver',           
    'db_name' => 'master',               
    'db_user' => 'sa',                   
    'db_pass' => 'YourStrong!Passw0rd', 
    'db_driver' => 'sqlsrv',             
];
```

### 3. Variável no `docker-compose.yml`

No arquivo `docker-compose.yml`, a variável de ambiente `SA_PASSWORD` é definida para configurar o container do SQL
Server, por exemplo:

```yaml
services:
  sqlserver:
  image: mcr.microsoft.com/mssql/server:2022-latest
  environment:
    - SA_PASSWORD: "${SA_PASSWORD}"
    - ACCEPT_EULA: "Y"
    ...
```

---

## 🛡️ Estratégia para evitar *deadlocks*

A aplicação foi projetada com cuidados específicos para evitar *deadlocks* — situações em que duas ou mais transações esperam indefinidamente por recursos bloqueados entre si, gerando travamentos no sistema. As estratégias utilizadas são:

- **🔒 Ordem consistente de bloqueio:**  
  Sempre que duas contas precisam ser acessadas para uma transferência, a aplicação bloqueia essas contas seguindo uma ordem fixa, baseada no ID da conta. Isso elimina a possibilidade de *deadlocks circulares*, onde cada processo espera pelo recurso do outro.

- **🔁 Transações atômicas e indivisíveis:**  
  As operações de débito e crédito são executadas dentro de uma única transação SQL. Isso garante que ambas as ações sejam concluídas juntas (ou nenhuma delas), mantendo a consistência dos dados mesmo em casos de falhas.

- **⏱️ Timeouts e re-tentativas automáticas:**  
  Se uma transação demorar demais por conta de bloqueios concorrentes, ela é automaticamente abortada e tentada novamente. Isso evita travamentos permanentes e garante que a operação seja eventualmente concluída com sucesso.

---

## ⚙️ Arquitetura escalável e resiliente

A aplicação foi construída com foco em escalabilidade horizontal, tolerância a falhas e facilidade de manutenção. Os principais elementos arquiteturais incluem:

- **🌐 Balanceador de carga com Nginx:**  
  O Nginx distribui automaticamente as requisições entre múltiplos containers PHP (`php1`, `php2`, etc.), garantindo melhor desempenho e suporte a um grande número de acessos simultâneos.

- **🧱 Containers PHP independentes e replicáveis:**  
  Cada instância do backend roda em um container Docker isolado. Isso permite escalar horizontalmente o sistema com facilidade, apenas adicionando novos containers à infraestrutura.

- **🗃️ Banco de dados único e confiável (SQL Server):**  
  Um único banco de dados centralizado garante a integridade e persistência das informações. Isso evita divergência entre réplicas e simplifica o controle transacional.

- **📡 Comunicação via API REST:**  
  A API utiliza o padrão HTTP REST para troca de dados. Isso torna a aplicação modular, facilmente integrável com outros serviços e apta a evoluir para uma arquitetura de microserviços no futuro.

- **💾 Volumes Docker para persistência:**  
  Os dados do banco de dados são armazenados em volumes persistentes, que não se perdem mesmo se os containers forem destruídos ou reiniciados. Isso garante a durabilidade e segurança das informações.

---
