import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useAuth } from '../context/AuthContext';
import { ScreenHeader } from '../components/ScreenHeader';
import { colors, radius, spacing } from '../theme';

export function HomeScreen() {
  const { user } = useAuth();

  return (
    <View style={styles.root}>
      <ScreenHeader
        title={`Welcome${user?.name ? `, ${user.name}` : ''}`}
        subtitle="Browse products, add services, and place orders — same data as the website."
      />
      <View style={styles.grid}>
        <Feature icon="✓" title="Quality Assurance" text="Products and stock from your Symfony database." />
        <Feature icon="⚡" title="Fast Processing" text="Orders use pending_approval like the web flow." />
        <Feature icon="📦" title="Track Orders" text="View status: pending, approved, completed." />
      </View>
    </View>
  );
}

function Feature({ icon, title, text }: { icon: string; title: string; text: string }) {
  return (
    <View style={styles.card}>
      <Text style={styles.icon}>{icon}</Text>
      <Text style={styles.cardTitle}>{title}</Text>
      <Text style={styles.cardText}>{text}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  grid: { gap: spacing.md },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
  },
  icon: {
    width: 48,
    height: 48,
    lineHeight: 48,
    textAlign: 'center',
    backgroundColor: colors.primary,
    color: '#fff',
    borderRadius: 24,
    overflow: 'hidden',
    marginBottom: spacing.sm,
    fontWeight: '700',
  },
  cardTitle: { fontSize: 18, fontWeight: '700', color: colors.heading, marginBottom: 6 },
  cardText: { color: colors.textMuted, lineHeight: 20 },
});
