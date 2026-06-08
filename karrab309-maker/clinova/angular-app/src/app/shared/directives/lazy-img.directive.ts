import { Directive, ElementRef, Input, OnDestroy, AfterViewInit } from '@angular/core';

@Directive({
  selector: 'img[appLazyImg]',
  standalone: true,
})
export class LazyImgDirective implements AfterViewInit, OnDestroy {
  @Input() appLazyImg = '';
  @Input() fallback = '';

  private observer: IntersectionObserver | null = null;

  constructor(private el: ElementRef<HTMLImageElement>) {}

  ngAfterViewInit(): void {
    const img = this.el.nativeElement;
    img.loading = 'lazy';

    if (this.appLazyImg) {
      img.dataset['src'] = this.appLazyImg;
      img.removeAttribute('src');
    }

    this.observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const src = img.dataset['src'];
          if (src) {
            img.src = src;
            img.removeAttribute('data-src');
          }
          if (this.fallback) {
            img.addEventListener('error', () => {
              img.src = this.fallback;
            }, { once: true });
          }
          this.observer?.unobserve(img);
        }
      });
    }, { rootMargin: '200px' });

    this.observer.observe(img);
  }

  ngOnDestroy(): void {
    this.observer?.disconnect();
  }
}
