export type InvoiceStatus = 'paid' | 'unpaid' | 'overdue';

export interface Client {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  createdAt: string;
}

export interface Invoice {
  id: number;
  number: string;
  client: { id: number; name: string };
  amount: number;
  description: string | null;
  issueDate: string;
  dueDate: string;
  paidAt: string | null;
  status: InvoiceStatus;
  daysOverdue: number;
}

export interface NewInvoice {
  clientId: number;
  amount: number;
  issueDate: string;
  dueDate: string;
  description?: string;
}

export interface NewClient {
  name: string;
  email?: string;
  phone?: string;
}

export interface StatusTotals {
  total: number;
  count: number;
}

export interface Dashboard {
  paid: StatusTotals;
  unpaid: StatusTotals;
  overdue: StatusTotals;
  outstanding: number;
  collectionRate: number;
  topClients: { clientId: number; clientName: string; outstanding: number; overdue: number }[];
  overdueInvoices: Invoice[];
}

export interface InvoiceFilters {
  status?: InvoiceStatus | '';
  clientId?: number | null;
  q?: string;
}

/** Erreurs de validation renvoyées par l'API : { errors: { champ: message } } */
export type ApiErrors = Record<string, string>;
