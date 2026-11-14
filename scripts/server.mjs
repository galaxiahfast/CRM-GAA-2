// Arranca el CRM en el servidor: la web, el programador (sincroniza el reloj checador cada minuto y lo revisa a
// las 23:00) y las dos colas. Si alguno se cae, se vuelve a iniciar a los 5 segundos.
// Uso: composer run server (o scripts\iniciar-servidor.bat, que es lo que ejecuta la tarea de Windows).
import { spawn, spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const php = process.env.CRM_PHP || 'php';
const tasks = [
    { name: 'programador', args: ['artisan', 'schedule:work'] },
    { name: 'cola general', args: ['artisan', 'queue:work', 'database', '--queue=default', '--tries=3', '--timeout=7200', '--sleep=1'] },
    { name: 'respaldos', args: ['artisan', 'queue:work', 'backup_database', '--queue=backup-uploads', '--tries=3', '--timeout=7200', '--sleep=1'] },
];

// La web también se levanta aquí, abierta a la red (0.0.0.0) para que las demás computadoras la vean.
// CRM_HOST y CRM_PORT cambian la dirección; CRM_WEB=0 la desactiva si Apache ya sirve la carpeta public.
const host = process.env.CRM_HOST || '0.0.0.0';
const port = process.env.CRM_PORT || '8001';
if (process.env.CRM_WEB !== '0') {
    tasks.unshift({ name: 'web', args: ['artisan', 'serve', `--host=${host}`, `--port=${port}`, '--no-reload'] });
}

// Con CRM_LOG la salida va a ese archivo (lo usa la tarea de Windows, que no tiene consola). Al pasar de 10 MB
// el archivo se renombra a .old.log y se empieza uno nuevo, así nunca ocupa más de unos 20 MB.
const logFile = process.env.CRM_LOG ? path.resolve(root, process.env.CRM_LOG) : null;
const maxLogBytes = 10 * 1024 * 1024;
let writesSinceCheck = 0;

function write(text) {
    if (!logFile) {
        process.stdout.write(text);
        return;
    }
    try {
        if (++writesSinceCheck >= 100) {
            writesSinceCheck = 0;
            if (fs.existsSync(logFile) && fs.statSync(logFile).size > maxLogBytes) {
                fs.renameSync(logFile, logFile.replace(/\.log$/, '') + '.old.log');
            }
        }
        const stamp = new Date().toLocaleString('es-MX', { hour12: false });
        fs.appendFileSync(logFile, text.split('\n').map((line) => (line ? `${stamp} ${line}` : line)).join('\n'));
    } catch {
        // Si el registro no se puede escribir, el CRM sigue funcionando igual
    }
}

if (logFile) {
    fs.mkdirSync(path.dirname(logFile), { recursive: true });
    writesSinceCheck = 100; // revisa el tamaño desde el primer mensaje
}

// Si quedó el archivo de Vite en modo desarrollo, las demás computadoras intentarían cargar los estilos desde
// «localhost» y verían la página sin diseño. En el servidor se usan los archivos compilados (npm run build).
const hotFile = path.join(root, 'public', 'hot');
if (fs.existsSync(hotFile)) {
    fs.rmSync(hotFile, { force: true });
    write('Se quitó public/hot: la web usará los archivos compilados de public/build.\n');
}
if (!fs.existsSync(path.join(root, 'public', 'build', 'manifest.json'))) {
    write('Falta public/build: ejecuta «npm run build» (o composer run deploy) para que la web cargue sus estilos y scripts.\n');
}

let stopping = false;
const children = new Map();

// La web escribe una línea por cada petición (y otra con su duración): solo se guardan los avisos y errores,
// para que el registro no se llene de peticiones normales.
const requestLine = /^\s*(\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}:\d{2}\s+\S|[.\s]*~?\s*[\d.]+\s*(ms|s)\s*$|[.\s]+$)/;

function print(stream, name, quiet = false) {
    let pending = '';

    stream.on('data', (chunk) => {
        pending += chunk.toString();
        const lines = pending.split(/\r?\n/);
        pending = lines.pop();
        for (const line of lines) {
            if (line && !(quiet && requestLine.test(line))) write(`[${name}] ${line}\n`);
        }
    });

    stream.on('end', () => {
        if (pending && !(quiet && requestLine.test(pending))) write(`[${name}] ${pending}\n`);
    });
}

function start(task) {
    if (stopping) return;

    const child = spawn(php, task.args, { cwd: root, stdio: ['ignore', 'pipe', 'pipe'], windowsHide: true });
    children.set(task.name, child);
    print(child.stdout, task.name, task.name === 'web');
    print(child.stderr, task.name, task.name === 'web');

    let finished = false;
    const restart = (reason) => {
        if (finished) return;
        finished = true;
        children.delete(task.name);
        if (!stopping) {
            write(`[${task.name}] ${reason}; reiniciando en 5 segundos.\n`);
            setTimeout(() => start(task), 5000);
        }
    };

    child.on('error', (error) => restart(error.message));
    child.on('exit', (code, signal) => restart(`terminó (${signal || code})`));
}

function stop() {
    if (stopping) return;
    stopping = true;
    for (const child of children.values()) {
        // En Windows «artisan serve» abre su propio proceso de PHP: se cierra todo el árbol para liberar el puerto
        if (process.platform === 'win32' && child.pid) {
            spawnSync('taskkill', ['/pid', String(child.pid), '/T', '/F'], { stdio: 'ignore' });
        } else {
            child.kill();
        }
    }
    setTimeout(() => process.exit(0), 1500).unref();
}

process.on('SIGINT', stop);
process.on('SIGTERM', stop);

write(process.env.CRM_WEB === '0'
    ? 'Apache sirve la web; iniciando programador y ambas colas. Pulsa Ctrl+C para detenerlos.\n'
    : `Iniciando la web en http://${host}:${port}, el programador y ambas colas. Pulsa Ctrl+C para detenerlos.\n`);

// Si los cuatro procesos arrancan a la vez y falta bootstrap/cache/packages.php (por ejemplo, después de un
// despliegue), todos intentan crearlo al mismo tiempo y Windows rechaza a alguno con «Acceso denegado».
// Se genera una sola vez antes y los procesos se inician con un segundo de diferencia.
const discover = spawnSync(php, ['artisan', 'package:discover'], { cwd: root, encoding: 'utf8', windowsHide: true });
if (discover.status !== 0) {
    write(`[inicio] No se pudo preparar la caché de paquetes: ${(discover.stderr || discover.stdout || discover.error?.message || '').trim()}\n`);
}
tasks.forEach((task, index) => setTimeout(() => start(task), index * 1000));
