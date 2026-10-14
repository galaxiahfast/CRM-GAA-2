import fs from 'node:fs';
import path from 'node:path';
import concurrently from 'concurrently';

if (process.platform === 'win32') {
    const windowsRoot = process.env.SystemRoot || process.env.WINDIR || 'C:\\Windows';
    const system32 = path.join(windowsRoot, 'System32');
    const commandProcessor = path.join(system32, 'cmd.exe');

    if (!fs.existsSync(commandProcessor)) {
        console.error(`No se encontró el procesador de comandos de Windows: ${commandProcessor}`);
        process.exit(1);
    }

    process.env.ComSpec = commandProcessor;

    const currentPath = process.env.PATH || process.env.Path || '';
    const hasSystem32 = currentPath
        .split(path.delimiter)
        .some((entry) => entry.toLowerCase() === system32.toLowerCase());

    if (!hasSystem32) {
        process.env.PATH = `${system32}${path.delimiter}${currentPath}`;
    }
}

const commands = [
    { command: 'php artisan serve', name: 'server', prefixColor: '#93c5fd' },
    { command: 'php artisan queue:work --queue=default --tries=3 --timeout=7200 --sleep=1', name: 'queue', prefixColor: '#a7f3d0' },
    { command: 'npm run dev', name: 'vite', prefixColor: '#fdba74' },
    { command: 'php artisan schedule:work', name: 'scheduler', prefixColor: '#86efac' },
];

if (process.platform !== 'win32') {
    commands.splice(2, 0, {
        command: 'php artisan pail --timeout=0',
        name: 'logs',
        prefixColor: '#fb7185',
    });
}

const { result } = concurrently(commands, {
    prefix: 'name',
    handleInput: true,
    killOthers: ['failure'],
});

try {
    await result;
} catch {
    process.exitCode = 1;
}
