import { mkdir, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { build } from 'vite';

// Keep two readable browser bundles for installations that run without Node.js.
const result = await build({
    configFile: false,
    root: fileURLToPath(new URL('../', import.meta.url)),
    publicDir: false,
    logLevel: 'warn',
    build: {
        write: false,
        minify: false,
        cssMinify: false,
        cssCodeSplit: false,
        rollupOptions: {
            input: fileURLToPath(new URL('../resources/js/app.js', import.meta.url)),
            output: {
                entryFileNames: 'app.js',
                assetFileNames: 'app[extname]',
            },
        },
    },
});

const destinations = {
    'app.css': '../public/css/app.css',
    'app.js': '../public/js/app.js',
};

for (const [fileName, path] of Object.entries(destinations)) {
    const file = result.output.find((output) => output.fileName === fileName);

    if (!file) {
        throw new Error(`Static asset build did not produce ${fileName}.`);
    }

    const destination = new URL(path, import.meta.url);
    const contents = file.type === 'asset' ? file.source : file.code;

    await mkdir(new URL('.', destination), { recursive: true });
    await writeFile(destination, contents);
}
