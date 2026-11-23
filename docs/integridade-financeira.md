# Integridade financeira e parcelamentos

## Valores e parcelas

Os lançamentos manuais aceitam valores positivos com até duas casas decimais. Os cálculos e a distribuição de parcelas usam centavos inteiros. O valor total é dividido igualmente e os centavos restantes são atribuídos, um por vez, às primeiras parcelas. Cada parcela deve ter pelo menos um centavo; portanto, um total de R$ 0,01 não pode ser dividido em duas parcelas.

Cada parcela usa a data original deslocada pelo número de meses correspondente. Se o dia não existir naquele mês, utiliza-se o último dia; no mês seguinte, retorna-se ao dia original quando possível. A data determina também o mês e o ano do lançamento.

Na edição individual, somente a parcela escolhida muda; as demais permanecem iguais e o total do grupo passa a ser a soma dos valores existentes. Na edição do grupo, informa-se o valor total, a data inicial e a quantidade desejada: o grupo anterior é substituído por novas parcelas. Alterar a quantidade redistribui todo o valor informado. Na exclusão individual, as demais parcelas e suas numerações são preservadas; na exclusão do grupo, todas são removidas. Uma edição posterior do grupo o recria por completo, inclusive se alguma parcela tiver sido excluída.

## Saldos e fechamento

O saldo anterior é o fechamento do mês imediatamente anterior, inclusive dezembro para janeiro. Quando ele não existe, considera-se zero; meses não fechados não são fechados automaticamente. Entradas incluem receitas manuais e importadas elegíveis, além do saldo anterior. Despesas elegíveis são divididas entre Pix e demais formas; o saldo final desconta ambos os grupos. Estornos e cashback reduzem as despesas na forma de pagamento correspondente; transferências próprias e outros itens excluídos não entram nos totais.

Após alterações que afetem movimentações, os fechamentos existentes a partir do primeiro mês afetado são recalculados em ordem cronológica. Isso inclui alterações manuais e mudanças relevantes nas transações importadas. Fechar novamente um mês também atualiza fechamentos posteriores. Valores de fechamento antigos podem, portanto, mudar após uma operação; não é criado um histórico de versões desses valores. Lacunas entre meses fechados interrompem a continuidade do saldo, pois apenas o mês anterior imediato é considerado.

As alterações de código não recalculam nem modificam dados existentes por si só. Parcelamentos antigos não têm datas ou valores corrigidos automaticamente; uma edição de grupo os recria com as regras atuais. Os testes usam dados fictícios em SQLite isolado, sem acessar o banco real ou serviços bancários.

## Limitações

O cálculo não identifica automaticamente lançamentos manuais duplicados de transações importadas. A conciliação e a identificação de banco e novas formas de pagamento pertencem aos módulos posteriores. A consistência sob operações simultâneas e a proteção contra fechamentos duplicados precisam de avaliação adicional com o banco de produção e seus índices; os testes automatizados deste módulo utilizam SQLite.
