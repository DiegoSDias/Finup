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
