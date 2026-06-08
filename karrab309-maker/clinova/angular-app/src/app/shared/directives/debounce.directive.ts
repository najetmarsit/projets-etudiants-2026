import { Directive, Input, Output, EventEmitter, OnInit, OnDestroy } from '@angular/core';
import { NgControl } from '@angular/forms';
import { Subject, debounceTime, distinctUntilChanged, takeUntil } from 'rxjs';

@Directive({
  selector: '[appDebounce]',
  standalone: true,
})
export class DebounceDirective implements OnInit, OnDestroy {
  @Input() debounceTime = 400;
  @Output() debouncedChange = new EventEmitter<string>();

  private destroy$ = new Subject<void>();

  constructor(private ngControl: NgControl) {}

  ngOnInit(): void {
    this.ngControl.valueChanges?.pipe(
      debounceTime(this.debounceTime),
      distinctUntilChanged(),
      takeUntil(this.destroy$),
    ).subscribe((value) => {
      this.debouncedChange.emit(value);
    });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}
