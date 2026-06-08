/**
 * Images du dashboard (`src/assets/images/`) — PNG fournis par l’équipe.
 * Essai `.webp` homonyme puis SVG de secours via `onDashImageError` dans le composant.
 *
 * Cartographie :
 * - image1.png   — hero
 * - image2–4.png — cartes stats Patients / Médecins / Rendez-vous
 * - image10.png  — carte stat Alertes
 * - image5.png   — vitrine « performance & conformité »
 * - image6.png   — vitrine « parcours patient »
 * - image7.png   — bandeau décoratif infirmier (mobile)
 * - image8.png   — tuile rapide « Patients » (dashboard infirmier)
 * - image9.png   — tuile rapide « Constantes » (dashboard infirmier)
 *
 * image10 est aussi utilisé pour la tuile « Alertes » infirmier (même fichier que la carte stat alertes).
 */
const IMG = '/assets/images';

export const dashImageUrls = {
  hero: `${IMG}/image1.png`,
  patients: `${IMG}/image2.png`,
  doctors: `${IMG}/image3.png`,
  appointments: `${IMG}/image4.png`,
  alerts: `${IMG}/image10.png`,
  sectionQuality: `${IMG}/image5.png`,
  sectionCare: `${IMG}/image6.png`,
  nurseTilePatients: `${IMG}/image8.png`,
  nurseTileVitals: `${IMG}/image9.png`,
} as const;

/** Bandeau décoratif (dashboard infirmier, mobile). */
export const dashNurseStripUrl = `${IMG}/image7.png`;

export type DashImageKey = keyof typeof dashImageUrls;

export const dashImageFallbacks: Record<DashImageKey, string> = {
  hero: '/assets/brand/dashboard-hero.svg',
  patients: '/assets/brand/dash-stat-patients.svg',
  doctors: '/assets/brand/dash-stat-doctors.svg',
  appointments: '/assets/brand/dash-stat-appointments.svg',
  alerts: '/assets/brand/dash-stat-alerts.svg',
  sectionCare: '/assets/brand/dashboard-hero.svg',
  sectionQuality: '/assets/brand/brand-aurora.svg',
  nurseTilePatients: '/assets/brand/dash-stat-patients.svg',
  nurseTileVitals: '/assets/brand/dash-stat-appointments.svg',
};
