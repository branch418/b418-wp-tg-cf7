import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [vue()],
    define: {
        'process.env': {},
        '__VUE_OPTIONS_API__': true,
        '__VUE_PROD_DEVTOOLS__': false,
        '__VUE_PROD_HYDRATION_MISMATCH_DETAILS__': false
    },
    build: {
        outDir: 'dist',
        emptyOutDir: false,
        lib: {
            entry: path.resolve(__dirname, 'src/admin.js'),
            name: 'b418WpTgCf7Admin',
            fileName: 'admin',
            formats: ['iife']
        },
        rollupOptions: {
            output: {
                assetFileNames: 'admin.[ext]'
            }
        }
    }
});