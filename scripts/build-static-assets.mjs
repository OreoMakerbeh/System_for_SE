import { cp, mkdir } from 'node:fs/promises';

// Keep the PHP-only setup working with the same files used by Vite.
for (const folder of ['css', 'js']) {
    const source = new URL(`../resources/${folder}/`, import.meta.url);
    const destination = new URL(`../public/${folder}/`, import.meta.url);

    await mkdir(destination, { recursive: true });
    await cp(source, destination, { recursive: true });
}
