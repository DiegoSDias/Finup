# Grana sob Controle

Aplicação Laravel para controle de receitas e despesas manuais, com integração opcional de Open Finance pela Pluggy. As informações bancárias são buscadas pela Pluggy e persistidas no banco local antes de serem exibidas nos relatórios.

## Executar com Docker

O ambiente de desenvolvimento conta com PHP 8.3, Nginx, MySQL 8.4 e um worker para a fila de sincronização. O banco é mantido no volume Docker `mysql-data`.

1. Crie o arquivo de ambiente, caso ainda não exista: `Copy-Item .env.example .env`.
2. Defina uma chave da aplicação em `APP_KEY` no `.env` (ou use `php artisan key:generate` uma única vez antes de subir os contêineres).
3. Inicie o ambiente: `docker compose up --build`.

A aplicação estará disponível em `http://localhost:8080`. As migrations são aplicadas ao iniciar o serviço `app`, e o serviço `queue` processa os jobs em segundo plano. O MySQL também fica acessível no host pela porta `3309` com banco, usuário e senha `finup`.

O worker usa tempo limite padrão de 240 segundos, alinhado aos jobs da Pluggy. O `retry_after` da fila de banco deve permanecer maior que esse tempo (o padrão é 300 segundos). O código é incorporado às imagens: após alterá-lo, execute `docker compose up --build` para reconstruí-las. O banco Docker é separado do banco local; apenas seus dados no volume `mysql-data` são persistidos entre recriações dos contêineres. Arquivos e logs armazenados dentro dos contêineres não possuem volume persistente configurado.

Para interromper os serviços, use `docker compose down`. Para remover também os dados locais do banco, use `docker compose down --volumes`.

## Deploy na Vercel

