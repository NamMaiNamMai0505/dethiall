import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        // Bootstrap Icons KHÔNG được @import vào app.css — Lightning CSS (bundler
        // CSS của Tailwind v4) làm hỏng escape Unicode trong content: "\fXXX" khi
        // minify, biến mọi glyph icon-font thành content:"" (nút chỉ có icon như
        // /roles, /trash thành ô trống). Copy nguyên bản (không transform) thẳng
        // vào outDir (public/build) — đã có sẵn trong COPY của Dockerfile, không
        // cần sửa Dockerfile hay thêm bước build riêng nào.
        viteStaticCopy({
            targets: [
                {
                    src: 'node_modules/bootstrap-icons/font/bootstrap-icons.min.css',
                    dest: 'vendor/bootstrap-icons',
                    rename: { stripBase: true },
                },
                {
                    src: 'node_modules/bootstrap-icons/font/fonts/*',
                    dest: 'vendor/bootstrap-icons/fonts',
                    rename: { stripBase: true },
                },
            ],
        }),
    ],
});
