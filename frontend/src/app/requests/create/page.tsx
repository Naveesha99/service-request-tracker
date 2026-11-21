'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { FormEvent, useState } from 'react';
import AppShell from '@/components/AppShell';
import ProtectedPage from '@/components/ProtectedPage';
import { ApiError } from '@/lib/api';
import { createRequest } from '@/lib/requests';
import type { RequestPriority } from '@/lib/types';

export default function CreateRequestPage() {
  return <ProtectedPage><AppShell><CreateRequest /></AppShell></ProtectedPage>;
}

function CreateRequest() {
  const router = useRouter();
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [priority, setPriority] = useState<RequestPriority>('MEDIUM');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setError('');
    setFieldErrors({});

    const clientErrors: Record<string, string[]> = {};
    if (title.trim().length < 3) clientErrors.title = ['Title must be at least 3 characters.'];
    if (title.trim().length > 100) clientErrors.title = ['Title cannot exceed 100 characters.'];
    if (description.trim().length < 10) clientErrors.description = ['Description must be at least 10 characters.'];
    if (description.trim().length > 500) clientErrors.description = ['Description cannot exceed 500 characters.'];
    if (Object.keys(clientErrors).length) {
      setFieldErrors(clientErrors);
      return;
    }

    setSubmitting(true);
    try {
      const response = await createRequest({ title: title.trim(), description: description.trim(), priority });
      router.replace(`/requests/${response.data.id}`);
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
        setFieldErrors(caught.fieldErrors ?? {});
      } else setError('Unable to create the request. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="mx-auto max-w-2xl">
      <div><p className="text-sm text-slate-500">New request</p><h1 className="mt-1 text-2xl font-semibold">Create service request</h1></div>
      {error && <div role="alert" className="mt-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</div>}
      <form onSubmit={submit} noValidate className="mt-6 space-y-5 rounded-lg border border-slate-200 bg-white p-5 sm:p-6">
        <div><div className="flex items-center justify-between"><label htmlFor="title" className="text-sm font-medium">Title</label><span className="text-xs text-slate-500">{title.length}/100</span></div><input id="title" value={title} maxLength={100} onChange={(event) => setTitle(event.target.value)} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />{fieldErrors.title?.map((message) => <p key={message} className="mt-1 text-xs text-rose-700">{message}</p>)}</div>
        <div><div className="flex items-center justify-between"><label htmlFor="description" className="text-sm font-medium">Description</label><span className="text-xs text-slate-500">{description.length}/500</span></div><textarea id="description" value={description} maxLength={500} rows={6} onChange={(event) => setDescription(event.target.value)} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />{fieldErrors.description?.map((message) => <p key={message} className="mt-1 text-xs text-rose-700">{message}</p>)}</div>
        <div><label htmlFor="priority" className="text-sm font-medium">Priority</label><select id="priority" value={priority} onChange={(event) => setPriority(event.target.value as RequestPriority)} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><option value="LOW">LOW</option><option value="MEDIUM">MEDIUM</option><option value="HIGH">HIGH</option></select>{fieldErrors.priority?.map((message) => <p key={message} className="mt-1 text-xs text-rose-700">{message}</p>)}</div>
        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><Link href="/requests" className="rounded-md border border-slate-300 px-4 py-2 text-center text-sm font-medium">Cancel</Link><button type="submit" disabled={submitting} className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">{submitting ? 'Creating...' : 'Create request'}</button></div>
      </form>
    </div>
  );
}
