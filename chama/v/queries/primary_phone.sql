with
	#
	#Rank all users with the same phone number
	identical_num as(
		select 
			mobile.`user` as user,
			mobile.num,
			rank() over (partition by mobile.num order by mobile.user desc) as number
		from mobile
	),
	#
	#Rank all users with more than one phone number 
	more_num as(
		select 
			mobile.`user` as user,
			mobile.num,
			rank() over(partition by mobile.user order by mobile.num) as users
		from mobile
	),
	#
	#Get all users with the same phone number
	same_phone as(
		select
			user,
			num,
			number
		from identical_num 
		where number=1
		group by user,num
	),
	#
	#Get all the primary phone number of the each user
	pri_phone as(
		select
			user,
			num,
			users
		from more_num 
		where users=1
		group by user,num
	)
select * from pri_phone;