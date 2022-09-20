with 
    invoice as (
        select
            `period`.cutoff,        
            client.`name` as client,
            invoice.invoice
        from
            invoice
            inner join `period` on invoice.`period`=`period`.`period`
            inner join client on invoice.client= client.client
        where 
            client.`name` in('mzalendo','grand_midways','export_pool','mzalendo_ex')
            and    `period`.cutoff >='2021-03-31'
        order by `period`.cutoff
    ),

    payment as (
        select
            invoice.*,
            payment.amount,
            payment.`ref`
        from
            payment
            inner join invoice on payment.invoice = invoice.invoice
    ),
    credit as (
        select
            invoice.*,
            credit.amount,
            credit.reason
        from 
            credit
            inner join invoice on credit.invoice = invoice.invoice
    )

Select * from payment;


