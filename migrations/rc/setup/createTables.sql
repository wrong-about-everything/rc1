create table sample_table (
  id uuid primary key,
  test_field text
);

create table "bot" (
  id uuid primary key,
  token text,
  is_private bool,
  name text
);

create table "group" (
  id uuid primary key,
  bot_id uuid,
  name text
);

create table "user" (
  id uuid primary key,
  name text,
  telegram_id int,
  telegram_handle text
);

create table user_bot (
  user_id uuid,
  bot_id uuid,

  primary key (user_id, bot_id)
);

grant usage, select on all sequences in schema public to rc;
grant select, insert, update on all tables in schema public to rc;
