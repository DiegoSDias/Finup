# Autenticação, e-mail e perfil

## Política de verificação

A verificação de e-mail é opcional para acesso às funcionalidades financeiras, conforme decisão do responsável. O cadastro dispara uma notificação de verificação; a pessoa pode verificar seu endereço pelo link ou solicitar reenvio. A alteração do endereço no perfil remove o estado de verificação anterior; o novo endereço pode ser verificado por reenvio a partir do perfil. Não há bloqueio de acesso financeiro para contas não verificadas.

Os links de verificação são assinados e expiram. O reenvio é sujeito à limitação de requisições definida nas rotas. A validação do link exige que o usuário correspondente esteja autenticado.

## Envio de e-mail em desenvolvimento

O `.env.example` usa `MAIL_MAILER=log`: as mensagens geradas pelo Laravel ficam no log da aplicação, em vez de serem enviadas. Consulte o canal de log configurado no ambiente local (por padrão, `storage/logs/laravel.log`). Links de verificação e recuperação de senha funcionam como credenciais temporárias; não compartilhe logs com esses links e controle seu acesso e retenção.

Para gerar links corretos, configure `APP_URL` com a origem usada para acessar a aplicação. Ao usar Docker, `localhost` pode apontar para lugares diferentes dependendo de quem acessa o link.

Para entrega real, configure um serviço de e-mail autorizado, remetente e transporte SMTP ou outro driver disponível no Laravel, com credenciais guardadas fora do repositório. Confirme a origem HTTPS pública, a configuração do serviço e a entrega antes de depender desses e-mails. Não coloque credenciais ou links reais em testes, logs compartilhados ou documentação. Os testes usam notificações simuladas e transporte `array`; não enviam mensagens reais.

## Exclusão de conta: comportamento atual, sujeito a decisão

A exclusão exige a senha atual e remove o usuário local. As chaves estrangeiras das tabelas financeiras e das conexões Pluggy associadas estão configuradas com exclusão em cascata. Isso não equivale a uma política de retenção aprovada: os eventos de webhook, sessões, tokens de recuperação, logs, backups e dados ou consentimentos mantidos na Pluggy podem ter ciclos de vida diferentes. A exclusão local, por si só, não comprova exclusão remota nem revogação de consentimento. Qualquer mudança de retenção, limpeza ou operação remota deve ser decidida antes da implementação.
