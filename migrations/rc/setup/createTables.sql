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

create table "telegram_user" (
  id uuid primary key,
  first_name text,
  last_name text,
  telegram_id bigint,
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
  invitation_date timestamptz,
  start_date timestamptz,
  feedback_date timestamptz,
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
  type smallint,
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

create table meeting_round_pair (
  id uuid,
  participant_id uuid,
  match_participant_id uuid,
  match_participant_contacts_sent bool default false,

  primary key (id),
  unique (participant_id),
  unique (match_participant_id)
);

create table meeting_round_dropout (
  id uuid,
  dropout_participant_id uuid,

  primary key (id),
  unique (dropout_participant_id)
);

create table feedback_invitation (
  id uuid,
  participant_id uuid,
  status smallint,

  primary key (id),
  unique (participant_id)
);

create table feedback_question (
  id uuid,
  meeting_round_id uuid,
  ordinal_number smallint,
  text text,

  primary key (id)
);

create table feedback_answer (
  feedback_question_id uuid,
  participant_id uuid,
  text text,

  primary key (feedback_question_id, participant_id)
);


grant usage, select on all sequences in schema public to rc;
grant select, insert, update on all tables in schema public to rc;
