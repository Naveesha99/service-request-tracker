export default function LoadingState({ label = 'Loading...' }: { label?: string }) {
  return (
    <div className="flex min-h-48 items-center justify-center" role="status">
      <div className="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-slate-900" aria-hidden="true" />
      <span className="ml-3 text-sm text-slate-600">{label}</span>
    </div>
  );
}