O deploy usa o [Container Runtime](https://vercel.com/docs/functions/runtimes/container) da Vercel, atualmente em beta. O arquivo `Dockerfile.vercel` tem prioridade sobre a detecção do Vite e monta uma imagem com PHP 8.3, FrankenPHP e os assets compilados. O Dockerfile e o Compose usados no desenvolvimento continuam independentes.

Os arquivos de deploy têm estas responsabilidades:

| Arquivo | Responsabilidade |
| --- | --- |
| `Dockerfile.vercel` | Instalar as dependências de produção, compilar o frontend e montar a imagem da aplicação. |
| `docker/vercel/Caddyfile` | Servir a pasta `public` por HTTP na porta informada em `PORT`, com padrão 80. A Vercel fornece o HTTPS público. |
| `docker/vercel/entrypoint.sh` | Preparar os diretórios temporários usados pelo Laravel e iniciar o comando da imagem. |
| `vercel.json` | Manter um worker da fila como Vercel Daemon, usando a mesma imagem e um comando diferente. |

### Banco e variáveis de ambiente

É necessário um **MySQL externo e persistente**, acessível pela Vercel. O serviço `db` do Compose e o endereço `localhost` não ficam disponíveis no deploy. Sessões, cache e fila também usam esse banco. Configure TLS conforme as instruções do provedor; se ele exigir um certificado CA, `MYSQL_ATTR_SSL_CA` deve apontar para o arquivo disponibilizado no contêiner.

Em **Environment Variables**, cadastre os valores reais para **Production**:

```env
APP_NAME=Finup
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-projeto.vercel.app
APP_KEY=base64:SUBSTITUA_PELA_CHAVE_DA_APLICACAO

DB_CONNECTION=mysql
DB_HOST=host-do-mysql
DB_PORT=3306
DB_DATABASE=finup
DB_USERNAME=usuario-do-mysql
DB_PASSWORD=senha-do-mysql

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=300
LOG_CHANNEL=stderr

PLUGGY_CLIENT_ID=seu-client-id
PLUGGY_CLIENT_SECRET=seu-client-secret
PLUGGY_BASE_URL=https://api.pluggy.ai
PLUGGY_WEBHOOK_SECRET=seu-segredo-longo
PLUGGY_WEBHOOK_URL=https://seu-projeto.vercel.app/webhooks/pluggy?token=seu-segredo-longo
```

Use a mesma `APP_KEY` em todos os deploys desse ambiente. Se for uma instalação nova, gere a chave uma única vez com `php artisan key:generate --show` e salve o resultado no painel. Deixe `SESSION_DOMAIN` sem configuração para o cookie usar o domínio acessado. As variáveis `VITE_*` são públicas no frontend e não devem conter credenciais.

### Preparar o banco e publicar

1. Construa a imagem: `docker build --file Dockerfile.vercel --tag finup-vercel .`.
2. Para aplicar as migrations, coloque as variáveis reais em um arquivo local `.env.production` (ignorado pelo Git) e execute `docker run --rm --env-file .env.production finup-vercel php artisan migrate --force`. Faça isso como uma etapa controlada antes de liberar tráfego. As migrations não são executadas automaticamente a cada inicialização.
3. Envie os arquivos para a branch do GitHub que será usada no deploy.
4. Importe o repositório na Vercel com **Root Directory** `./` e **Application Preset** **Container**. Remova eventuais comandos personalizados de Build, Install e Output Directory do Vite; a imagem faz essas etapas.
5. Cadastre as variáveis de ambiente, publique e confira `/up`, o login e os logs do worker em **Daemons**. Se a Vercel atribuir uma URL diferente, atualize `APP_URL` e `PLUGGY_WEBHOOK_URL` e faça um novo deploy.

O daemon `queue` possui uma instância com 1 GiB de memória em `iad1`. Ajuste a região em `vercel.json` e a região da função no painel para ficarem próximas ao banco contratado. O tempo de encerramento de 300 segundos permite concluir os jobs que têm limite de 240 segundos. A configuração segue a [referência dos Daemons](https://vercel.com/docs/daemons/config-reference).

Daemons estão em beta e executam apenas em **Production**. Para Preview, use um banco e credenciais separados; jobs enfileirados nesse banco precisarão de um worker próprio. O worker de produção permanece ativo e seu consumo deve ser acompanhado no painel.

A imagem grava views e caches temporários em `/tmp` e envia logs para `stderr`. Esses arquivos não são persistentes. Uploads que precisem sobreviver a um novo contêiner devem usar armazenamento externo. O Laravel confia nos cabeçalhos do proxy apenas quando `DEPLOY_TARGET=vercel`, definido pela imagem de deploy.

## O que a integração faz

- Abre o Pluggy Connect sem expor `PLUGGY_CLIENT_SECRET` ao navegador.
- Associa cada Item Pluggy ao usuário autenticado da aplicação.
- Sincroniza contas, cartões e transações de forma assíncrona.
- Mantém cópias locais idempotentes: sincronizar novamente atualiza, não duplica.
- Soma apenas receitas e despesas economicamente válidas; ignora pendências, transferências internas e pagamento de fatura já representada pelas compras do cartão.
- Registra estornos e cashback como reversão de despesa.
- Processa webhooks rapidamente, com deduplicação por `eventId` e processamento em fila.

## Configuração

Copie o ambiente e preencha somente no servidor:

```env
PLUGGY_CLIENT_ID=
PLUGGY_CLIENT_SECRET=
PLUGGY_BASE_URL=https://api.pluggy.ai
PLUGGY_WEBHOOK_SECRET=gere-um-segredo-longo
PLUGGY_WEBHOOK_URL=https://seu-dominio.com/webhooks/pluggy?token=gere-um-segredo-longo
PLUGGY_API_KEY_CACHE_MINUTES=105
```

Nunca coloque credenciais da Pluggy em JavaScript, repositório ou logs. A chave de API temporária é obtida no backend e fica em cache pelo tempo configurado.

Execute as migrations e mantenha um worker ativo para os jobs de sincronização e webhooks:

```bash
php artisan migrate
php artisan queue:work --tries=3
```

Em desenvolvimento, `composer run dev` já inicia o worker. Para receber webhooks localmente, exponha uma URL HTTPS pública (por exemplo, com um túnel) e atualize `PLUGGY_WEBHOOK_URL`.

## Fluxo de uso

1. Entre no painel e escolha **Conectar banco**.
2. Conclua o fluxo do Pluggy Connect.
3. O navegador envia somente o `itemId` à rota autenticada `POST /pluggy/items`.
4. A aplicação valida a posse do Item, associa-o ao usuário atual e coloca a sincronização na fila.
5. Acesse **Conexões** para acompanhar contas locais, última sincronização, solicitar atualização ou remover a conexão.

Para um Item criado no MeuPluggy, use o campo **Vincular conexão existente** no painel. Esse caminho é destinado ao Item da própria pessoa; um Item já associado a outro usuário local é recusado.

## Webhook

Configure na Pluggy a mesma URL de `PLUGGY_WEBHOOK_URL`. A rota pública é `POST /webhooks/pluggy` e:

- aceita apenas JSON;
- valida o segredo opcional via `?token=`;
- salva e deduplica o evento pelo `eventId`;
- retorna `2xx` sem esperar a sincronização;
- envia a atualização de Item ou transações para a fila.

Para conferir problemas, use `php artisan pail` ou os logs da aplicação. O sistema registra IDs técnicos e status, não credenciais ou dados bancários sensíveis.

## Modelo local

- `pluggy_items`: conexão por usuário e estado de sincronização;
- `pluggy_accounts`: contas/cartões e saldos persistidos localmente;
- `pluggy_transactions`: transações normalizadas, classificação, categoria e payload técnico;
- `pluggy_webhook_events`: eventos recebidos, deduplicação e status de processamento.

Números de documentos e contas usados para detectar transferências internas ficam apenas como HMAC; a interface mostra, no máximo, os quatro últimos dígitos. Remover uma conexão a desativa junto com seus lançamentos importados, preservando todos os lançamentos manuais.

## Testes

```bash
php artisan test
```

Os testes cobrem classificação de entradas, despesas, estornos e pagamento de fatura, idempotência de importação, transferência interna por referência PIX e deduplicação de webhook. O ambiente de teste usa SQLite; habilite a extensão `pdo_sqlite` no PHP de linha de comando caso ela ainda não esteja disponível.

## Referências Pluggy

- [Autenticação e Connect Token](https://docs.pluggy.ai/pt/docs/authentication)
- [Transações paginadas](https://v2.docs.pluggy.ai/en/reference/transactions-list-by-cursor)
- [Webhooks](https://docs.pluggy.ai/pt/reference/webhooks)
