<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>API Bank Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 2rem;
            background-color: #f8f9fa;
            color: #212529;
        }

        h1, h2, h3 {
            color: #0d6efd;
        }

        code, pre {
            background-color: #e9ecef;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: monospace;
        }

        pre {
            padding: 1rem;
            overflow-x: auto;
        }

        section {
            margin-bottom: 2rem;
        }

        hr {
            border: 0;
            border-top: 1px solid #dee2e6;
            margin: 2rem 0;
        }
    </style>
</head>
<body>
<h1>API Bank Application</h1>

<section>
    <h2>Descrição</h2>
    <p>Este projeto consiste em uma API PHP para operações bancárias, com balanceamento de carga via Nginx e múltiplos
        containers PHP para alta disponibilidade.</p>
</section>

<hr>

<section>
    <h2>Como rodar o sistema</h2>
    <ol>
        <li>
            <strong>Pré-requisitos:</strong>
            <ul>
                <li>Docker e Docker Compose instalados na sua máquina.</li>
                <li>Porta 8080 livre para uso no host.</li>
            </ul>
        </li>
        <li>
            <strong>Executar os containers:</strong><br>
            No terminal, na pasta do projeto, rode:
            <pre><code>docker-compose up --build</code></pre>
            <p>Isso irá:</p>
            <ul>
                <li>Construir as imagens PHP (<code>php1</code> e <code>php2</code>).</li>
                <li>Subir o container SQL Server.</li>
                <li>Subir o Nginx com balanceamento para os containers PHP.</li>
                <li>Criar as tabelas <code>Conta</code> e <code>Transacoes</code> e inserir contas fictícias no banco de
                    dados.
                </li>
            </ul>
        </li>
        <li>
            <strong>Acessar a API:</strong><br>
            A API estará disponível em:
            <pre><code>http://localhost:8080/</code></pre>
            <p>Exemplos de endpoints:</p>
            <ul>
                <li><code>/api/contas</code> – Lista contas</li>
                <li><code>/api/transacoes</code> – Lista transações</li>
                <li><code>/api/transferencia</code> – Efetua transferência via POST</li>
            </ul>
        </li>
    </ol>
</section>

<hr>

<section>
    <h2>Como simular os testes de carga</h2>
    <ol>
        <li>Certifique-se que o sistema está rodando.</li>
        <li>Execute o script PHP de stress test:
            <pre><code>php stress-test.php</code></pre>
            <p>O script:</p>
            <ul>
                <li>Consulta contas via API.</li>
                <li>Envia múltiplas requisições de transferência com cURL multi-handle.</li>
                <li>Imprime as respostas para validação.</li>
            </ul>
        </li>
    </ol>
    <p><strong>Dicas extras:</strong></p>
    <ul>
        <li>Garanta que o PHP esteja no PATH (no Windows).</li>
        <li>Se necessário, ajuste o IP no <code>stress-test.php</code>.</li>
    </ul>
</section>

<hr>

<section>
    <h2>Estrutura de Arquivos</h2>
    <ul>
        <li><code>.env</code> — Variáveis de ambiente</li>
        <li><code>config.php</code> — Configurações do banco de dados</li>
        <li><code>docker-compose.yml</code> — Configuração dos containers Docker</li>
        <li><code>Dockerfile</code> — Imagem da aplicação PHP</li>
        <li><code>nginx.conf</code> — Configuração do Nginx</li>
        <li><code>api/</code> — Código da API</li>
        <li><code>scripts/</code> — Scripts auxiliares</li>
        <li><code>index.php</code> — Entrada da aplicação</li>
        <li><code>stress-test.php</code> — Script de carga</li>
    </ul>
</section>

<hr>

<section>
    <h2>Banco de dados</h2>
    <ul>
        <li><strong>SGBD:</strong> SQL Server 2022</li>
        <li><strong>Usuário:</strong> <code>sa</code></li>
        <li><strong>Senha:</strong> <code>YourStrong!Passw0rd</code></li>
        <li><strong>Banco criado:</strong> <code>master</code></li>
        <li><strong>Tabelas:</strong> <code>CONTAS</code>, <code>TRANSACOES</code></li>
        <li><strong>Dados:</strong> 150 contas fictícias</li>
    </ul>
</section>

<hr>

<section>
    <h2>Configuração das Chaves do Banco de Dados</h2>
    <h3>1. `.env`</h3>
    <pre><code>SA_PASSWORD=YourStrong!Passw0rd</code></pre>

    <h3>2. `config.php`</h3>
    <pre><code>&lt;?php
return [
    'db_host' => 'sqlserver',
    'db_name' => 'master',
    'db_user' => 'sa',
    'db_pass' => 'YourStrong!Passw0rd',
    'db_driver' => 'sqlsrv',
];</code></pre>

    <h3>3. `docker-compose.yml`</h3>
    <pre><code>services:
  sqlserver:
    image: mcr.microsoft.com/mssql/server:2022-latest
    environment:
      - SA_PASSWORD: "${SA_PASSWORD}"
      - ACCEPT_EULA: "Y"</code></pre>
</section>

<hr>

<section>
    <h2>🛡️ Estratégia para evitar deadlocks</h2>
    <ul>
        <li><strong>Ordem consistente de bloqueio:</strong> bloqueio sempre na mesma ordem pelo ID da conta.</li>
        <li><strong>Transações atômicas:</strong> débito e crédito na mesma transação SQL.</li>
        <li><strong>Timeouts e re-tentativas:</strong> transações são reexecutadas em caso de falha por bloqueio.</li>
    </ul>
</section>

<hr>

<section>
    <h2>⚙️ Arquitetura escalável e resiliente</h2>
    <ul>
        <li><strong>Balanceador Nginx:</strong> distribui as requisições entre containers PHP.</li>
        <li><strong>Containers PHP independentes:</strong> fáceis de replicar e escalar.</li>
        <li><strong>Banco de dados central:</strong> garante integridade.</li>
        <li><strong>API REST:</strong> arquitetura modular e integrável.</li>
        <li><strong>Volumes Docker:</strong> dados persistentes mesmo com reinício dos containers.</li>
    </ul>
</section>
</body>
</html>
