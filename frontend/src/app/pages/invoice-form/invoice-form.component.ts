import { Component, OnInit, inject, signal } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ApiErrors, Client } from '../../core/models';

/** Format AAAA-MM-JJ attendu par l'API. */
export function toIsoDate(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/** L'échéance ne peut pas précéder la date d'émission. */
export const dueAfterIssueValidator: ValidatorFn = (group: AbstractControl): ValidationErrors | null => {
  const issue = group.get('issueDate')?.value;
  const due = group.get('dueDate')?.value;
  return issue && due && due < issue ? { dueBeforeIssue: true } : null;
};

@Component({
  selector: 'app-invoice-form',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './invoice-form.component.html',
})
export class InvoiceFormComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);

  readonly clients = signal<Client[]>([]);
  readonly serverErrors = signal<ApiErrors>({});
  readonly saving = signal(false);

  readonly form = this.fb.nonNullable.group(
    {
      clientId: [0, [Validators.required, Validators.min(1)]],
      amount: [null as number | null, [Validators.required, Validators.min(0.01)]],
      issueDate: [toIsoDate(new Date()), Validators.required],
      dueDate: [toIsoDate(new Date(Date.now() + 30 * 86_400_000)), Validators.required],
      description: ['', Validators.maxLength(255)],
    },
    { validators: dueAfterIssueValidator },
  );

  ngOnInit(): void {
    this.api.getClients().subscribe((c) => this.clients.set(c));
  }

  /** Raccourci : échéance à J+30, J+45 ou J+60 à partir de la date d'émission. */
  setTerm(days: number): void {
    const issue = new Date(this.form.controls.issueDate.value);
    issue.setDate(issue.getDate() + days);
    this.form.controls.dueDate.setValue(toIsoDate(issue));
  }

  invalid(name: keyof typeof this.form.controls): boolean {
    const control = this.form.controls[name];
    return control.invalid && (control.touched || control.dirty);
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.saving.set(true);
    this.api
      .createInvoice({
        clientId: value.clientId,
        amount: value.amount!,
        issueDate: value.issueDate,
        dueDate: value.dueDate,
        description: value.description || undefined,
      })
      .subscribe({
        next: () => this.router.navigate(['/factures']),
        error: (e) => {
          this.serverErrors.set(e.error?.errors ?? { global: "Erreur lors de l'enregistrement." });
          this.saving.set(false);
        },
      });
  }
}
