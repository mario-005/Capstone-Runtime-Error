import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

const stack = [
    ['Backend', 'Laravel 13'],
    ['Frontend', 'React 19 + Tailwind CSS 4'],
    ['Database', 'PostgreSQL 18'],
    ['Environment', 'Docker Compose'],
];

function App() {
    return (
        <main className="min-h-screen bg-slate-950 px-6 py-16 text-slate-100">
            <section className="mx-auto max-w-5xl">
                <p className="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-400">
                    Silih Asih
                </p>
                <h1 className="mt-4 max-w-3xl text-4xl font-bold tracking-tight sm:text-6xl">
                    Sistem Pengelolaan Produksi dan Bahan Baku
                </h1>
                <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                    Fondasi aplikasi telah siap. Modul pesanan, persediaan, dan produksi akan dibangun
                    mengikuti dokumentasi pada folder docs.
                </p>

                <div className="mt-12 grid gap-4 sm:grid-cols-2">
                    {stack.map(([label, value]) => (
                        <article
                            className="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl shadow-black/10"
                            key={label}
                        >
                            <p className="text-sm text-slate-400">{label}</p>
                            <p className="mt-2 text-xl font-semibold text-white">{value}</p>
                        </article>
                    ))}
                </div>
            </section>
        </main>
    );
}

const rootElement = document.getElementById('app');

if (rootElement) {
    createRoot(rootElement).render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
}
