import { animate, animateChild, group, query, sequence, stagger, state, style, transition, trigger } from '@angular/animations';

export const fadeInUp = trigger('fadeInUp', [
  transition(':enter', [
    style({ opacity: 0, transform: 'translateY(24px)' }),
    animate('0.45s cubic-bezier(0.4, 0, 0.2, 1)', style({ opacity: 1, transform: 'translateY(0)' })),
  ]),
]);

export const fadeIn = trigger('fadeIn', [
  transition(':enter', [
    style({ opacity: 0 }),
    animate('0.3s ease-out', style({ opacity: 1 })),
  ]),
  transition(':leave', [
    animate('0.2s ease-in', style({ opacity: 0 })),
  ]),
]);

export const slideInRight = trigger('slideInRight', [
  transition(':enter', [
    style({ opacity: 0, transform: 'translateX(30px)' }),
    animate('0.4s cubic-bezier(0.4, 0, 0.2, 1)', style({ opacity: 1, transform: 'translateX(0)' })),
  ]),
]);

export const scaleIn = trigger('scaleIn', [
  transition(':enter', [
    style({ opacity: 0, transform: 'scale(0.92)' }),
    animate('0.35s cubic-bezier(0.4, 0, 0.2, 1)', style({ opacity: 1, transform: 'scale(1)' })),
  ]),
]);

export const listAnimation = trigger('listAnimation', [
  transition(':enter', [
    query(':enter', [
      style({ opacity: 0, transform: 'translateY(16px)' }),
      stagger(60, [
        animate('0.35s cubic-bezier(0.4, 0, 0.2, 1)', style({ opacity: 1, transform: 'translateY(0)' })),
      ]),
    ], { optional: true }),
  ]),
]);

export const expandCollapse = trigger('expandCollapse', [
  transition(':enter', [
    style({ height: 0, opacity: 0, overflow: 'hidden' }),
    animate('0.3s ease-out', style({ height: '*', opacity: 1 })),
  ]),
  transition(':leave', [
    style({ overflow: 'hidden' }),
    animate('0.2s ease-in', style({ height: 0, opacity: 0 })),
  ]),
]);

export const cardHover = trigger('cardHover', [
  state('default', style({
    transform: 'translateY(0)',
    boxShadow: 'var(--shadow), var(--shadow-ring)',
  })),
  state('hovered', style({
    transform: 'translateY(-4px)',
    boxShadow: 'var(--shadow-lg), var(--shadow-ring)',
  })),
  transition('default <=> hovered', [
    animate('0.25s cubic-bezier(0.4, 0, 0.2, 1)'),
  ]),
]);

export const routeAnimations = trigger('routeAnimations', [
  transition('* <=> *', [
    style({ position: 'relative' }),
    query(':enter, :leave', [
      style({
        position: 'absolute',
        top: 0,
        left: 0,
        width: '100%',
      }),
    ], { optional: true }),
    group([
      query(':leave', [
        animate('0.25s ease-out', style({ opacity: 0, transform: 'translateY(-12px)' })),
      ], { optional: true }),
      query(':enter', [
        style({ opacity: 0, transform: 'translateY(12px)' }),
        animate('0.35s 0.1s ease-out', style({ opacity: 1, transform: 'translateY(0)' })),
      ], { optional: true }),
    ]),
  ]),
]);

export const staggerFadeIn = trigger('staggerFadeIn', [
  transition(':enter', [
    query(':enter', [
      style({ opacity: 0, transform: 'translateY(16px)' }),
      stagger(80, [
        animate('0.4s cubic-bezier(0.4, 0, 0.2, 1)', style({ opacity: 1, transform: 'translateY(0)' })),
      ]),
    ], { optional: true }),
  ]),
]);

export const counterAnimation = trigger('counterAnimation', [
  transition(':enter', [
    style({ opacity: 0, transform: 'scale(0.5)' }),
    animate('0.6s cubic-bezier(0.34, 1.56, 0.64, 1)', style({ opacity: 1, transform: 'scale(1)' })),
  ]),
]);
