create table groups
(
  id int auto_increment
    primary key,
  name varchar(15) not null,
  course int(1) not null,
  constraint name
  unique (name)
)
;

create table students_schedule
(
  id int auto_increment
    primary key,
  group_id int not null,
  weekday int(1) not null comment 'From monday',
  weektype int(1) not null comment '0 - all, 1 - bottom, 2 - top',
  start int(4) not null comment 'in minutes of day',
  end int(4) not null comment 'in minutes of day',
  subject varchar(1000) null,
  constraint group_id
  foreign key (group_id) references groups (id)
    on update cascade on delete cascade
)
;

create index group_id
  on students_schedule (group_id)
;

