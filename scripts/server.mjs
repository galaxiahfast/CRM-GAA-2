import { spawn } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const php = process.env.CRM_PHP || 'php';
const tasks = [
    { name: 'programador', args: ['artisan', 'schedule:work'] },
    { name: 'cola general', args: ['artisan', 'queue:work', 'database', '--queue=default', '--tries=3', '--timeout=7200', '--sleep=1'] },
    { name: 'respaldos', args: ['artisan', 'queue:work', 'backup_database', '--queue=backup-uploads', '--tries=3', '--timeout=7200', '--sleep=1'] },
];

let stopping = false;
const children = new Map();

function print(stream, name) {
    let pending = '';

    stream.on('data', (chunk) => {
        pending += chunk.toString();
        const lines = pending.split(/\r?\n/);
        pending = lines.pop();
        for (const line of lines) {
            if (line) process.stdout.write(`[${name}] ${line}\n`);
        }
    });

    stream.on('end', () => {
        if (pending) process.stdout.write(`[${name}] ${pending}\n`);
    });
}

function start(task) {
    if (stopping) return;

    const child = spawn(php, task.args, { cwd: root, stdio: ['ignore', 'pipe', 'pipe'], windowsHide: true });
    children.set(task.name, child);
    print(child.stdout, task.name);
    print(child.stderr, task.name);

    let finished = false;
    const restart = (reason) => {
        if (finished) return;
        finished = true;
        children.delete(task.name);
        if (!stopping) {
            process.stderr.write(`[${task.name}] ${reason}; reiniciando en 5 segundos.\n`);
            setTimeout(() => start(task), 5000);
        }
    };

    child.on('error', (error) => restart(error.message));
    child.on('exit', (code, signal) => restart(`terminó (${signal || code})`));
}

function stop() {
    if (stopping) return;
    stopping = true;
    for (const child of children.values()) child.kill();
    setTimeout(() => process.exit(0), 1500).unref();
}

process.on('SIGINT', stop);
process.on('SIGTERM', stop);

console.log('Apache sirve la web; iniciando programador y ambas colas. Pulsa Ctrl+C para detenerlos.');
for (const task of tasks) start(task);
