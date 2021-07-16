-- do not touch it, it is for tests
create table sample_table (
  id uuid primary key,
  test_field text
);

create table "bot" (
  id uuid primary key,
  token text,
  is_private bool default false,
  name text
);

create table "group" (
  id uuid primary key,
  bot_id uuid,
  name text
);

create table "user" (
  id uuid primary key,
  first_name text,
  last_name text,
  telegram_id int,
  telegram_handle text,

  unique (telegram_id)
);

create table user_bot (
  user_id uuid,
  bot_id uuid,

  primary key (user_id, bot_id)
);

create table registration_question (
  id uuid primary key,
  profile_record_type smallint,
  bot_id uuid,
  ordinal_number smallint,
  text text
);

create table user_registration_progress (
  registration_question_id uuid,
  user_id uuid,

  primary key (registration_question_id, user_id)
);

create table user_profile (
  user_id uuid primary key,
  position smallint,
  experience smallint,
  about text
);


grant usage, select on all sequences in schema public to rc;
grant select, insert, update on all tables in schema public to rc;
