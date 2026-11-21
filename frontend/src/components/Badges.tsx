import type { RequestPriority, RequestStatus } from '@/lib/types';

export function StatusBadge({ status }: { status: RequestStatus }) {
  const styles = {
    OPEN: 'bg-blue-50 text-blue-700 ring-blue-200',
    IN_PROGRESS: 'bg-amber-50 text-amber-800 ring-amber-200',
    RESOLVED: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  };

  const labels = { OPEN: 'Open', IN_PROGRESS: 'In progress', RESOLVED: 'Resolved' };

  return (
    <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${styles[status]}`}>
      {labels[status]}
    </span>
  );
}

export function PriorityBadge({ priority }: { priority: RequestPriority }) {
  const styles = {
    LOW: 'bg-slate-100 text-slate-700',
    MEDIUM: 'bg-violet-50 text-violet-700',
    HIGH: 'bg-rose-50 text-rose-700',
  };
  return <span className={`inline-flex rounded-md px-2 py-1 text-xs font-semibold ${styles[priority]}`}>{priority}</span>;
}
