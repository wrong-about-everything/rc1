create table sample_table (
  id int primary key,
  id_1c text, -- для отчетов
  id_aventa text, -- для отчетов
  name text,
  address text,
  city text,
  country text,
  brand_name text,
  brand int,
  uri text,
  username text,
  password text,
  station_id  text,
  cashier_id text,
  principal text, -- для отчетов
  inn text, -- для отчетов
  agent text, -- для отчетов
  legal_name text, -- для отчетов,
  utc_offset text -- e.g., "Europe/Moscow", "Asia/Novosibirsk", etc
);

grant usage, select on all sequences in schema public to rc;
grant select, insert, update on all tables in schema public to rc;
