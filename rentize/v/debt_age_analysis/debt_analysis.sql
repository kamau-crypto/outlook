--
-- The query to generate all the debtors with debts older than an year old.
with
    --
    --Get all the current with valid agreements
    aclient as (
        select 
           client.client,
            client.name
        from 
            client
            inner join agreement on agreement.client=client.client
        where
            agreement.terminated is NULL and agreement.valid
    ),
    --
    --Balances as a base for other calculations
    bal as(
        select 
            invoice.client,
            period.month as mon,
            period.year as yr,
            closing_balance.amount
        from 
            closing_balance 
            inner join invoice on closing_balance.invoice= invoice.invoice
            inner join period on invoice.period= period.period
    ),
    --
    --Calculate the current balance now
    current_bal as(
        select bal.*
        from bal
        where mon= MONTH(NOW())and yr =YEAR(NOW())  
    ),
    --
    --Calculate the balances in the last 3 months
    bal_3 as(
        select bal.*
        from bal
        --
        --Get the month and year of the date three months ago
        where mon= MONTH(DATE_SUB(CURDATE(),INTERVAL 3 MONTH))and
                yr=YEAR(DATE_SUB(CURDATE(),INTERVAL 3 MONTH))
    ),
    --
    --Calculate the balances from the last 6 months
    bal_6 as(
        select *
        from bal
        --
        --Get the month and the year of the date 6 months ago
        where mon= MONTH(DATE_SUB(CURDATE(),INTERVAL 6 MONTH))and
              yr=YEAR(DATE_SUB(CURDATE(), INTERVAL 6 MONTH))
    ),
    --
    --Calculate the balances as from 12 months ago
    bal_12 as(
        select *
        from bal
        where mon= MONTH(DATE_SUB(CURDATE(),INTERVAL 1 YEAR))and
              yr=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 YEAR))
    ),
    --
    --Calculate the balance from 12 months ago to 6 months ago
    D1 as(
        select
            bal_12.client,
            (bal_6.amount- bal_12.amount) as amount
        from bal_12
            inner join bal_6 on bal_6.client= bal_12.client
    ),
    --
    --Calculate the balance from 6 months ago to 3 months ago
    D2 as(
        select
            bal_6.client,
            (bal_3.amount-bal_6.amount) as amount
        from bal_6
            inner join bal_3 on bal_3.client=bal_6.client
    ),
    --
    -- The balance from three months ago to now
    D3 as(
        select 
            bal_3.client,
            (current_bal.amount- bal_3.amount) as amount
        from bal_3
            inner join current_bal on current_bal.client=bal_3.client
    )
    --
    --Select all debts that all older than 1 year, between 12 months and 6 months,
    -- between 6 months and 3 months, and debts less than 3 months old.
    select
        aclient.client,
        aclient.name,
        bal_12.amount as `debt_older_than_1yr`,
        D1.amount as `12_months<debt>6_months`,
        D2.amount as `6_months<debt>3_months`,
        D3.amount as `3_months<debt>now`,
        current_bal.amount as `current_balance`
    from aclient
        inner join bal_12 on bal_12.client=`aclient`.`client`
        inner join D1 on D1.client=`aclient`.`client`
        inner join D2 on D2.client=`aclient`.`client`
        inner join D3 on D3.client=`aclient`.`client`
        inner join current_bal on current_bal.client=`aclient`.`client`
    order by client ASC;
/**/
    select * from aclient;