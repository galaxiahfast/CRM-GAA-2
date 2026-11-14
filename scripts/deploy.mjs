// Despliegue del CRM en el servidor con un solo comando: composer run deploy (o doble clic en desplegar.bat).
//
// Hace los mismos pasos de siempre, pero se salta los lentos cuando no hace falta:
//   1. git pull                       (descarga los cambios; se omite con --sin-git)
//   2. composer install --no-dev      (solo si cambió composer.lock o falta vendor)
//   3. npm ci                         (solo si cambió package-lock.json o falta node_modules)
//   4. npm run build                  (solo si cambiaron estilos, scripts o vistas, o falta public/build)
//   5. php artisan migrate --force    (siempre; si no hay migraciones nuevas no hace nada)
//   6. php artisan optimize:clear + optimize   (cachés de configuración, rutas, eventos y vistas)
//   7. php artisan queue:restart      (las colas se reinician solas con los cambios nuevos)
//
// Opciones: --todo (hace todos los pasos aunque no haya cambios), --sin-git, --simular (solo muestra qué haría).
import { spawnSync } from 'node:child_process';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const args = new Set(process.argv.slice(2));
const forceAll = args.has('--todo');
const dryRun = args.has('--simular');
const skipGit = args.has('--sin-git');
const php = process.env.CRM_PHP || 'php';
const stateFile = path.join(root, 'storage', 'app', 'deploy-state.json');
const started = Date.now();

const at = (...parts) => path.join(root, ...parts);

function readState() {
    try {
        return JSON.parse(fs.readFileSync(stateFile, 'utf8'));
    } catch {
        return {};
    }
}

function saveState(state) {
    if (dryRun) return;
    fs.mkdirSync(path.dirname(stateFile), { recursive: true });
    fs.writeFileSync(stateFile, JSON.stringify(state, null, 2));
}

// Huella de un conjunto de archivos y carpetas: si cambia algún archivo, cambia la huella
function fingerprint(entries) {
    const hash = crypto.createHash('sha1');
    const walk = (target) => {
        if (!fs.existsSync(target)) return;
        const stat = fs.statSync(target);
        if (stat.isDirectory()) {
            for (const name of fs.readdirSync(target).sort()) walk(path.join(target, name));
            return;
        }
        hash.update(path.relative(root, target));
        hash.update(fs.readFileSync(target));
    };
    entries.forEach((entry) => walk(at(entry)));
    return hash.digest('hex');
}

function run(title, command, commandArgs) {
    const line = [command, ...commandArgs].join(' ');
    console.log(`\n> ${title}\n  ${line}`);
    if (dryRun) return;

    const stepStarted = Date.now();
    // composer y npm son archivos .bat/.cmd en Windows: necesitan la consola de comandos
    const result = spawnSync(command, commandArgs, { cwd: root, stdio: 'inherit', shell: process.platform === 'win32' });
    if (result.status !== 0) {
        console.error(`\nEl paso «${title}» falló (código ${result.status ?? result.error?.message}). El despliegue se detuvo aquí.`);
        process.exit(1);
    }
    console.log(`  Listo en ${((Date.now() - stepStarted) / 1000).toFixed(1)} s`);
}

function skip(title, reason) {
    console.log(`\n- ${title}: se omite (${reason})`);
}

console.log(dryRun ? 'Simulación del despliegue (no se ejecuta nada)' : 'Desplegando el CRM');

// 1. Descargar los cambios
if (skipGit || !fs.existsSync(at('.git'))) {
    skip('Descargar cambios', skipGit ? 'se pidió --sin-git' : 'la carpeta no es un repositorio git');
} else {
    run('Descargar cambios', 'git', ['pull', '--ff-only']);
}

const state = readState();
const next = { ...state };

// 2. Dependencias de PHP
const composerPrint = fingerprint(['composer.lock']);
if (forceAll || composerPrint !== state.composer || !fs.existsSync(at('vendor', 'autoload.php'))) {
    run('Dependencias de PHP', 'composer', ['install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction']);
    next.composer = composerPrint;
} else {
    skip('Dependencias de PHP', 'composer.lock no cambió');
}

// 3. Dependencias del frontend
const npmPrint = fingerprint(['package-lock.json']);
if (forceAll || npmPrint !== state.npm || !fs.existsSync(at('node_modules'))) {
    run('Dependencias del frontend', 'npm', ['ci']);
    next.npm = npmPrint;
} else {
    skip('Dependencias del frontend', 'package-lock.json no cambió');
}

// 4. Compilar estilos y scripts (Tailwind lee las vistas, por eso también cuentan)
const buildPrint = fingerprint(['resources', 'vite.config.js', 'tailwind.config.js', 'postcss.config.js', 'package-lock.json']);
if (forceAll || buildPrint !== state.build || !fs.existsSync(at('public', 'build', 'manifest.json'))) {
    run('Compilar estilos y scripts', 'npm', ['run', 'build']);
    next.build = buildPrint;
} else {
    skip('Compilar estilos y scripts', 'no cambiaron estilos, scripts ni vistas');
}

// Si quedó Vite en modo desarrollo, las demás computadoras verían la página sin estilos
if (fs.existsSync(at('public', 'hot')) && !dryRun) {
    fs.rmSync(at('public', 'hot'), { force: true });
    console.log('\n- Se quitó public/hot para usar los archivos compilados.');
}

// 5. Base de datos
run('Migraciones de la base de datos', php, ['artisan', 'migrate', '--force']);

// 6. Cachés de Laravel
run('Limpiar cachés', php, ['artisan', 'optimize:clear']);
run('Generar cachés de producción', php, ['artisan', 'optimize']);

// 7. Colas: terminan su trabajo actual y vuelven a arrancar con el código nuevo
run('Reiniciar colas', php, ['artisan', 'queue:restart']);

// 8. Si cambió el arranque del servidor (server.mjs o iniciar-servidor.bat), la tarea de Windows se reinicia para
//    usar la versión nueva; la web se corta unos segundos. Sin esos cambios no se toca.
const taskName = 'CRM GAA servidor';
const serverPrint = fingerprint(['scripts/server.mjs', 'scripts/iniciar-servidor.bat']);
if (state.server && serverPrint !== state.server && process.platform === 'win32') {
    const exists = spawnSync('schtasks', ['/Query', '/TN', taskName], { stdio: 'ignore' }).status === 0;
    if (!exists) {
        skip('Reiniciar el servidor', `no existe la tarea «${taskName}»`);
    } else if (dryRun) {
        console.log(`\n> Reiniciar el servidor\n  schtasks /End y /Run de «${taskName}»`);
    } else {
        console.log('\n> Reiniciar el servidor (cambió su arranque)');
        spawnSync('schtasks', ['/End', '/TN', taskName], { stdio: 'inherit' });
        spawnSync(process.platform === 'win32' ? 'ping' : 'sleep', process.platform === 'win32' ? ['-n', '4', '127.0.0.1'] : ['3'], { stdio: 'ignore' });
        const relaunch = spawnSync('schtasks', ['/Run', '/TN', taskName], { stdio: 'inherit' });
        if (relaunch.status !== 0) {
            console.warn(`  No se pudo reiniciar la tarea. Abre una terminal como administrador y ejecuta: schtasks /Run /TN "${taskName}"`);
        }
    }
} else {
    skip('Reiniciar el servidor', 'su arranque no cambió');
}
next.server = serverPrint;

saveState(next);
console.log(`\nDespliegue terminado en ${((Date.now() - started) / 1000).toFixed(0)} s.`);
