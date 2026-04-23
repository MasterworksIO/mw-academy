import 'dotenv/config';
import pg from 'pg';

const { Client } = pg;

const client = new Client({
  host:     process.env.REDSHIFT_HOST,
  port:     Number(process.env.REDSHIFT_PORT) || 5439,
  database: process.env.REDSHIFT_DB,
  user:     process.env.REDSHIFT_USER,
  password: process.env.REDSHIFT_PASSWORD,
  ssl:      { rejectUnauthorized: false },
});

await client.connect();

const { rows } = await client.query(`
  SELECT table_schema, table_name, table_type
  FROM information_schema.tables
  WHERE table_schema NOT IN ('information_schema', 'pg_catalog', 'pg_internal')
    AND table_type IN ('BASE TABLE', 'VIEW')
  ORDER BY table_schema, table_name
`);

console.log(`\n${'SCHEMA'.padEnd(30)} ${'TABLE'.padEnd(50)} TYPE`);
console.log('─'.repeat(90));
for (const r of rows) {
  console.log(`${r.table_schema.padEnd(30)} ${r.table_name.padEnd(50)} ${r.table_type}`);
}
console.log(`\n${rows.length} tables/views found.\n`);

await client.end();
