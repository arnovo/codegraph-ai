import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const revisionPath = join(root, 'resources/js/revision.json');

const revision = JSON.parse(readFileSync(revisionPath, 'utf8'));
revision.number = Number(revision.number ?? 0) + 1;
revision.builtAt = new Date().toISOString();

writeFileSync(revisionPath, `${JSON.stringify(revision, null, 2)}\n`);
console.log(`revision.json → ${revision.number}`);
