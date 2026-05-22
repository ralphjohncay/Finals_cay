/** Matches RALPHS Footwear web palette (homepage + admin). */
export const colors = {
  background: '#faf6f0',
  backgroundAlt: '#f0e8df',
  surface: '#ffffff',
  text: '#2b2b2b',
  textMuted: '#6c757d',
  heading: '#3f2a24',
  primary: '#8f5644',
  primaryDark: '#6e3828',
  primaryDeep: '#3d221c',
  navbar: '#0c0c0c',
  border: 'rgba(105, 62, 48, 0.12)',
  error: '#b42318',
  success: '#2d6a4f',
};

export const gradients = {
  hero: ['#f0dcc4', '#c49a78', '#8f5644', '#4a2820'],
  button: ['#e6d2b8', '#b88968', '#7d4a3a', '#3d221c'],
};

export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
};

export const radius = {
  sm: 8,
  md: 12,
  lg: 18,
  pill: 999,
};

export const typography = {
  title: { fontSize: 28, fontWeight: '700' as const, color: colors.heading },
  subtitle: { fontSize: 16, color: colors.textMuted },
  body: { fontSize: 15, color: colors.text, lineHeight: 22 },
  label: { fontSize: 13, fontWeight: '600' as const, color: colors.heading },
};
