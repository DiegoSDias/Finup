# Despesas recorrentes

Uma recorrência representa uma despesa repetida, e não um parcelamento. Cada ocorrência é gravada como uma despesa própria, vinculada à sua série por `recurring_expense_id`.

## Regras implementadas

- Periodicidades: mensal, trimestral, semestral e anual.
- A série tem primeira ocorrência, valor, categoria, banco, forma de pagamento e término opcional.
- O calendário é calculado a partir da data inicial. Uma série iniciada em 31/01 gera 28/02 (ou 29/02) e volta ao dia 31 quando possível.
- A criação preenche os próximos 12 meses. Ao abrir uma visão mensal ou anual, o sistema completa de forma idempotente as ocorrências daquele período.
- Editar somente uma ocorrência cria uma exceção permanente. Excluir somente uma ocorrência a marca como ignorada, para que não seja recriada.
- Editar “esta e as próximas” altera os dados financeiros e descritivos da série a partir da ocorrência escolhida. Exceções individuais futuras são preservadas.
- A página **Editar série** permite ajustar a data-base, periodicidade e valor das próximas ocorrências sem reescrever o histórico. Uma exceção individual em um mês futuro impede a criação de uma segunda cobrança naquele mês.
- Encerrar remove ou preserva as ocorrências futuras já materializadas conforme a opção escolhida. Nenhuma nova ocorrência é criada após o término.

Ocorrências ignoradas não aparecem nas consultas mensais e não afetam os cálculos nem o fechamento. Alterações em meses fechados recalculam os saldos fechados a partir do mês afetado.

## Operação

Use **Despesas → Recorrências** para cadastrar e consultar séries. A edição ou exclusão de uma ocorrência ocorre pela listagem mensal, onde é possível escolher o escopo da ação.

Quando uma série estiver encerrada ou sua data final já tiver passado e não houver ocorrência futura ativa, ela pode ser excluída pela área de recorrências. A exclusão remove somente a série; as despesas históricas permanecem no FINUP.

Não há processamento externo nem agendamento de fila para esta funcionalidade. A geração é determinística, ocorre dentro do banco de dados e não duplica ocorrências por causa da chave única formada pela série e pela data originalmente programada.
