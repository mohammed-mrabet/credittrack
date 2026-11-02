import { FormControl, FormGroup } from '@angular/forms';
import { dueAfterIssueValidator, toIsoDate } from './invoice-form.component';

describe('Formulaire de facture', () => {
  const group = (issueDate: string, dueDate: string) =>
    new FormGroup({ issueDate: new FormControl(issueDate), dueDate: new FormControl(dueDate) });

  it("refuse une échéance antérieure à la date d'émission", () => {
    expect(dueAfterIssueValidator(group('2026-11-10', '2026-11-01'))).toEqual({ dueBeforeIssue: true });
  });

  it('accepte une échéance le même jour ou plus tard', () => {
    expect(dueAfterIssueValidator(group('2026-11-10', '2026-11-10'))).toBeNull();
    expect(dueAfterIssueValidator(group('2026-11-10', '2026-12-10'))).toBeNull();
  });

  it('formate les dates en AAAA-MM-JJ', () => {
    expect(toIsoDate(new Date(2026, 0, 5))).toBe('2026-01-05');
  });
});
