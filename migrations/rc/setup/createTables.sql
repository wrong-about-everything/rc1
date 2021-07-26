-- @todo: Add slow query logs!

-- do not remove it, it is for tests
create table sample_table (
  id uuid primary key,
  test_field text
);

create table "bot" (
  id uuid primary key,
  token text,
  is_private bool default false,
  name text,
  available_positions jsonb,
  available_experiences jsonb
);

create table "group" (
  id uuid primary key,
  bot_id uuid,
  name text
);

-- @todo: rename to telegram_user
create table "user" (
  id uuid primary key,
  first_name text,
  last_name text,
  telegram_id int,
  telegram_handle text,

  unique (telegram_id)
);

create table bot_user (
  id uuid,
  user_id uuid,
  bot_id uuid,
  position smallint,
  experience smallint,
  about text,
  status int,

  primary key (id),
  unique (user_id, bot_id)
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

create table meeting_round (
  id uuid,
  bot_id uuid,
  name text,
  start_date timestamptz,
  timezone text,
  available_interests jsonb,

  primary key (id)
);

create table meeting_round_invitation (
  id uuid,
  meeting_round_id uuid,
  user_id uuid,
  status smallint,

  primary key (id),
  unique (meeting_round_id, user_id)
);

create table meeting_round_registration_question (
  id uuid,
  meeting_round_id uuid,
  user_interest smallint,
  ordinal_number smallint,
  text text,

  primary key (id)
);

create table user_round_registration_progress (
  registration_question_id uuid,
  user_id uuid,

  primary key (registration_question_id, user_id)
);

create table meeting_round_participant (
  id uuid,
  user_id uuid,
  meeting_round_id uuid,
  status smallint,
  interested_in_as_plain_text text,
  interested_in jsonb,

  primary key (id),
  unique (user_id, meeting_round_id)
);

create procedure create_analysis_paradisis_round(
    id uuid,
    bot_id uuid,
    start_date timestamptz
)
    language plpgsql
as $$
begin

    insert into meeting_round (id, bot_id, name, start_date, timezone, available_interests)
    select id, bot_id, 'Новый раунд', start_date, 'Europe/Moscow', '"[0, 1]"';

    commit;
end;$$
;

grant usage, select on all sequences in schema public to rc;
grant select, insert, update on all tables in schema public to rc;
