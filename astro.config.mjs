// @ts-check
import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';

// https://astro.build/config
// Mode STATIQUE : tout le HTML/JS/CSS est généré à la build.
// Le runtime (auth, DB, IA) est assuré par les scripts PHP du dossier /api.
export default defineConfig({
  output: 'static',
  vite: {
    plugins: [tailwindcss()],
  },
});
