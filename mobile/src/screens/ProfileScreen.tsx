import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { AppButton } from '../components/AppButton';
import { ScreenHeader } from '../components/ScreenHeader';
import { useAuth } from '../context/AuthContext';
import { API_URL } from '../config';
import { colors, radius, spacing } from '../theme';

export function ProfileScreen() {
  const { user, signOut } = useAuth();

  return (
    <View style={styles.root}>
      <ScreenHeader title="Profile" subtitle="Synced with Symfony users" />
      <View style={styles.card}>
        <Text style={styles.name}>{user?.name}</Text>
        <Text style={styles.email}>{user?.email}</Text>
        <Text style={styles.roles}>{user?.roles?.join(', ')}</Text>
        <Text style={styles.api}>API: {API_URL}</Text>
      </View>
      <AppButton label="Sign out" variant="outline" onPress={() => signOut()} />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: spacing.lg,
  },
  name: { fontSize: 22, fontWeight: '700', color: colors.heading },
  email: { color: colors.textMuted, marginTop: 4 },
  roles: { marginTop: spacing.sm, color: colors.primary, fontWeight: '600' },
  api: { marginTop: spacing.md, fontSize: 12, color: colors.textMuted },
});
