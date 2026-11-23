# Testes e contrato do webhook

## Ambiente de testes

Execute `php artisan test` ou `php vendor/bin/phpunit` com as dependências de desenvolvimento e a extensão `pdo_sqlite` instaladas. O PHPUnit define o ambiente de teste e o SQLite em memória, mas variáveis herdadas do sistema ou configuração em cache podem prevalecer. A classe base verifica a configuração efetiva antes da preparação do banco e interrompe a execução se ela não for segura; conexões de banco alternativas não ficam disponíveis nos testes. Se o bloqueio ocorrer, confira as variáveis de ambiente e o cache de configuração antes de alterá-los; o cache pode ser usado pela aplicação em execução.

O cliente HTTP do Laravel rejeita requisições externas que não tenham resposta simulada. Testes que precisem de HTTP devem usar `Http::fake()` ou simular o serviço. A proteção não intercepta outros clientes de rede ou código que altere explicitamente a configuração em tempo de execução. Não executar testes reais contra a Pluggy sem autorização específica. Fila, e-mail, sessão e cache usam configurações de teste; testes que verificam enfileiramento devem usar `Queue::fake()`.

## Página inicial

`GET /` exige autenticação: visitantes são redirecionados à tela de login; usuários autenticados recebem o dashboard com HTTP 200.

## `POST /webhooks/pluggy`

- O endpoint aceita JSON e exige o segredo configurado na aplicação, enviado pelo cabeçalho `X-Pluggy-Webhook-Secret`. Por compatibilidade, o parâmetro `token` na URL também é aceito por padrão; para desativá-lo, configure `PLUGGY_WEBHOOK_ALLOW_QUERY_TOKEN=false` após migrar todas as entregas para cabeçalho.
- Para evento válido e autenticado, o contrato atual é **HTTP 200 sem corpo**, inclusive para uma entrega repetida. O evento é persistido e o job é enfileirado na mesma transação. O 200 não significa que o processamento terminou ou teve sucesso; o driver `sync` é recusado.
- Eventos são identificados por `eventId`; uma repetição não cria outro registro nem agenda outro job. O mesmo ID com outro tipo ou Item é recusado com 409.
- Conteúdo que não seja JSON recebe 415; segredo ausente, incorreto ou não configurado recebe 401; campos obrigatórios inválidos recebem 400. Conflito de proprietário também recebe 401.
- A documentação da Pluggy aceita respostas 2xx dentro do prazo estabelecido para confirmar o recebimento: https://docs.pluggy.ai/en/docs/developer-tools/webhooks-ref. Assim, 200 é uma escolha do contrato deste projeto, e não uma exigência específica de 200 pela Pluggy.

O processamento consulta o estado atual da Pluggy, evitando aplicar diretamente eventos de Item atrasados. Exclusões de transações são conciliadas apenas para a conexão local correspondente, quando o Item está atualizado com sucesso e o endpoint autenticado confirma que cada transação não existe; falhas interrompem e permitem repetição. Eventos desconhecidos não modificam dados. Um Item novo só é vinculado após confirmação do proprietário na Pluggy. O recibo salva somente campos necessários, não o payload completo.

Para garantir a gravação atômica do evento e do job, este endpoint exige fila `database` usando a mesma conexão de banco da aplicação; configurações `sync` ou filas externas recebem 503 e precisam de uma arquitetura transacional alternativa antes de serem usadas. O worker deve estar ativo, e todos os processos devem compartilhar o banco de cache usado pelos bloqueios. O job tem timeout de 240 segundos; o `retry_after` precisa ser maior, assim como a visibilidade da fila, quando aplicável. Uma entrega 200 não confirma conclusão do job. Falhas de processamento ficam registradas genericamente e são repetidas conforme as tentativas da fila; inspecione tarefas falhas antes de reprocessá-las. Eventos já existentes de versões anteriores com job perdido podem exigir conciliação operacional específica.

## Configuração e segurança

A Pluggy exige uma URL HTTPS acessível publicamente. Para ambiente local é necessário um túnel HTTPS confiável; não exponha uma instância local com dados reais apenas para testes. Segredos em URLs podem aparecer em logs, histórico e intermediários. Prefira configurar o cabeçalho de autenticação na API de cadastro de webhook da Pluggy e desabilitar o token por URL após confirmar que todos os remetentes usam o cabeçalho. URLs de webhook por Item/Connect Token não fornecem necessariamente esse cabeçalho: planeje a migração dessas assinaturas antes de desligar o modo legado, ou os eventos serão recusados. Nunca registre segredos em exemplos, logs, capturas ou documentação compartilhada.

O webhook usa um segredo compartilhado, não uma assinatura criptográfica do corpo nem uma identificação exclusiva de evento baseada em assinatura. Proteja o segredo e a infraestrutura HTTPS, e faça rotação se houver suspeita de exposição. A API da Pluggy pode devolver 400/401/403/404/405 sem repetir a entrega; falhas temporárias de infraestrutura devem retornar 503. A Pluggy recomenda responder em até 10 segundos; monitore isso no ambiente real.

O teste do retorno e do agendamento usa banco em memória e fila de teste; ele não comprova entrega real, tempo de resposta em produção nem conclusão da sincronização. A autenticação por cabeçalho requer configuração externa do webhook, que não é alterada automaticamente pelo FINUP. Referência: https://docs.pluggy.ai/en/reference/webhooks.
