'use client';

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <html lang="ru" className="dark">
      <body className="bg-[#0B0D0E] text-white min-h-screen flex items-center justify-center">
        <div className="text-center space-y-4">
          <h2 className="text-lg font-semibold">Что-то пошло не так</h2>
          <p className="text-sm text-zinc-400">{error.message}</p>
          <button
            onClick={reset}
            className="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-xs font-medium transition-colors"
          >
            Попробовать снова
          </button>
        </div>
      </body>
    </html>
  );
}
