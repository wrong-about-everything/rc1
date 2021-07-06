create table sample_table (
  id uuid primary key,
  test_field text
);

grant usage, select on all sequences in schema public to rc;
grant select, insert, update on all tables in schema public to rc;
