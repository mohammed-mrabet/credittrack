import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { ApiService } from './api.service';

describe('ApiService', () => {
  let service: ApiService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(ApiService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('envoie uniquement les filtres renseignés', () => {
    service.getInvoices({ status: 'overdue', clientId: null, q: '  atlas ' }).subscribe();

    const req = http.expectOne((r) => r.url === '/api/invoices');
    expect(req.request.params.get('status')).toBe('overdue');
    expect(req.request.params.get('q')).toBe('atlas');
    expect(req.request.params.has('clientId')).toBeFalse();
    req.flush([]);
  });

  it('enregistre un paiement avec POST /invoices/{id}/pay', () => {
    service.markAsPaid(12, '2026-11-02').subscribe();

    const req = http.expectOne('/api/invoices/12/pay');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ paidAt: '2026-11-02' });
    req.flush({});
  });
});
