create table cache(
   word varchar(50) not null,
   matches text,
   primary key(word)
);

create table cache_l2(
   word varchar(50) not null,
   second_word varchar(50) not null,
   matches text,
   primary key(word, second_word)
);

create table cache_l3(
   word varchar(50) not null,
   second_word varchar(50) not null,
   third_word varchar(50) not null,
   matches text,
   primary key(word, second_word, third_word)
);
