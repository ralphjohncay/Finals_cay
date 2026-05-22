import React, { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { AppButton } from '../components/AppButton';
import { ScreenHeader } from '../components/ScreenHeader';
import { fetchServices } from '../api/catalog';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import type { Service } from '../api/types';
import { colors, radius, spacing } from '../theme';

export function ServicesScreen() {
  const { token } = useAuth();
  const { addService } = useCart();
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    if (!token) return;
    setServices(await fetchServices(token));
  }, [token]);

  React.useEffect(() => {
    load()
      .catch(() => setServices([]))
      .finally(() => setLoading(false));
  }, [load]);

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator color={colors.primary} size="large" />
      </View>
    );
  }

  return (
    <View style={styles.root}>
      <ScreenHeader title="Services" subtitle="Gift wrap, express handling, and more" />
      <FlatList
        data={services}
        keyExtractor={(item) => String(item.id)}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={async () => {
              setRefreshing(true);
              await load().catch(() => undefined);
              setRefreshing(false);
            }}
          />
        }
        contentContainerStyle={{ gap: spacing.md, paddingBottom: spacing.xl }}
        renderItem={({ item }) => (
          <View style={styles.card}>
            <Text style={styles.name}>{item.name}</Text>
            <Text style={styles.desc}>{item.description}</Text>
            <Text style={styles.price}>${item.price}</Text>
            <AppButton label="Add to cart" onPress={() => addService(item)} style={{ marginTop: spacing.sm }} />
          </View>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
  },
  name: { fontSize: 18, fontWeight: '700', color: colors.heading },
  desc: { color: colors.textMuted, marginTop: 6, lineHeight: 20 },
  price: { fontSize: 18, fontWeight: '700', color: colors.primary, marginTop: 8 },
});
