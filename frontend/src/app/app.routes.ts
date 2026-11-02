import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: '', title: 'Tableau de bord · CreditTrack', loadComponent: () => import('./pages/dashboard/dashboard.component').then((m) => m.DashboardComponent) },
  { path: 'factures', title: 'Factures · CreditTrack', loadComponent: () => import('./pages/invoice-list/invoice-list.component').then((m) => m.InvoiceListComponent) },
  { path: 'factures/nouvelle', title: 'Nouvelle facture · CreditTrack', loadComponent: () => import('./pages/invoice-form/invoice-form.component').then((m) => m.InvoiceFormComponent) },
  { path: 'clients', title: 'Clients · CreditTrack', loadComponent: () => import('./pages/clients/clients.component').then((m) => m.ClientsComponent) },
  { path: '**', redirectTo: '' },
];
