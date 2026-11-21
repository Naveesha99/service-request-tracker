export type RequestPriority = 'LOW' | 'MEDIUM' | 'HIGH';
export type RequestStatus = 'OPEN' | 'IN_PROGRESS' | 'RESOLVED';

export type PublicUser = {
  id: number;
  name: string;
  email: string;
};

export type ServiceRequest = {
  id: number;
  title: string;
  description: string;
  priority: RequestPriority;
  status: RequestStatus;
  version: number;
  created_at: string;
  updated_at: string;
};

export type RequestHistoryRecord = {
  id: number;
  from_status: RequestStatus;
  to_status: RequestStatus;
  remark: string | null;
  actor: PublicUser;
  created_at: string;
};

export type PaginationMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type PaginatedRequests = {
  data: ServiceRequest[];
  meta: PaginationMeta;
};

export type ApiErrorPayload = {
  message?: string;
  errors?: Record<string, string[]>;
  error?: { code?: string };
};

export type CreateRequestPayload = {
  title: string;
  description: string;
  priority: RequestPriority;
};

export type UpdateStatusPayload = {
  status: RequestStatus;
  expected_version: number;
  remark?: string;
};
