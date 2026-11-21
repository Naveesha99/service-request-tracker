import type { PaginationMeta } from '@/lib/types';

export default function Pagination({ meta, onPageChange }: { meta: PaginationMeta; onPageChange: (page: number) => void }) {
  return (
    <nav className="flex items-center justify-between border-t border-slate-200 pt-4" aria-label="Pagination">
      <p className="text-sm text-slate-500">Page {meta.current_page} of {meta.last_page}</p>
      <div className="flex gap-2">
        <button type="button" disabled={meta.current_page <= 1} onClick={() => onPageChange(meta.current_page - 1)} className="rounded-md border border-slate-300 px-3 py-2 text-sm disabled:opacity-40">Previous</button>
        <button type="button" disabled={meta.current_page >= meta.last_page} onClick={() => onPageChange(meta.current_page + 1)} className="rounded-md border border-slate-300 px-3 py-2 text-sm disabled:opacity-40">Next</button>
      </div>
    </nav>
  );
}
