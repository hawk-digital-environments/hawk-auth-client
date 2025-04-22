import {dirname, resolve} from 'node:path';
import {fileURLToPath} from 'node:url';
import {defineConfig} from 'vite';
import dtsPlugin from 'vite-plugin-dts';

const __dirname = dirname(fileURLToPath(import.meta.url))

export default defineConfig({
    build: {
        lib: {
            entry: resolve(__dirname, 'src/index.ts'),
            name: 'HawkAuthClient',
            // the proper extensions will be added
            fileName: 'hawk-auth-client',
        }
    },
    plugins: [dtsPlugin()]
    
})
