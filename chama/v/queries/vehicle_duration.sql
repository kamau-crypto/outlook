#
#
with
    #
    #Split the datetime to date and time as the base of our queries
    flow as (
        select
        	flow,
            upper(vehicle .reg_no) as reg,
            cast(flow.`datetime`as date) as siku,
            cast(flow.`datetime` as time) as saa,
            `flow`.`direction` as dir,
            `operator`.`name` as `operator`
        from flow
        	inner join vehicle on flow.vehicle= vehicle.vehicle
        	inner join operator on flow.operator =operator.operator
        order by reg,siku,saa
    ),
    #
    #Show the lead direction and time
    leads as(
    	select
    		reg,
    		siku,
    		dir as dir1,
    		saa as saa1,
    		lead(dir) over(PARTITION BY reg,siku)as dir2,
    		lead(saa) over(PARTITION BY reg,siku)as saa2,
    		operator
    	from flow	
    ),
    #
    #Isolate cases with Incoming and outgoing errors
    ioerr as(
    	select
    		*
    	from leads
    	where dir1=dir2
    	
    ),
    #
    #Find the errors per day
    dailyerr as(
    	select 
    		siku,
    		count(reg)as err
    	from ioerr
    	group by siku
    ),
    #
    #Get all the flows per day
    dailyflow as(
    	select
    		siku,
    		count(reg) as total		
    	from leads
    	group by siku
    ),
    #
    #Find out the error rate per day
    performance as(
    	select
    		dailyflow.siku,
    		dailyflow.total,
    		dailyerr.err,
    		format((err/total)*100,1)as `rate(%)`
    	from dailyflow
    		left join dailyerr on dailyflow.siku= dailyerr.siku
    	order by siku desc
    ),
    #
    #Find out the duration of each visit of each car
    car_dur as(
        select
            reg,
            siku,
            saa1,
            dir1,
            saa2,
            dir2,
            timestampdiff(minute, saa1,saa2) as duration
        from leads
        where dir1 !=dir2
    ) 
--
select * from car_dur;