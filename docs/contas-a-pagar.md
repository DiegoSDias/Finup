# Contas a pagar

Uma conta a pagar representa um compromisso ou uma previsão. Ela não altera os totais realizados enquanto estiver pendente ou vencida.

## Estados

- **Pendente:** ainda não foi liquidada.
- **Vencida:** é pendente e o vencimento já passou. Esse estado é calculado pela data, portanto não depende de uma tarefa agendada para aparecer corretamente.
- **Paga:** possui exatamente uma liquidação registrada.
- **Cancelada:** não deve mais ser paga e não afeta os totais.

## Liquidação e não duplicidade

Uma conta pode ser liquidada por somente uma origem:

1. **Despesa manual:** o FINUP cria uma despesa manual vinculada, usando o valor, a categoria e a descrição da conta.
2. **Transação importada pela Pluggy:** o FINUP apenas vincula a conta a uma transação já importada. Nenhuma despesa manual adicional é criada.

O vínculo é único no banco de dados. A mesma transação Pluggy não pode liquidar duas contas e uma conta paga não pode ser liquidada outra vez. Ao desfazer uma liquidação manual, a despesa vinculada é removida junto e a conta volta a ficar pendente. Ao desfazer um vínculo Pluggy, a transação importada é preservada e a conta volta a ficar pendente.

Contas pagas não podem ser excluídas diretamente. É necessário desfazer a liquidação primeiro, evitando apagar ou ocultar uma movimentação financeira real por acidente.

## Categorias e pagamento manual

Uma conta pode ser cadastrada sem categoria, mas precisa de uma categoria de despesa válida do mesmo usuário para gerar uma despesa manual. O pagamento manual permite informar data, forma de pagamento e banco de origem, usando as mesmas opções dos lançamentos manuais.

## Lembretes

A regra aprovada é avisar três dias antes do vencimento, no dia e enquanto a conta estiver vencida e pendente. A infraestrutura do sino, leitura e prevenção de avisos repetidos será criada no **Módulo 12 — Lembretes e notificações**. O Módulo 8 já fornece os vencimentos e estados necessários, sem criar notificações antecipadamente.
