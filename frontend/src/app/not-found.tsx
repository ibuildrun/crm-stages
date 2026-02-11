import Link from 'next/link';

export default function NotFound() {
  return (
    <div className="min-h-screen flex items-center justify-center">
      <div className="text-center space-y-4">
        <h2 className="text-lg font-semibold">Страница не найдена</h2>
        <p className="text-sm text-zinc-400">Запрашиваемая страница не существует.</p>
        <Link
          href="/"
          className="inline-block px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-xs font-medium transition-colors"
        >
          На главную
        </Link>
      </div>
    </div>
  );
}
